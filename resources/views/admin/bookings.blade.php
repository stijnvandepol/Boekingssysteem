@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    @php
        $bookingsByDay = $weeklyBookings->groupBy(function ($booking) use ($resource) {
            $start = $booking->slotInstance?->starts_at?->setTimezone($resource->timezone)
                ?? $booking->booked_at?->setTimezone($resource->timezone);

            return optional($start)->toDateString();
        });
    @endphp

    <div class="card" style="margin-bottom: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
            <div>
                <h2>Weekagenda</h2>
                <div class="muted">Week van {{ $weekStart->format('d M Y') }} t/m {{ $weekEnd->format('d M Y') }}</div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <a class="button" href="{{ route('admin.bookings.index', array_merge(request()->except('page', 'week'), ['week' => $weekOffset - 1])) }}" style="padding: 10px 12px;">Vorige week</a>
                <a class="button" href="{{ route('admin.bookings.index', array_merge(request()->except('page', 'week'), ['week' => 0])) }}" style="padding: 10px 12px;">Deze week</a>
                <a class="button" href="{{ route('admin.bookings.index', array_merge(request()->except('page', 'week'), ['week' => $weekOffset + 1])) }}" style="padding: 10px 12px;">Volgende week</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 16px;">
            @foreach ($weekDays as $day)
                @php
                    $dayKey = $day->toDateString();
                    $dayBookings = ($bookingsByDay[$dayKey] ?? collect())->sortBy(fn ($booking) => $booking->slotInstance?->starts_at);
                @endphp
                <div style="border: 1px solid var(--border-light); border-radius: 10px; padding: 12px; background: #fff; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                        <div style="font-weight: 700; color: var(--ink);">{{ $day->locale('nl')->isoFormat('ddd D MMM') }}</div>
                        <span class="badge muted">{{ $dayBookings->count() }}x</span>
                    </div>
                    @if ($dayBookings->isEmpty())
                        <div class="muted" style="font-size: 0.9rem;">Geen boekingen.</div>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            @foreach ($dayBookings as $booking)
                                @php
                                    $start = $booking->slotInstance?->starts_at?->setTimezone($resource->timezone);
                                    $end = $booking->slotInstance?->ends_at?->setTimezone($resource->timezone);
                                    $guest = $booking->guests->first();
                                @endphp
                                <div style="border: 1px solid var(--border-light); border-radius: 8px; padding: 10px; background: rgba(5, 15, 31, 0.02); display: grid; gap: 4px;">
                                    <div style="font-weight: 700; color: var(--ink);">
                                        {{ $start?->format('H:i') }} - {{ $end?->format('H:i') ?? '?' }}
                                    </div>
                                    <div style="font-size: 0.95rem;">{{ $guest?->name ?? 'Onbekende gast' }}</div>
                                    <div class="muted" style="font-size: 0.85rem;">
                                        @if ($guest?->phone)
                                            {{ $guest->phone }}
                                        @endif
                                        @if ($guest?->email)
                                            <div>{{ $guest->email }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        @if ($booking->status === 'confirmed')
                                            <span class="badge success">Bevestigd</span>
                                        @else
                                            <span class="badge danger">Geannuleerd</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h2>Boekingen lijst</h2>
        <form method="get" style="display: grid; gap: 12px; grid-template-columns: 1fr 1fr auto; margin-bottom: 20px; align-items: flex-end;">
            <div>
                <label for="date">Filterdatum (boekingsdatum)</label>
                <input type="date" name="date" id="date" value="{{ request('date') }}">
            </div>
            <div>
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">Alles</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>Bevestigd</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Geannuleerd</option>
                </select>
            </div>
            <div>
                <button type="submit" style="padding: 12px 16px;">Zoeken</button>
            </div>
        </form>

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
                                    <span class="badge success">Bevestigd</span>
                                @else
                                    <span class="badge danger">Geannuleerd</span>
                                @endif
                            </td>
                            <td>
                                @if ($booking->status === 'confirmed')
                                    <form method="post" action="{{ route('admin.bookings.cancel', $booking) }}" style="margin: 0;">
                                        @csrf
                                        <button type="submit" style="padding: 8px 12px; font-size: 0.9rem;" onclick="return confirm('Weet je zeker dat je deze boeking wilt annuleren?')">Annuleer</button>
                                    </form>
                                @else
                                    <span class="muted">-</span>
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

        @if ($bookings->hasPages())
            <div style="margin-top: 20px;">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
@endsection

