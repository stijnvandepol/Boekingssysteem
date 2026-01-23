@extends('layouts.admin')

@section('content')
    @include('partials.alerts')
    <div style="max-width:400px; margin:0 auto;">
        <div class="card">
            <h2>Admin login</h2>
            <form method="post" action="{{ route('admin.login.submit') }}">
                @csrf
                <div style="display:grid; gap:16px;">
                    <div>
                        <label for="email">E-mail adres</label>
                        <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="admin@example.com">
                    </div>

                    <div>
                        <label for="password">Wachtwoord</label>
                        <input type="password" name="password" id="password" required placeholder="••••••••">
                    </div>

                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="remember" id="remember" value="1" style="width:auto; margin:0;">
                        <label for="remember" style="margin:0; font-weight:normal;">Onthoud mij</label>
                    </div>
                </div>

                <button type="submit" style="margin-top:20px; width:100%;">Inloggen</button>
            </form>
        </div>
    </div>
@endsection
