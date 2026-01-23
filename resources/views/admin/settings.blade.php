@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div style="max-width: 600px;">
        <div class="card">
            <h2>⚙️ Resource instellingen</h2>
            <form method="post" action="{{ route('admin.resource.update') }}">
                @csrf
                @method('PUT')

                <label for="name">Naam *</label>
                <input type="text" name="name" id="name" value="{{ $resource->name }}" required placeholder="bijv. Kantoor, Vergaderruimte">

                <label for="timezone">Tijdzone *</label>
                <input type="text" name="timezone" id="timezone" value="{{ $resource->timezone }}" required placeholder="bijv. Europe/Amsterdam">

                <label for="default_slot_length_minutes">Standaard slotlengte (minuten) *</label>
                <select name="default_slot_length_minutes" id="default_slot_length_minutes" required>
                    @foreach (config('booking.allowed_slot_lengths') as $length)
                        <option value="{{ $length }}" @selected($length === $resource->default_slot_length_minutes)>{{ $length }} min</option>
                    @endforeach
                </select>

                <label for="default_capacity">Standaard capaciteit *</label>
                <input type="number" min="1" max="50" name="default_capacity" id="default_capacity" value="{{ $resource->default_capacity }}" required placeholder="Aantal plaatsen">

                <label for="min_notice_hours">Minimale boekingstijd vooraf (uren) *</label>
                <input type="number" min="0" max="24" step="0.25" name="min_notice_hours" id="min_notice_hours" value="{{ $resource->min_notice_minutes / 60 }}" required placeholder="bijv. 2">

                <label for="is_active">Status *</label>
                <select name="is_active" id="is_active" required>
                    <option value="1" @selected($resource->is_active)>✓ Actief</option>
                    <option value="0" @selected(! $resource->is_active)>✕ Inactief</option>
                </select>

                <button type="submit" style="margin-top: 24px;">💾 Opslaan</button>
            </form>
        </div>
    </div>
@endsection
