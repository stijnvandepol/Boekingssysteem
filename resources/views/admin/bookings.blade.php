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
                <button type="submit" style="margin-top:26px;">Filter</button>
            </div>
        </form>

        <div style="overflow-x:auto; margin:-16px -16px 0 -16px;">
            <table style="margin:0;">
                <thead>
                    <tr>
                        <th style="min-width:120px;">Boekingsdatum</th>
                        <th style="min-width:100px;">Slot</th>
                        <th style="min-width:60px;">Duur</th>
                        <th style="min-width:150px;">Gast</th>
                        <th style="min-width:80px;">Status</th>
                        <th style="min-width:100px;">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booked_at->format('d-m-Y H:i') }}</td>
                            <td>{{ $booking->slotInstance?->starts_at?->setTimezone($resource->timezone)->format('d-m H:i') }}</td>
                            <td>{{ $booking->duration_minutes ?? $booking->slotInstance?->starts_at?->diffInMinutes($booking->slotInstance?->ends_at) }} min</td>
                            <td>
                                <strong>{{ $booking->guests->first()?->name }}</strong>
                                <div class="muted" style="font-size:0.85rem;">
                                    @if ($booking->guests->first()?->phone)
                                        {{ $booking->guests->first()?->phone }}
                                    @endif
                                    @if ($booking->guests->first()?->email)
                                        <br>{{ $booking->guests->first()?->email }}
                                    @endif
                                </div>
                            </td>
                            <td><strong>{{ $booking->status }}</strong></td>
                            <td>
                                @if ($booking->status === 'confirmed')
                                    <form method="post" action="{{ route('admin.bookings.cancel', $booking) }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" style="width:auto; padding:6px 10px; font-size:0.9rem;">Annuleer</button>
                                    </form>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">Geen boekingen gevonden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $bookings->links() }}
    </div>
@endsection
