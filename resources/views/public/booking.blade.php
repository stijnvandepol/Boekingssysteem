@php use Carbon\Carbon; @endphp
@extends('layouts.public')

@section('content')
    @include('partials.alerts')

    <div class="grid grid-2">
        <div class="card">
<<<<<<< HEAD
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
=======
            <form method="get" action="{{ route('booking.index') }}">
                <label for="resource_id">Kies beheerder/resource</label>
                <select name="resource_id" id="resource_id" required>
                    <option value="">Selecteer...</option>
                    @foreach ($resources as $res)
                        <option value="{{ $res->id }}" @selected($resource && $resource->id === $res->id)>{{ $res->name }}</option>
>>>>>>> parent of 1568204 (errors fixed)
                    @endforeach
                </select>

                <label for="date">Datum</label>
                <input type="date" name="date" id="date" value="{{ $selectedDate }}">

                <button type="submit">Beschikbaarheid tonen</button>
            </form>
        </div>

<<<<<<< HEAD
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
=======
        <div class="card">
            <form method="post" action="{{ route('booking.store') }}">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

                <label for="slot_instance_id">Slot</label>
                <select name="slot_instance_id" id="slot_instance_id" required>
                    <option value="">Selecteer een slot...</option>
                    @foreach ($slots as $slot)
                        @php
                            $localStart = $slot->starts_at->setTimezone($resource?->timezone ?? 'Europe/Amsterdam');
                            $localEnd = $slot->ends_at->setTimezone($resource?->timezone ?? 'Europe/Amsterdam');
                        @endphp
                        <option value="{{ $slot->id }}">
                            {{ $localStart->format('H:i') }} - {{ $localEnd->format('H:i') }}
                            ({{ $slot->remainingCapacity() }} plek(ken) vrij)
                        </option>
                    @endforeach
                </select>
>>>>>>> parent of 1568204 (errors fixed)

                <label for="name">Naam</label>
                <input type="text" name="name" id="name" required value="{{ old('name') }}">

<<<<<<< HEAD
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

                    <label for="email">E-mailadres</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="jouw@email.nl">

                    @if (config('services.turnstile.enabled'))
                        <label for="turnstile_token">Beveiligingsverificatie</label>
                        <input type="text" name="turnstile_token" id="turnstile_token" placeholder="Vul verificatie in">
                        <div class="muted" style="margin-top: -12px; margin-bottom: 16px; font-size: 0.85rem;">Cloudflare Turnstile geïntegreerd voor productie.</div>
                    @endif

                    <button type="submit" style="margin-bottom: 0;">✓ Bevestig afspraak</button>
                </form>
            @endif
=======
                <label for="email">E‑mail</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}">

                <label for="phone">Telefoon (optioneel)</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}">

                @if (config('services.turnstile.enabled'))
                    <label for="turnstile_token">Turnstile token</label>
                    <input type="text" name="turnstile_token" id="turnstile_token" placeholder="Vul token in">
                    <div class="muted">Integreer Cloudflare Turnstile in de frontend voor productie.</div>
                @endif

                <button type="submit">Boek slot</button>
            </form>
>>>>>>> parent of 1568204 (errors fixed)
        </div>
    </div>

    @if ($resource)
        <div class="card" style="margin-top:16px;">
            <strong>Slots op {{ $selectedDate }}</strong>
            <div class="slots" style="margin-top:12px;">
                @forelse ($slots as $slot)
                    @php
                        $localStart = $slot->starts_at->setTimezone($resource->timezone);
                        $localEnd = $slot->ends_at->setTimezone($resource->timezone);
                    @endphp
                    <div class="slot">
                        <div>
                            {{ $localStart->format('H:i') }} - {{ $localEnd->format('H:i') }}
                            <div class="muted">{{ $resource->name }}</div>
                        </div>
                        <div>{{ $slot->remainingCapacity() }} vrij</div>
                    </div>
                @empty
                    <div class="muted">Geen slots beschikbaar op deze datum.</div>
                @endforelse
            </div>
        </div>
    @else
        <div class="card" style="margin-top:16px;">
            <div class="muted">Selecteer een resource om beschikbare slots te zien.</div>
        </div>
    @endif
@endsection
