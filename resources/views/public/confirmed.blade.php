@php use Carbon\Carbon; @endphp
@extends('layouts.public')

@section('content')
    <div class="success">Je boeking is bevestigd.</div>

    <div class="card">
        <strong>Details</strong>
        <div class="muted">
            Resource: {{ $booking->resource->name }}<br>
            Datum/tijd:
            {{ $booking->slotInstance->starts_at->setTimezone($booking->resource->timezone)->format('d-m-Y H:i') }}
            -
            {{ $booking->slotInstance->ends_at->setTimezone($booking->resource->timezone)->format('H:i') }}
            <br>
            Duur: {{ $booking->duration_minutes ?? $booking->slotInstance->starts_at->diffInMinutes($booking->slotInstance->ends_at) }} min
        </div>
    </div>

    <a href="{{ route('booking.index') }}">Nieuwe boeking</a>
@endsection
