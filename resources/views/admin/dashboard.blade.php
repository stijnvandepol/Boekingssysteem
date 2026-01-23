@php use Carbon\Carbon; @endphp
@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div class="grid grid-2">
        <div class="card">
            <h2>Beschikbaarheid toevoegen</h2>
            <form method="post" action="{{ route('admin.availability.store') }}">
                @csrf
                <input type="hidden" name="resource_id" value="{{ $resource->id }}">

                <label for="starts_at">Start</label>
                <input type="datetime-local" name="starts_at" id="starts_at" required value="{{ $defaultStart->format('Y-m-d\\TH:i') }}">

                <label for="ends_at">Einde</label>
                <input type="datetime-local" name="ends_at" id="ends_at" required value="{{ $defaultEnd->format('Y-m-d\\TH:i') }}">

                <label for="slot_length_minutes">Slotlengte (min)</label>
                <select name="slot_length_minutes" id="slot_length_minutes" required>
                    @foreach (config('booking.allowed_slot_lengths') as $length)
                        <option value="{{ $length }}" @selected($length === $resource->default_slot_length_minutes)>{{ $length }}</option>
                    @endforeach
                </select>

                <label for="capacity">Capaciteit</label>
                <input type="number" min="1" max="50" name="capacity" id="capacity" value="{{ $resource->default_capacity }}" required>

                <button type="submit" style="margin-top:12px;">Toevoegen</button>
            </form>
        </div>

        <div class="card">
            <h2>Resource instellingen</h2>
            <form method="post" action="{{ route('admin.resource.update') }}">
                @csrf
                @method('PUT')
                <label for="name">Naam</label>
                <input type="text" name="name" id="name" value="{{ $resource->name }}" required>

                <label for="timezone">Tijdzone</label>
                <input type="text" name="timezone" id="timezone" value="{{ $resource->timezone }}" required>

                <label for="default_slot_length_minutes">Standaard slotlengte</label>
                <select name="default_slot_length_minutes" id="default_slot_length_minutes" required>
                    @foreach (config('booking.allowed_slot_lengths') as $length)
                        <option value="{{ $length }}" @selected($length === $resource->default_slot_length_minutes)>{{ $length }}</option>
                    @endforeach
                </select>

                <label for="default_capacity">Standaard capaciteit</label>
                <input type="number" min="1" max="50" name="default_capacity" id="default_capacity" value="{{ $resource->default_capacity }}" required>

                <label for="min_notice_minutes">Minimale boekingstijd vooraf (min)</label>
                <input type="number" min="0" max="1440" name="min_notice_minutes" id="min_notice_minutes" value="{{ $resource->min_notice_minutes }}" required>

                <label for="is_active">Actief</label>
                <select name="is_active" id="is_active" required>
                    <option value="1" @selected($resource->is_active)>Ja</option>
                    <option value="0" @selected(! $resource->is_active)>Nee</option>
                </select>

                <button type="submit" style="margin-top:12px;">Opslaan</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <h2>Weekoverzicht ({{ $weekStart->format('d-m-Y') }})</h2>
            <div style="display:flex; gap:8px;">
                <a class="button" href="{{ route('admin.dashboard', ['week' => $weekOffset - 1]) }}">Vorige week</a>
                <a class="button" href="{{ route('admin.dashboard') }}">Huidige week</a>
                <a class="button" href="{{ route('admin.dashboard', ['week' => $weekOffset + 1]) }}">Volgende week</a>
            </div>
        </div>

        @foreach ($weekDays as $day)
            @php
                $dayKey = $day->toDateString();
                $daySlots = $slotsByDate->get($dayKey, collect());
                $dayBlocks = $blocksByDate->get($dayKey, collect());
            @endphp
            <h3>{{ $day->locale('nl')->isoFormat('dddd D MMMM') }}</h3>

            @if ($daySlots->isEmpty())
                <div class="muted">Geen slots.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Start</th>
                            <th>Einde</th>
                            <th>Capaciteit</th>
                            <th>Geboekt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daySlots as $slot)
                            <tr>
                                <td>{{ $slot->starts_at->setTimezone($resource->timezone)->format('H:i') }}</td>
                                <td>{{ $slot->ends_at->setTimezone($resource->timezone)->format('H:i') }}</td>
                                <td>{{ $slot->capacity }}</td>
                                <td>{{ $slot->booked_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($dayBlocks->isNotEmpty())
                <table style="margin-top:8px;">
                    <thead>
                        <tr>
                            <th>Beschikbaarheidsblok</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dayBlocks as $block)
                            <tr>
                                <td>
                                    {{ $block->starts_at->setTimezone($resource->timezone)->format('H:i') }}
                                    - {{ $block->ends_at->setTimezone($resource->timezone)->format('H:i') }}
                                    ({{ $block->slot_length_minutes }} min / cap {{ $block->capacity }})
                                </td>
                                <td style="display:flex; gap:8px; align-items:center;">
                                    <a class="button" href="{{ route('admin.availability.edit', $block) }}">Bewerk</a>
                                    <form method="post" action="{{ route('admin.availability.destroy', $block) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Verwijderen</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    </div>

    <div class="card" style="margin-top:16px;">
        <h2>Recente boekingen</h2>
        <table>
            <thead>
                <tr>
                    <th>Moment</th>
                    <th>Slot</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentBookings as $booking)
                    <tr>
                        <td>{{ $booking->booked_at->format('d-m H:i') }}</td>
                        <td>{{ $booking->slotInstance?->starts_at?->setTimezone($resource->timezone)->format('d-m H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
