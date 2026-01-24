<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\SlotInstance;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();

        $weekOffset = (int) $request->integer('week', 0);
        $now = $this->currentTime($resource->timezone);
        $weekStart = $now->copy()->startOfWeek()->addWeeks($weekOffset);
        $weekEnd = $weekStart->copy()->endOfWeek();

        $query = Booking::with(['slotInstance', 'guests'])
            ->where('resource_id', $resource->id)
            ->orderByDesc('booked_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $date = Carbon::parse($request->input('date'), $resource->timezone);
            $query->whereBetween('booked_at', [$date->copy()->startOfDay()->utc(), $date->copy()->endOfDay()->utc()]);
        }

        $bookings = $query->paginate(20)->withQueryString();

        // Weekagenda: toon alleen bevestigde boekingen, ook als lijstfilter op geannuleerd staat
        $weeklyBookings = Booking::with(['slotInstance', 'guests'])
            ->where('resource_id', $resource->id)
            ->where('status', 'confirmed')
            ->whereHas('slotInstance', function ($slotQuery) use ($weekStart, $weekEnd) {
                $slotQuery->whereBetween('starts_at', [
                    $weekStart->copy()->startOfDay()->utc(),
                    $weekEnd->copy()->endOfDay()->utc(),
                ]);
            })
            ->get()
            ->sortBy(fn ($booking) => $booking->slotInstance?->starts_at ?? $booking->booked_at);

        $weekDays = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));

        return view('admin.bookings', [
            'resource' => $resource,
            'bookings' => $bookings,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekOffset' => $weekOffset,
            'weekDays' => $weekDays,
            'weeklyBookings' => $weeklyBookings,
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();
        if ((int) $booking->resource_id !== (int) $resource->id) {
            abort(403);
        }

        DB::transaction(function () use ($booking, $resource) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($lockedBooking->status !== 'confirmed') {
                return;
            }

            $lockedBooking->update(['status' => 'cancelled']);

            if ($lockedBooking->slot_instance_id) {
                $lockedSlot = SlotInstance::whereKey($lockedBooking->slot_instance_id)->lockForUpdate()->first();
                if ($lockedSlot) {
                    $newCount = max(0, $lockedSlot->booked_count - 1);
                    $lockedSlot->update([
                        'booked_count' => $newCount,
                        'status' => $newCount < $lockedSlot->capacity ? 'open' : $lockedSlot->status,
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $resource->user_id,
                'action' => 'booking.cancelled',
                'auditable_type' => Booking::class,
                'auditable_id' => $lockedBooking->id,
                'metadata' => [
                    'slot_instance_id' => $lockedBooking->slot_instance_id,
                ],
                'created_at' => now(),
            ]);
        });

        return redirect()->route('admin.bookings.index')->with('status', 'Boeking geannuleerd.');
    }

    public function storeManual(Request $request, BookingService $bookingService)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();

        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $start = Carbon::parse($data['start_at'], $resource->timezone);
        $end = $start->copy()->addMinutes($resource->default_slot_length_minutes);

        $slot = SlotInstance::create([
            'resource_id' => $resource->id,
            'availability_block_id' => null,
            'starts_at' => $start->utc(),
            'ends_at' => $end->utc(),
            'capacity' => $resource->default_capacity,
            'booked_count' => 0,
            'status' => 'open',
        ]);

        try {
            $bookingService->book(
                $slot,
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? '',
                ],
                (string) Str::uuid()
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('admin.bookings.index')->with('status', 'Boeking handmatig toegevoegd.');
    }

    private function currentTime(string $timezone): Carbon
    {
        try {
            $response = Http::timeout(3)->get("https://worldtimeapi.org/api/timezone/{$timezone}");
            if ($response->ok() && ($dt = $response->json('datetime'))) {
                return Carbon::parse($dt)->setTimezone($timezone);
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return Carbon::now($timezone);
    }
}
