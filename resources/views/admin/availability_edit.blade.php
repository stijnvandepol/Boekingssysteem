@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div style="max-width:600px;">
        <div class="card">
            <h2>Beschikbaarheid wijzigen</h2>
            <form method="post" action="{{ route('admin.availability.update', $block) }}">
                @csrf
                @method('PUT')

                <div style="display:grid; gap:16px;">
                    <div>
                        <label for="starts_at">Startdatum en -tijd</label>
                        <input type="datetime-local" name="starts_at" id="starts_at" required value="{{ $block->starts_at->setTimezone($resource->timezone)->format('Y-m-d\\TH:i') }}">
                    </div>

                    <div>
                        <label for="ends_at">Einddatum en -tijd</label>
                        <input type="datetime-local" name="ends_at" id="ends_at" required value="{{ $block->ends_at->setTimezone($resource->timezone)->format('Y-m-d\\TH:i') }}">
                    </div>

                    <div>
                        <label for="slot_length_minutes">Slotlengte (minuten)</label>
                        <select name="slot_length_minutes" id="slot_length_minutes" required>
                            @foreach (config('booking.allowed_slot_lengths') as $length)
                                <option value="{{ $length }}" @selected($length === $block->slot_length_minutes)>{{ $length }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="capacity">Capaciteit</label>
                        <input type="number" min="1" max="50" name="capacity" id="capacity" value="{{ $block->capacity }}" required>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:20px;">
                    <button type="submit" style="flex:1;">Opslaan</button>
                    <a class="button" href="{{ route('admin.dashboard') }}" style="flex:1; text-align:center; padding:10px 12px;">Annuleren</a>
                </div>
            </form>
        </div>
    </div>
@endsection
