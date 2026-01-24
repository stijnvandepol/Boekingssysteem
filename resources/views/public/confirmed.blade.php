@php use Carbon\Carbon; @endphp
@extends('layouts.public')

@section('content')
    <div class="success" style="border-left: 4px solid #059669; padding: 16px;">
        <strong style="color: var(--success); display: block; margin-bottom: 4px;">✓ Boeking bevestigd</strong>
        Je afspraak is succesvol ingeplan!
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2 style="color: var(--accent); margin-bottom: 16px;">📋 Boekingsdetails</h2>
        <div style="background: rgba(5, 150, 105, 0.04); border-left: 4px solid var(--accent); padding: 16px; border-radius: 8px;">
            <div style="display: grid; gap: 12px; font-size: 0.95rem;">
                <div>
                    <div class="muted" style="margin-bottom: 4px;">Bron</div>
                    <strong>{{ $booking->resource->name }}</strong>
                </div>
                <div>
                    <div class="muted" style="margin-bottom: 4px;">Datum</div>
                    <strong>{{ $booking->slotInstance->starts_at->setTimezone($booking->resource->timezone)->locale('nl')->isoFormat('dddd D MMMM YYYY') }}</strong>
                </div>
                <div>
                    <div class="muted" style="margin-bottom: 4px;">Tijd</div>
                    <strong>
                        {{ $booking->slotInstance->starts_at->setTimezone($booking->resource->timezone)->format('H:i') }} -
                        {{ $booking->slotInstance->ends_at->setTimezone($booking->resource->timezone)->format('H:i') }}
                    </strong>
                </div>
                <div>
                    <div class="muted" style="margin-bottom: 4px;">Duur</div>
                    <strong>{{ $booking->duration_minutes ?? $booking->slotInstance->starts_at->diffInMinutes($booking->slotInstance->ends_at) }} minuten</strong>
                </div>
            </div>
        </div>

        @php
            $icsUrl = URL::signedRoute('booking.ics', ['booking' => $booking->id]);
        @endphp
        <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="{{ $icsUrl }}" class="button" style="display: inline-block; padding: 12px 16px; width: auto;">➕ Voeg toe aan agenda (ICS)</a>
            <a href="https://www.google.com/calendar/render?action=TEMPLATE&text={{ urlencode(config('app.name').' - '.$booking->resource->name) }}&dates={{ $booking->slotInstance->starts_at->setTimezone('UTC')->format('Ymd\\THis\\Z') }}/{{ $booking->slotInstance->ends_at->setTimezone('UTC')->format('Ymd\\THis\\Z') }}&details={{ urlencode('Afspraak met '.$booking->resource->name) }}&ctz={{ urlencode($booking->resource->timezone) }}" target="_blank" rel="noopener" class="button" style="display: inline-block; padding: 12px 16px; width: auto;">📆 Voeg toe in Google Calendar</a>
        </div>
    </div>

    <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-light); text-align: center;">
        <a href="{{ route('booking.index') }}" class="button" style="display: inline-block; padding: 12px 24px; width: auto;">← Terug naar agenda</a>
    </div>
@endsection
