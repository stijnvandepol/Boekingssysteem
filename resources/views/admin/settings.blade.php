@extends('layouts.admin')

@section('content')
    @include('partials.alerts')

    <div style="max-width:600px;">
        <div class="card">
            <h2>Resource instellingen</h2>
            <form method="post" action="{{ route('admin.resource.update') }}">
                @csrf
                @method('PUT')
                <div style="display:grid; gap:16px;">
                    <div>
                        <label for="name">Naam</label>
                        <input type="text" name="name" id="name" value="{{ $resource->name }}" required>
                    </div>

                    <div>
                        <label for="timezone">Tijdzone</label>
                        <input type="text" name="timezone" id="timezone" value="{{ $resource->timezone }}" required>
                    </div>

                    <div>
                        <label for="default_slot_length_minutes">Standaard slotlengte (minuten)</label>
                        <select name="default_slot_length_minutes" id="default_slot_length_minutes" required>
                            @foreach (config('booking.allowed_slot_lengths') as $length)
                                <option value="{{ $length }}" @selected($length === $resource->default_slot_length_minutes)>{{ $length }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="default_capacity">Standaard capaciteit</label>
                        <input type="number" min="1" max="50" name="default_capacity" id="default_capacity" value="{{ $resource->default_capacity }}" required>
                    </div>

                    <div>
                        <label for="min_notice_minutes">Minimale boekingstijd vooraf (minuten)</label>
                        <input type="number" min="0" max="1440" name="min_notice_minutes" id="min_notice_minutes" value="{{ $resource->min_notice_minutes }}" required>
                    </div>

                    <div>
                        <label for="is_active">Actief</label>
                        <select name="is_active" id="is_active" required>
                            <option value="1" @selected($resource->is_active)>Ja</option>
                            <option value="0" @selected(! $resource->is_active)>Nee</option>
                        </select>
                    </div>
                </div>

                <button type="submit" style="margin-top:20px;">Opslaan</button>
            </form>
        </div>
    </div>
@endsection
