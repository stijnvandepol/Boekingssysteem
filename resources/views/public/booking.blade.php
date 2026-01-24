@php use Carbon\Carbon; @endphp
@extends('layouts.public')

@section('content')
    @include('partials.alerts')

    <div class="grid grid-2">
        <div class="card">
            <h2>📅 Beschikbare dagen</h2>

            @if ($slotsByDate->isEmpty())
                <div class="muted" style="padding: 20px; text-align: center;">
                    <div style="font-size: 1.1rem; margin-bottom: 8px;">Geen beschikbaarheid</div>
                    <div style="font-size: 0.9rem;">Helaas is er geen beschikbaarheid in de komende 30 dagen.</div>
                </div>
            @else
                <div class="slots" style="margin-top: 8px;">
                    @foreach ($slotsByDate as $date => $slots)
                        @php
                            $day = Carbon::parse($date, $timezone);
                            $label = $day->isSameDay($today)
                                ? 'Vandaag'
                                : ($day->isSameDay($today->copy()->addDay()) ? 'Morgen' : $day->locale('nl')->isoFormat('dd D MMM'));
                        @endphp
                        <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
                            <strong style="color: var(--ink); font-weight: 600;">{{ $label }}</strong>
                            <div class="muted" style="font-size: 0.85rem; margin-top: 2px;">{{ $day->locale('nl')->isoFormat('dddd D MMMM') }}</div>
                            <div class="slots" style="margin-top: 12px;">
                                @foreach ($slots as $slot)
                                    @php
                                        $localStart = $slot->starts_at->setTimezone($timezone);
                                        $localEnd = $slot->ends_at->setTimezone($timezone);
                                    @endphp
                                    <a class="slot" href="{{ route('booking.index', ['slot' => $slot->id]) }}#booking">
                                        <div>
                                            <strong style="color: var(--ink);">{{ $localStart->format('H:i') }} - {{ $localEnd->format('H:i') }}</strong>
                                            <div class="muted" style="margin-top: 4px;">{{ $slot->resource->name }}</div>
                                        </div>
                                        <div style="white-space: nowrap; margin-left: 12px;">
                                            <span style="background: rgba(5, 150, 105, 0.1); color: var(--accent); padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                                                {{ $slot->remainingCapacity() }} vrij
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card" id="booking">
            <h2>✏️ Afspraak boeken</h2>

            @if (! $selectedSlot)
                <div class="muted" style="padding: 20px; text-align: center;">
                    <div style="font-size: 1.1rem; margin-bottom: 8px;">Selecteer een tijdslot</div>
                    <div style="font-size: 0.9rem;">Klik op een beschikbaar tijdslot aan de linkerkant.</div>
                </div>
            @else
                @php
                    $localStart = $selectedSlot->starts_at->setTimezone($timezone);
                    $localEnd = $selectedSlot->ends_at->setTimezone($timezone);
                    $maxDuration = $localStart->diffInMinutes($localEnd);
                @endphp
                <div style="background: rgba(5, 150, 105, 0.05); border: 1px solid rgba(5, 150, 105, 0.2); border-radius: 8px; padding: 12px 14px; margin-bottom: 20px; font-size: 0.95rem;">
                    <strong style="color: var(--accent); display: block; margin-bottom: 4px;">{{ $selectedSlot->resource->name }}</strong>
                    <div class="muted">
                        {{ $localStart->locale('nl')->isoFormat('dd D MMM') }},
                        {{ $localStart->format('H:i') }} - {{ $localEnd->format('H:i') }}
                    </div>
                </div>

                <form method="post" action="{{ route('booking.store') }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                    <input type="hidden" name="slot_instance_id" value="{{ $selectedSlot->id }}">

                    <label for="duration_minutes">Duur (minuten)</label>
                    <select name="duration_minutes" id="duration_minutes" required>
                        @foreach ($durations as $duration)
                            @if ($duration <= $maxDuration)
                                <option value="{{ $duration }}" @selected((int) old('duration_minutes', $maxDuration) === $duration)>
                                    {{ $duration }} min
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <label for="name">Naam *</label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="Voornaam Achternaam">

                    <label for="phone">Telefoonnummer *</label>
                    <input type="text" name="phone" id="phone" required value="{{ old('phone') }}" placeholder="+31 (0)6 ...">

                    <label for="email">E-mailadres *</label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="jouw@email.nl">

                    @if (config('services.turnstile.enabled'))
                        <label for="turnstile_token">Beveiligingsverificatie</label>
                        <input type="text" name="turnstile_token" id="turnstile_token" placeholder="Vul verificatie in">
                        <div class="muted" style="margin-top: -12px; margin-bottom: 16px; font-size: 0.85rem;">Cloudflare Turnstile geïntegreerd voor productie.</div>
                    @endif

                    <button type="submit" style="margin-bottom: 0;">✓ Bevestig afspraak</button>
                </form>
            @endif
        </div>
    </div>
@endsection
