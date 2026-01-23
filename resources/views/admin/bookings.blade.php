@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div class="card">
        <h2>Boekingen</h2>
        <form method="get" class="grid grid-2" style="margin-bottom:12px;">
            <div>
                <label for="date">Datum</label>
                <input type="date" name="date" id="date" value="{{ request('date') }}">
            </div>
            <div>
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">Alle</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>Bevestigd</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Geannuleerd</option>
                </select>
            </div>
            <div>
                <button type="submit">Filter</button>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Boekingsdatum</th>
                    <th>Slot</th>
                    <th>Gast</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->booked_at->format('d-m-Y H:i') }}</td>
                        <td>{{ $booking->slotInstance?->starts_at?->setTimezone($resource->timezone)->format('d-m H:i') }}</td>
                        <td>{{ $booking->guests->first()?->name }} ({{ $booking->guests->first()?->email }})</td>
                        <td>{{ $booking->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">Geen boekingen gevonden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $bookings->links() }}
    </div>
@endsection
