@php use Carbon\Carbon; @endphp
@extends('layouts.public')

@section('content')
    <div class="success" style="border-left: 4px solid #059669; padding: 16px;">
        <strong style="color: var(--success); display: block; margin-bottom: 4px;">✓ Boeking bevestigd</strong>
        Je afspraak is succesvol ingeplan!
    </div>

<<<<<<< HEAD
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
=======
    <div class="card">
        <strong>Details</strong>
        <div class="muted">
            Resource: {{ $booking->resource->name }}<br>
            Datum/tijd:
            {{ $booking->slotInstance->starts_at->setTimezone($booking->resource->timezone)->format('d-m-Y H:i') }}
            -
            {{ $booking->slotInstance->ends_at->setTimezone($booking->resource->timezone)->format('H:i') }}
>>>>>>> parent of 1568204 (errors fixed)
        </div>
    </div>

    <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-light); text-align: center;">
        <a href="{{ route('booking.index') }}" class="button" style="display: inline-block; padding: 12px 24px; width: auto;">← Terug naar agenda</a>
    </div>
@endsection
