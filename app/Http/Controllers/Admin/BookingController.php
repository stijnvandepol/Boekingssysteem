<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\SlotInstance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $resource = Resource::where('user_id', $request->user()->id)->firstOrFail();

        $weekOffset = (int) $request->integer('week', 0);
        $weekStart = Carbon::now($resource->timezone)->startOfWeek()->addWeeks($weekOffset);
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

        $weeklyBookings = Booking::with(['slotInstance', 'guests'])
            ->where('resource_id', $resource->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
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
}
