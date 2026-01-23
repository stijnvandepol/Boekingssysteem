@extends('layouts.public')

@section('content')
    <style>
        .login-container {
            max-width: 420px;
            margin: 60px auto 0;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: var(--card);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(5, 15, 31, 0.06);
        }

        .login-card h2 {
            text-align: center;
            margin: 0 0 24px 0;
            font-size: 1.3rem;
            color: var(--ink);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            color: var(--ink);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #fff;
            font-family: inherit;
            font-size: 1rem;
            color: var(--ink-soft);
            transition: all 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .checkbox-group input {
            width: 16px !important;
            height: 16px;
            margin: 0;
            padding: 0;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            background: var(--accent);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        button:hover {
            background: var(--accent-hover);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
        }

        .login-footer {
            text-align: center;
            margin-top: 16px;
        }

        .demo-info {
            font-size: 0.9rem;
            color: var(--muted);
            padding: 12px;
            background: var(--bg);
            border-radius: 8px;
        }
    </style>

    <div class="login-container">
        <div class="login-card">
            <h2>🔐 Beheerder Login</h2>
            <form method="post" action="{{ route('login.submit') }}">
                @csrf
                <div class="form-group">
                    <label for="email">E-mailadres</label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="admin@example.com" autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Wachtwoord</label>
                    <input type="password" name="password" id="password" required placeholder="••••••••">
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="remember" id="remember" value="1">
                    <label for="remember">Onthoud mij</label>
                </div>

                <button type="submit">Inloggen</button>
            </form>
        </div>
        <div class="login-footer">
            <div class="demo-info">
                <strong>Demo credentials:</strong><br>
                admin@example.com / ChangeMe123!
            </div>
        </div>
    </div>
@endsection
