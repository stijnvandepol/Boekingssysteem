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
                <input type="datetime-local" name="starts_at" id="starts_at" required>

                <label for="ends_at">Einde</label>
                <input type="datetime-local" name="ends_at" id="ends_at" required>

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
        <h2>Weekoverzicht ({{ $weekStart->format('d-m-Y') }})</h2>
        @foreach ($slotsByDate as $date => $slots)
            <h3>{{ Carbon::parse($date)->format('d-m-Y') }}</h3>
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
                    @foreach ($slots as $slot)
                        <tr>
                            <td>{{ $slot->starts_at->setTimezone($resource->timezone)->format('H:i') }}</td>
                            <td>{{ $slot->ends_at->setTimezone($resource->timezone)->format('H:i') }}</td>
                            <td>{{ $slot->capacity }}</td>
                            <td>{{ $slot->booked_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>

    <div class="card" style="margin-top:16px;">
        <h2>Beschikbaarheidsblokken</h2>
        <table>
            <thead>
                <tr>
                    <th>Start</th>
                    <th>Einde</th>
                    <th>Slots</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($blocks as $block)
                    <tr>
                        <td>{{ $block->starts_at->setTimezone($resource->timezone)->format('d-m H:i') }}</td>
                        <td>{{ $block->ends_at->setTimezone($resource->timezone)->format('d-m H:i') }}</td>
                        <td>{{ $block->slot_length_minutes }} min / cap {{ $block->capacity }}</td>
                        <td>
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
