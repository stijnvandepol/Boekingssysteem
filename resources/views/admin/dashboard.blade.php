@php use Carbon\Carbon; @endphp
@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div class="grid grid-2">
        <div class="card" id="availability-form">
            <h2>📅 Beschikbaarheid toevoegen</h2>
            <div class="muted" style="margin-bottom: 16px; font-size: 0.9rem;">Selecteer blokken in het weekrooster of vul handmatig in.</div>
            <form method="post" action="{{ route('admin.availability.store') }}">
                @csrf
                <input type="hidden" name="resource_id" value="{{ $resource->id }}">
                <input type="hidden" name="ranges" id="ranges" value="">

                <label for="starts_at">Startdatum en tijd</label>
                <input type="datetime-local" name="starts_at" id="starts_at" required value="{{ $defaultStart->format('Y-m-d\\TH:i') }}">

                <label for="ends_at">Einddatum en tijd</label>
                <input type="datetime-local" name="ends_at" id="ends_at" required value="{{ $defaultEnd->format('Y-m-d\\TH:i') }}">

                <label for="slot_length_minutes">Slotlengte (minuten)</label>
                <select name="slot_length_minutes" id="slot_length_minutes" required>
                    @foreach (config('booking.allowed_slot_lengths') as $length)
                        <option value="{{ $length }}" @selected($length === $resource->default_slot_length_minutes)>{{ $length }} min</option>
                    @endforeach
                </select>

                <label for="capacity">Capaciteit</label>
                <input type="number" min="1" max="50" name="capacity" id="capacity" value="{{ $resource->default_capacity }}" required>

                <button type="submit">➕ Voeg toe</button>
            </form>
        </div>

        <div class="card">
            <h2>⚙️ Instellingen</h2>
            <div class="muted">Beheer globale instellingen en brontypes in het aparte instellingenmenu.</div>
            <a class="button" style="margin-top: 20px; display: inline-block;" href="{{ route('admin.settings') }}">→ Naar instellingen</a>
        </div>
    </div>

    <div class="card" style="margin-top: 24px;">
        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
            <div>
                <h2 style="margin-bottom: 8px;">📋 Weekrooster ({{ $weekStart->format('d-m-Y') }})</h2>
                <div class="muted">Klik op een uurblok om beschikbaarheid in te vullen.</div>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a class="button" href="{{ route('admin.dashboard', ['week' => $weekOffset - 1]) }}" style="padding: 10px 12px; font-size: 0.95rem;">← Vorig</a>
                <a class="button" href="{{ route('admin.dashboard') }}" style="padding: 10px 12px; font-size: 0.95rem;">Vandaag</a>
                <a class="button" href="{{ route('admin.dashboard', ['week' => $weekOffset + 1]) }}" style="padding: 10px 12px; font-size: 0.95rem;">Volgende →</a>
            </div>
        </div>

        <div style="overflow-x: auto; margin-top: 16px; border-radius: 8px;">
            <div style="display: grid; grid-template-columns: 70px repeat(7, 1fr); min-width: 100%; border: 1px solid var(--border-light); border-radius: 8px; overflow: hidden;">
                <div style="background: rgba(5, 15, 31, 0.02); font-weight: 600; color: var(--ink); padding: 12px 8px; border-right: 1px solid var(--border-light); font-size: 0.85rem;">Tijd</div>
                @foreach ($weekDays as $day)
                    <div style="background: rgba(5, 15, 31, 0.02); font-weight: 600; color: var(--ink); padding: 12px 8px; border-right: 1px solid var(--border-light); text-align: center; font-size: 0.85rem;">{{ $day->locale('nl')->isoFormat('ddd D/M') }}</div>
                @endforeach
                
                @foreach ($calendarHours as $hour)
                    <div style="background: rgba(5, 15, 31, 0.02); font-weight: 600; color: var(--ink); padding: 10px 8px; border-right: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light); font-size: 0.85rem; text-align: center;">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                    @foreach ($weekDays as $day)
                        @php
                            $dayKey = $day->toDateString();
                            $dayBlocks = $blocksByDate->get($dayKey, collect());
                            $cellStart = $day->copy()->setTime($hour, 0);
                            $cellEnd = $cellStart->copy()->addHour();
                            $block = $dayBlocks->first(function ($b) use ($cellStart, $cellEnd, $resource) {
                                $start = $b->starts_at->setTimezone($resource->timezone);
                                $end = $b->ends_at->setTimezone($resource->timezone);
                                return $start->lt($cellEnd) && $end->gt($cellStart);
                            });
                        @endphp
                        <div
                            style="border-right: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light); min-height: 50px; padding: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; text-align: center; background: #fff; cursor: pointer; transition: all 0.2s ease; @if($block) background: rgba(37, 99, 235, 0.08); color: var(--accent); font-weight: 600; @else opacity: 0.6; @endif"
                            role="button"
                            tabindex="0"
                            data-start="{{ $cellStart->format('Y-m-d\\TH:i') }}"
                            data-end="{{ $cellEnd->format('Y-m-d\\TH:i') }}"
                            data-ts="{{ $cellStart->timestamp }}"
                            data-busy="{{ $block ? 1 : 0 }}"
                        >
                            @if ($block)
                                ✓ Beschikbaar
                            @else
                                +
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; align-items: center;">
            <div class="muted" id="selection-summary" style="flex: 1; min-width: 150px;">Geen selectie.</div>
            <button type="button" id="apply-selection" style="flex: 0; min-width: 120px; padding: 10px 14px;">Voeg toe</button>
            <button type="button" id="clear-selection" class="button" style="flex: 0; min-width: 100px;">Wissen</button>
        </div>

        <div style="margin-top: 24px;">
            <h2>📊 Beschikbaarheidsblokken</h2>
            @if ($blocks->isEmpty())
                <div class="muted" style="padding: 20px; text-align: center; background: rgba(5, 15, 31, 0.02); border-radius: 8px; margin-top: 12px;">Nog geen beschikbaarheid toegevoegd.</div>
            @else
                <table style="margin-top: 12px;">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Begintijd</th>
                            <th>Eindtijd</th>
                            <th>Slotlengte</th>
                            <th>Capaciteit</th>
                            <th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($blocks as $block)
                            <tr>
                                <td><strong>{{ $block->starts_at->setTimezone($resource->timezone)->format('d-m-Y') }}</strong></td>
                                <td>{{ $block->starts_at->setTimezone($resource->timezone)->format('H:i') }}</td>
                                <td>{{ $block->ends_at->setTimezone($resource->timezone)->format('H:i') }}</td>
                                <td><span class="badge muted">{{ $block->slot_length_minutes }} min</span></td>
                                <td><span class="badge success">{{ $block->capacity }} plaatsen</span></td>
                                <td style="display: flex; gap: 8px; align-items: center;">
                                    <a class="button" href="{{ route('admin.availability.edit', $block) }}" style="padding: 8px 12px; font-size: 0.9rem;">✏️ Bewerk</a>
                                    <form method="post" action="{{ route('admin.availability.destroy', $block) }}" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="padding: 8px 12px; font-size: 0.9rem;">🗑️ Verwijder</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <script>
        const cells = Array.from(document.querySelectorAll('[data-start][data-end][data-ts]'));
        const selected = new Set();
        const summary = document.getElementById('selection-summary');
        const applyButton = document.getElementById('apply-selection');
        const clearButton = document.getElementById('clear-selection');
        const rangesInput = document.getElementById('ranges');
        const startInput = document.getElementById('starts_at');
        const endInput = document.getElementById('ends_at');
        const form = document.querySelector('#availability-form form');

        const updateSummary = () => {
            if (selected.size === 0) {
                summary.textContent = 'Geen selectie.';
                return;
            }
            const list = Array.from(selected).map((el) => ({
                start: el.dataset.start,
                end: el.dataset.end,
                ts: Number(el.dataset.ts),
            })).sort((a, b) => a.ts - b.ts);

            const ranges = [];
            let current = { start: list[0].start, end: list[0].end };
            for (let i = 1; i < list.length; i += 1) {
                if (list[i - 1].end === list[i].start) {
                    current.end = list[i].end;
                } else {
                    ranges.push(current);
                    current = { start: list[i].start, end: list[i].end };
                }
            }
            ranges.push(current);

            summary.textContent = `Geselecteerd: ${ranges.length} blok(ken), ${list.length} uurblokken`;
        };

        const toggleCell = (cell, forceAdd = null) => {
            if (cell.dataset.busy === '1') {
                return;
            }
            const isSelected = selected.has(cell);
            const shouldAdd = forceAdd === null ? !isSelected : forceAdd;
            if (shouldAdd) {
                selected.add(cell);
                cell.style.background = 'rgba(37, 99, 235, 0.15)';
                cell.style.borderColor = 'var(--accent)';
                cell.style.color = 'var(--accent)';
                cell.style.fontWeight = '600';
            } else {
                selected.delete(cell);
                cell.style.background = '#fff';
                cell.style.borderColor = 'var(--border-light)';
                cell.style.opacity = '0.6';
                cell.style.color = 'inherit';
                cell.style.fontWeight = '400';
            }
            updateSummary();
        };

        let dragMode = null;
        const onPointerDown = (event) => {
            if (event.button && event.button !== 0) {
                return;
            }
            dragMode = !selected.has(event.currentTarget);
            toggleCell(event.currentTarget, dragMode);
        };
        const onPointerEnter = (event) => {
            if (dragMode === null) {
                return;
            }
            toggleCell(event.currentTarget, dragMode);
        };
        const onPointerUp = () => {
            dragMode = null;
        };

        cells.forEach((cell) => {
            cell.addEventListener('pointerdown', onPointerDown);
            cell.addEventListener('pointerenter', onPointerEnter);
        });
        window.addEventListener('pointerup', onPointerUp);

        applyButton.addEventListener('click', () => {
            if (selected.size === 0) {
                alert('Selecteer eerst een of meer blokken.');
                return;
            }
            const list = Array.from(selected).map((el) => ({
                start: el.dataset.start,
                end: el.dataset.end,
                ts: Number(el.dataset.ts),
            })).sort((a, b) => a.ts - b.ts);

            const ranges = [];
            let current = { start: list[0].start, end: list[0].end };
            for (let i = 1; i < list.length; i += 1) {
                if (list[i - 1].end === list[i].start) {
                    current.end = list[i].end;
                } else {
                    ranges.push(current);
                    current = { start: list[i].start, end: list[i].end };
                }
            }
            ranges.push(current);

            if (!rangesInput || !startInput || !endInput || !form) {
                return;
            }
            rangesInput.value = JSON.stringify(ranges);
            startInput.value = ranges[0].start;
            endInput.value = ranges[0].end;
            form.submit();
        });

        clearButton.addEventListener('click', () => {
            selected.forEach((cell) => {
                cell.style.background = '#fff';
                cell.style.opacity = '0.6';
                cell.style.color = 'inherit';
                cell.style.fontWeight = '400';
            });
            selected.clear();
            updateSummary();
            if (rangesInput) {
                rangesInput.value = '';
            }
        });

        [startInput, endInput].forEach((input) => {
            if (!input) return;
            input.addEventListener('change', () => {
                if (rangesInput) {
                    rangesInput.value = '';
                }
            });
        });
    </script>

    <div class="card" style="margin-top: 24px;">
        <h2>📝 Recente boekingen</h2>
        @if($recentBookings->count() > 0)
            <table style="margin-top: 12px;">
                <thead>
                    <tr>
                        <th>Geboekt op</th>
                        <th>Slottijd</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentBookings as $booking)
                        <tr>
                            <td>{{ $booking->booked_at->format('d-m H:i') }}</td>
                            <td>
                                @if($booking->slotInstance?->starts_at)
                                    {{ $booking->slotInstance->starts_at->setTimezone($resource->timezone)->format('d-m H:i') }}
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="muted" style="padding: 20px; text-align: center; background: rgba(5, 15, 31, 0.02); border-radius: 8px; margin-top: 12px;">Nog geen boekingen.</div>
        @endif
    </div>
@endsection
