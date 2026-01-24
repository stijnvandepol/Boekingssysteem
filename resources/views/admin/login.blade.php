@extends('layouts.admin')

@section('content')
    @include('partials.alerts')
    <div style="max-width: 420px; margin: 60px auto 0;">
        <div class="card">
            <h2 style="text-align: center; margin-bottom: 24px;">🔐 Beheerder login</h2>
            <form method="post" action="{{ route('admin.login.submit') }}">
                @csrf
                <div>
                    <label for="email">E-mailadres</label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="admin@example.com" autofocus>
                </div>

                <div>
                    <label for="password">Wachtwoord</label>
                    <input type="password" name="password" id="password" required placeholder="••••••••">
                </div>

                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                    <input type="checkbox" name="remember" id="remember" value="1" style="width: auto; margin: 0; width: 16px; height: 16px; cursor: pointer;">
                    <label for="remember" style="margin: 0; font-weight: 500; cursor: pointer;">Onthoud mij</label>
                </div>

                <button type="submit">Inloggen</button>
            </form>
        </div>
        <div style="text-align: center; margin-top: 16px;">
            <div class="muted" style="font-size: 0.9rem;">Demo: admin@example.com / ChangeMe123!</div>
        </div>
    </div>
@endsection
