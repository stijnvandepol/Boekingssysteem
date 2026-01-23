<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\SlotInstance;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicBookingController extends Controller
{
    public function index(Request $request)
    {
        $resources = Resource::query()->where('is_active', true)->orderBy('name')->get();
        $resource = $resources->firstWhere('id', (int) $request->query('resource_id'));

        $slots = collect();
        $selectedDate = null;
        if ($resource) {
            $selectedDate = $request->query('date') ?: now($resource->timezone)->toDateString();
            $dayStart = Carbon::parse($selectedDate, $resource->timezone)->startOfDay()->utc();
            $dayEnd = Carbon::parse($selectedDate, $resource->timezone)->endOfDay()->utc();
            $slots = SlotInstance::query()
                ->where('resource_id', $resource->id)
                ->whereBetween('starts_at', [$dayStart, $dayEnd])
                ->where('status', 'open')
                ->whereColumn('booked_count', '<', 'capacity')
                ->orderBy('starts_at')
                ->get();
        }

        $idempotencyKey = old('idempotency_key') ?? (string) Str::uuid();

        return view('public.booking', [
            'resources' => $resources,
            'resource' => $resource,
            'slots' => $slots,
            'selectedDate' => $selectedDate,
            'idempotencyKey' => $idempotencyKey,
        ]);
    }

    public function store(StoreBookingRequest $request, BookingService $bookingService)
    {
        $slot = SlotInstance::with('resource')->findOrFail($request->input('slot_instance_id'));

        try {
            $booking = $bookingService->book($slot, $request->only('name', 'email', 'phone'), $request->input('idempotency_key'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $request->session()->put('last_booking_id', $booking->id);

        return redirect()->route('booking.confirmed');
    }

    public function confirmed(Request $request)
    {
        $bookingId = $request->session()->get('last_booking_id');
        if (! $bookingId) {
            return redirect()->route('booking.index');
        }

        $booking = Booking::with(['slotInstance', 'resource', 'guests'])->findOrFail($bookingId);

        return view('public.confirmed', [
            'booking' => $booking,
        ]);
    }
}
