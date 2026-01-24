@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div style="max-width: 600px; width: 100%;">
        <div class="card">
            <h2>✏️ Beschikbaarheid aanpassen</h2>
            <form method="post" action="{{ route('admin.availability.update', $block) }}">
                @csrf
                @method('PUT')

                <label for="starts_at">Startdatum en -tijd *</label>
                <input type="datetime-local" name="starts_at" id="starts_at" required value="{{ $block->starts_at->setTimezone($resource->timezone)->format('Y-m-d\\TH:i') }}">

                <label for="ends_at">Einddatum en -tijd *</label>
                <input type="datetime-local" name="ends_at" id="ends_at" required value="{{ $block->ends_at->setTimezone($resource->timezone)->format('Y-m-d\\TH:i') }}">

                <label for="slot_length_minutes">Slotlengte (minuten) *</label>
                <select name="slot_length_minutes" id="slot_length_minutes" required>
                    @foreach (config('booking.allowed_slot_lengths') as $length)
                        <option value="{{ $length }}" @selected($length === $block->slot_length_minutes)>{{ $length }} min</option>
                    @endforeach
                </select>

                <label for="capacity">Capaciteit *</label>
                <input type="number" min="1" max="50" name="capacity" id="capacity" value="{{ $block->capacity }}" required placeholder="Aantal plaatsen">

                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" style="flex: 1;">✓ Opslaan</button>
                    <a class="button" href="{{ route('admin.dashboard') }}" style="flex: 1; text-align: center; padding: 12px 16px;">← Terug</a>
                </div>
            </form>
        </div>
    </div>
@endsection
