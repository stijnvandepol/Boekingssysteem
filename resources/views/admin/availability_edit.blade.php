@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div class="card">
        <h2>Beschikbaarheid wijzigen</h2>
        <form method="post" action="{{ route('admin.availability.update', $block) }}">
            @csrf
            @method('PUT')

            <label for="starts_at">Start</label>
            <input type="datetime-local" name="starts_at" id="starts_at" required value="{{ $block->starts_at->setTimezone($resource->timezone)->format('Y-m-d\\TH:i') }}">

            <label for="ends_at">Einde</label>
            <input type="datetime-local" name="ends_at" id="ends_at" required value="{{ $block->ends_at->setTimezone($resource->timezone)->format('Y-m-d\\TH:i') }}">

            <label for="slot_length_minutes">Slotlengte (min)</label>
            <select name="slot_length_minutes" id="slot_length_minutes" required>
                @foreach (config('booking.allowed_slot_lengths') as $length)
                    <option value="{{ $length }}" @selected($length === $block->slot_length_minutes)>{{ $length }}</option>
                @endforeach
            </select>

            <label for="capacity">Capaciteit</label>
            <input type="number" min="1" max="50" name="capacity" id="capacity" value="{{ $block->capacity }}" required>

            <div class="inline" style="margin-top:12px;">
                <button type="submit">Opslaan</button>
                <a class="button" href="{{ route('admin.dashboard') }}">Annuleren</a>
            </div>
        </form>
    </div>
@endsection
