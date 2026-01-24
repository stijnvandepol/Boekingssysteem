@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div class="card">
        <h2>🗓️ Alle boekingen</h2>
        <form method="get" style="display: grid; gap: 12px; grid-template-columns: 1fr 1fr auto; margin-bottom: 20px; align-items: flex-end;">
            <div>
                <label for="date">Filterdatum</label>
                <input type="date" name="date" id="date" value="{{ request('date') }}">
            </div>
            <div>
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">📋 Alles</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>✓ Bevestigd</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>✕ Geannuleerd</option>
                </select>
            </div>
            <div>
                <button type="submit" style="padding: 12px 16px;">🔍 Zoeken</button>
            </div>
        </form>

<<<<<<< HEAD
        <div style="overflow-x: auto; border-radius: 8px; border: 1px solid var(--border-light);">
            <table style="margin: 0;">
                <thead>
                    <tr>
                        <th>Geboekt op</th>
                        <th>Slottijd</th>
                        <th>Duur</th>
                        <th>Gast</th>
                        <th>Status</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td><strong>{{ $booking->booked_at->format('d-m-Y H:i') }}</strong></td>
                            <td>{{ $booking->slotInstance?->starts_at?->setTimezone($resource->timezone)->format('d-m H:i') }}</td>
                            <td><span class="badge muted">{{ $booking->duration_minutes ?? $booking->slotInstance?->starts_at?->diffInMinutes($booking->slotInstance?->ends_at) }} min</span></td>
                            <td>
                                <div>
                                    <strong>{{ $booking->guests->first()?->name ?? 'Onbekend' }}</strong>
                                </div>
                                <div class="muted" style="font-size: 0.85rem; margin-top: 2px;">
                                    @if ($booking->guests->first()?->phone)
                                        {{ $booking->guests->first()?->phone }}
                                    @endif
                                    @if ($booking->guests->first()?->email)
                                        <div>{{ $booking->guests->first()?->email }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($booking->status === 'confirmed')
                                    <span class="badge success">✓ Bevestigd</span>
                                @else
                                    <span class="badge danger">✕ Geannuleerd</span>
                                @endif
                            </td>
                            <td>
                                @if ($booking->status === 'confirmed')
                                    <form method="post" action="{{ route('admin.bookings.cancel', $booking) }}" style="margin: 0;">
                                        @csrf
                                        <button type="submit" style="padding: 8px 12px; font-size: 0.9rem;" onclick="return confirm('Weet je zeker dat je deze boeking wilt annuleren?')">Annuleer</button>
                                    </form>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 32px;">
                                <div class="muted" style="font-size: 0.95rem;">
                                    <div style="margin-bottom: 4px;">Geen boekingen gevonden</div>
                                    <div style="font-size: 0.85rem;">Pas filters aan of kom later terug.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
=======
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
>>>>>>> parent of 1568204 (errors fixed)

        @if ($bookings->hasPages())
            <div style="margin-top: 20px;">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
@endsection
