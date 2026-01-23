@php use Carbon\Carbon; @endphp
@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div class="grid grid-2">
        <div class="card" id="availability-form">
            <h2>Beschikbaarheid toevoegen</h2>
            <div class="muted" style="margin-bottom:8px;">Selecteer blokken in het weekrooster of vul handmatig in.</div>
            <form method="post" action="{{ route('admin.availability.store') }}">
                @csrf
                <input type="hidden" name="resource_id" value="{{ $resource->id }}">
                <input type="hidden" name="ranges" id="ranges" value="">

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
            <h2>Instellingen</h2>
            <div class="muted">Beheer globale instellingen in het aparte instellingenmenu.</div>
            <a class="button" style="margin-top:12px; display:inline-block;" href="{{ route('admin.settings') }}">Naar instellingen</a>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div style="display:flex; flex-direction: column; gap:16px; margin-bottom:16px;">
            <div>
                <h2 style="margin-bottom:8px;">Weekrooster ({{ $weekStart->format('d-m-Y') }})</h2>
                <div class="muted">Klik op een uurblok om de beschikbaarheid alvast in te vullen.</div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; width:100%;">
                <a class="button" href="{{ route('admin.dashboard', ['week' => $weekOffset - 1]) }}" style="flex:1; min-width:100px; text-align:center;">← Vorig</a>
                <a class="button" href="{{ route('admin.dashboard') }}" style="flex:1; min-width:100px; text-align:center;">Vandaag</a>
                <a class="button" href="{{ route('admin.dashboard', ['week' => $weekOffset + 1]) }}" style="flex:1; min-width:100px; text-align:center;">Volgende →</a>
            </div>
        </div>

        <div class="calendar-scroll" style="margin-top:16px;">
            <div class="calendar">
                <div class="calendar-grid calendar-head">
                    <div class="calendar-cell calendar-time">Tijd</div>
                    @foreach ($weekDays as $day)
                        <div class="calendar-cell">{{ $day->locale('nl')->isoFormat('ddd D MMM') }}</div>
                    @endforeach
                </div>
                @foreach ($calendarHours as $hour)
                    <div class="calendar-grid">
                        <div class="calendar-cell calendar-time">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</div>
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
                                class="calendar-cell calendar-slot @if($block) calendar-busy @endif"
                                role="button"
                                tabindex="0"
                                data-start="{{ $cellStart->format('Y-m-d\\TH:i') }}"
                                data-end="{{ $cellEnd->format('Y-m-d\\TH:i') }}"
                                data-ts="{{ $cellStart->timestamp }}"
                                data-busy="{{ $block ? 1 : 0 }}"
                            >
                                @if ($block)
                                    Beschikbaar
                                @else
                                    +
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; align-items:center;">
            <div class="muted" id="selection-summary" style="flex:1; min-width:150px;">Geen selectie.</div>
            <button type="button" id="apply-selection" style="flex:1; min-width:120px;">Toevoegen</button>
            <button type="button" id="clear-selection" class="button" style="flex:1; min-width:100px; width:auto;">Wissen</button>
        </div>

        <div style="margin-top:18px; overflow-x:auto;">
            <h3>Beschikbaarheidsblokken</h3>
            @if ($blocks->isEmpty())
                <div class="muted">Nog geen beschikbaarheid toegevoegd.</div>
            @else
                <table style="margin-top:8px;">
                    <thead>
                        <tr>
                            <th style="min-width:80px;">Datum</th>
                            <th style="min-width:60px;">Start</th>
                            <th style="min-width:60px;">Einde</th>
                            <th style="min-width:100px;">Slot</th>
                            <th style="min-width:150px;">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($blocks as $block)
                            <tr>
                                <td>{{ $block->starts_at->setTimezone($resource->timezone)->format('d-m-Y') }}</td>
                                <td>{{ $block->starts_at->setTimezone($resource->timezone)->format('H:i') }}</td>
                                <td>{{ $block->ends_at->setTimezone($resource->timezone)->format('H:i') }}</td>
                                <td>{{ $block->slot_length_minutes }} min / cap {{ $block->capacity }}</td>
                                <td style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    <a class="button" href="{{ route('admin.availability.edit', $block) }}" style="width:auto; padding:6px 12px;">Bewerk</a>
                                    <form method="post" action="{{ route('admin.availability.destroy', $block) }}" style="width:auto;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="width:auto; padding:6px 12px;">Verwijder</button>
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

            summary.textContent = `Geselecteerd: ${ranges.length} blok(ken), ${list.length} uurblok(ken)`;
        };

        const toggleCell = (cell, forceAdd = null) => {
            if (cell.dataset.busy === '1') {
                return;
            }
            const isSelected = selected.has(cell);
            const shouldAdd = forceAdd === null ? !isSelected : forceAdd;
            if (shouldAdd) {
                selected.add(cell);
                cell.classList.add('calendar-selected');
            } else {
                selected.delete(cell);
                cell.classList.remove('calendar-selected');
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
            selected.forEach((cell) => cell.classList.remove('calendar-selected'));
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
