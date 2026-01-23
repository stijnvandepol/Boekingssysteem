@php use Carbon\Carbon; @endphp
@extends('layouts.public')

@section('content')
    @include('partials.alerts')

    <div class="grid grid-2">
        <div class="card">
            <h2>Beschikbare dagen</h2>

            @if ($slotsByDate->isEmpty())
                <div class="muted">Geen beschikbaarheid in de komende 30 dagen.</div>
            @else
                <div class="slots" style="margin-top:12px;">
                    @foreach ($slotsByDate as $date => $slots)
                        @php
                            $day = Carbon::parse($date, $timezone);
                            $label = $day->isSameDay($today)
                                ? 'Vandaag'
                                : ($day->isSameDay($today->copy()->addDay()) ? 'Morgen' : $day->locale('nl')->isoFormat('dd D MMM'));
                        @endphp
                        <div class="slot" style="flex-direction:column; align-items:stretch;">
                            <strong>{{ $label }}</strong>
                            <div class="muted">{{ $day->locale('nl')->isoFormat('dddd D MMMM') }}</div>
                            <div class="slots" style="margin-top:8px;">
                                @foreach ($slots as $slot)
                                    @php
                                        $localStart = $slot->starts_at->setTimezone($timezone);
                                        $localEnd = $slot->ends_at->setTimezone($timezone);
                                    @endphp
                                    <a class="slot" href="{{ route('booking.index', ['slot' => $slot->id]) }}#booking">
                                        <div>
                                            {{ $localStart->format('H:i') }} - {{ $localEnd->format('H:i') }}
                                            <div class="muted">{{ $slot->resource->name }}</div>
                                        </div>
                                        <div>{{ $slot->remainingCapacity() }} vrij</div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card" id="booking">
            <h2>Afspraak boeken</h2>

            @if (! $selectedSlot)
                <div class="muted">Klik op een tijdslot om te boeken.</div>
            @else
                @php
                    $localStart = $selectedSlot->starts_at->setTimezone($timezone);
                    $localEnd = $selectedSlot->ends_at->setTimezone($timezone);
                    $maxDuration = $localStart->diffInMinutes($localEnd);
                @endphp
                <div class="muted" style="margin-bottom:12px;">
                    {{ $selectedSlot->resource->name }} -
                    {{ $localStart->locale('nl')->isoFormat('dd D MMM') }},
                    {{ $localStart->format('H:i') }} - {{ $localEnd->format('H:i') }}
                </div>

                <form method="post" action="{{ route('booking.store') }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                    <input type="hidden" name="slot_instance_id" value="{{ $selectedSlot->id }}">

                    <label for="duration_minutes">Duur</label>
                    <select name="duration_minutes" id="duration_minutes" required>
                        @foreach ($durations as $duration)
                            @if ($duration <= $maxDuration)
                                <option value="{{ $duration }}" @selected((int) old('duration_minutes', $maxDuration) === $duration)>
                                    {{ $duration }} min
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <label for="name">Naam</label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}">

                    <label for="phone">Telefoonnummer</label>
                    <input type="text" name="phone" id="phone" required value="{{ old('phone') }}">

                    <label for="email">E-mail (optioneel)</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}">

                    @if (config('services.turnstile.enabled'))
                        <label for="turnstile_token">Turnstile token</label>
                        <input type="text" name="turnstile_token" id="turnstile_token" placeholder="Vul token in">
                        <div class="muted">Integreer Cloudflare Turnstile in de frontend voor productie.</div>
                    @endif

                    <button type="submit">Boek afspraak</button>
                </form>
            @endif
        </div>
    </div>
@endsection
