<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\SlotInstance;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicBookingController extends Controller
{
    public function index(Request $request)
    {
        $timezone = config('app.timezone', 'Europe/Amsterdam');
        $now = now($timezone);
        $rangeStart = $now->copy()->utc();
        $rangeEnd = $now->copy()->addDays(30)->endOfDay()->utc();

        $availableSlots = SlotInstance::query()
            ->with('resource')
            ->whereHas('resource', fn ($query) => $query->where('is_active', true))
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->where('status', 'open')
            ->whereColumn('booked_count', '<', 'capacity')
            ->orderBy('starts_at')
            ->get();

        $nowUtc = $now->copy()->utc();
        $availableSlots = $availableSlots->filter(function ($slot) use ($nowUtc) {
            $minNotice = (int) ($slot->resource?->min_notice_minutes ?? 0);
            return $slot->starts_at->gte($nowUtc->copy()->addMinutes($minNotice));
        });

        $slotsByDate = $availableSlots
            ->groupBy(fn ($slot) => $slot->starts_at->setTimezone($timezone)->toDateString())
            ->sortKeys();

        $selectedSlot = null;
        $slotId = (int) $request->query('slot');
        if ($slotId > 0) {
            $selectedSlot = $availableSlots->firstWhere('id', $slotId);
        }

        $idempotencyKey = old('idempotency_key') ?? (string) Str::uuid();
        $durations = config('booking.allowed_durations', config('booking.allowed_slot_lengths'));

        return view('public.booking', [
            'slotsByDate' => $slotsByDate,
            'selectedSlot' => $selectedSlot,
            'idempotencyKey' => $idempotencyKey,
            'timezone' => $timezone,
            'today' => $now->copy()->startOfDay(),
            'durations' => $durations,
        ]);
    }

    public function store(StoreBookingRequest $request, BookingService $bookingService)
    {
        $slot = SlotInstance::with('resource')->findOrFail($request->input('slot_instance_id'));

        try {
            $booking = $bookingService->book(
                $slot,
                $request->only('name', 'email', 'phone'),
                $request->input('idempotency_key')
            );
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

    public function ics(Request $request, Booking $booking)
    {
        $slot = $booking->slotInstance()->with('resource')->firstOrFail();
        $resource = $slot->resource;

        $startUtc = $slot->starts_at->copy()->utc();
        $endUtc = $slot->ends_at->copy()->utc();

        $uid = 'booking-'.$booking->id.'@'.parse_url(config('app.url'), PHP_URL_HOST);
        $summary = config('app.name').' - '.$resource->name;
        $description = 'Afspraak met '.$resource->name;

        $ical = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//{$uid}//NL
METHOD:PUBLISH
BEGIN:VEVENT
UID:{$uid}
DTSTAMP:{$startUtc->format('Ymd\THis\Z')}
DTSTART:{$startUtc->format('Ymd\THis\Z')}
DTEND:{$endUtc->format('Ymd\THis\Z')}
SUMMARY:{$summary}
DESCRIPTION:{$description}
END:VEVENT
END:VCALENDAR
ICS;

        return response($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="booking-'.$booking->id.'.ics"',
        ]);
    }
}
