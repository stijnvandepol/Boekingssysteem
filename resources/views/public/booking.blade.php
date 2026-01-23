@php use Carbon\Carbon; @endphp
@extends('layouts.public')

@section('content')
    @include('partials.alerts')

    <div class="grid grid-2">
        <div class="card">
            <form method="get" action="{{ route('booking.index') }}">
                <label for="resource_id">Kies beheerder/resource</label>
                <select name="resource_id" id="resource_id" required>
                    <option value="">Selecteer...</option>
                    @foreach ($resources as $res)
                        <option value="{{ $res->id }}" @selected($resource && $resource->id === $res->id)>{{ $res->name }}</option>
                    @endforeach
                </select>

                <label for="date">Datum</label>
                <input type="date" name="date" id="date" value="{{ $selectedDate }}">

                <button type="submit">Beschikbaarheid tonen</button>
            </form>
        </div>

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

                <label for="name">Naam</label>
                <input type="text" name="name" id="name" required value="{{ old('name') }}">

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
