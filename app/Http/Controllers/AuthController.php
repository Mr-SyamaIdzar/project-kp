<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile as TurnstileRule;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        $throttleKey = 'login-lock|' . $request->ip();
        $isLocked    = RateLimiter::tooManyAttempts($throttleKey, 3);
        $seconds     = $isLocked ? RateLimiter::availableIn($throttleKey) : 0;
        $timeout     = (bool) $request->query('timeout');

        return view('auth.login', compact('isLocked', 'seconds', 'timeout'));
    }

    public function login(Request $request)
    {
        // Di environment local/testing, Turnstile::fake() membuat verifikasi
        // selalu lolos tanpa butuh koneksi ke server Cloudflare.
        // Di production/staging, verifikasi dikirim ke API Cloudflare via Rule package.
        if (app()->environment(['local', 'testing'])) {
            Turnstile::fake();
        }

        $request->validate([
            'username'              => ['required', 'string', 'max:60'],
            'password'              => ['required', 'string', 'min:8'],
            'cf-turnstile-response' => ['required', new TurnstileRule()],
        ], [
            'cf-turnstile-response.required' => 'Verifikasi keamanan (Turnstile) wajib diselesaikan.',
        ]);

        $ipKey   = 'login-lock|' . $request->ip();
        $userKey = Str::lower($request->username) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 3) || RateLimiter::tooManyAttempts($userKey, 3)) {
            $seconds = max(RateLimiter::availableIn($ipKey), RateLimiter::availableIn($userKey));
            $minutes = ceil($seconds / 60);

            return back()
                ->with('failed', "Terlalu banyak percobaan login. Silakan tunggu {$minutes} menit.")
                ->with('lockout_seconds', $seconds)
                ->withInput($request->only('username'));
        }

        if (Auth::attempt(
            ['username' => $request->username, 'password' => $request->password],
            $request->boolean('remember')
        )) {
            RateLimiter::clear($ipKey);
            RateLimiter::clear($userKey);
            $request->session()->regenerate();

            return match (Auth::user()->role) {
                'admin' => redirect()->route('admin.dashboard'),
                'opd'   => redirect()->route('opd.dashboard'),
                'bps'   => redirect()->route('bps.dashboard'),
                default => redirect('/'),
            };
        }

        // Kredensial salah — hit rate limiter
        RateLimiter::hit($ipKey, 300);
        RateLimiter::hit($userKey, 300);

        return back()
            ->with('failed', 'Username atau password salah.')
            ->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }
}

