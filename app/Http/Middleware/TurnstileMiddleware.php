<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TurnstileMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.turnstile.enabled')) {
            return $next($request);
        }

        $token = $request->input('turnstile_token');
        if (! $token) {
            throw ValidationException::withMessages([
                'turnstile_token' => 'Bot-bescherming ontbreekt.',
            ]);
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret_key'),
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (! $response->ok() || ! ($response->json('success') === true)) {
            throw ValidationException::withMessages([
                'turnstile_token' => 'Bot-bescherming mislukt, probeer opnieuw.',
            ]);
        }

        return $next($request);
    }
}
