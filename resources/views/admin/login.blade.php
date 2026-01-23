@extends('layouts.admin')

@section('content')
    @include('partials.alerts')
    <div class="card">
        <h2>Admin login</h2>
        <form method="post" action="{{ route('admin.login.submit') }}">
            @csrf
            <label for="email">E‑mail</label>
            <input type="email" name="email" id="email" required value="{{ old('email') }}">

            <label for="password">Wachtwoord</label>
            <input type="password" name="password" id="password" required>

            <div class="inline" style="margin-top:12px;">
                <input type="checkbox" name="remember" id="remember" value="1" style="width:auto;">
                <label for="remember" style="margin:0;">Onthoud mij</label>
            </div>

            <button type="submit" style="margin-top:12px;">Inloggen</button>
        </form>
    </div>
@endsection
