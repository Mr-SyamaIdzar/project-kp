<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        $throttleKey = 'login-lock|' . $request->ip();
        $isLocked = RateLimiter::tooManyAttempts($throttleKey, 3);
        $seconds = $isLocked ? RateLimiter::availableIn($throttleKey) : 0;

        $timeout = (bool) $request->query('timeout');

        // bikin captcha baru tiap buka halaman login
        $this->generateCaptcha($request);

        return view('auth.login', compact('isLocked', 'seconds', 'timeout'));
    }

    private function generateCaptcha(Request $request): void
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);

        $request->session()->put('captcha_question', "{$a} + {$b}");
        $request->session()->put('captcha_answer', $a + $b);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required','string','max:60'],
            'password' => ['required','string','min:8'],
            'captcha'  => ['required','numeric'],
        ], [
            'captcha.required' => 'Captcha wajib diisi.',
            'captcha.numeric'  => 'Captcha harus angka.',
        ]);

        $ipKey = 'login-lock|' . $request->ip();
        $userKey = Str::lower($request->username) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 3) || RateLimiter::tooManyAttempts($userKey, 3)) {
            $seconds = max(RateLimiter::availableIn($ipKey), RateLimiter::availableIn($userKey));
            $minutes = ceil($seconds / 60);

            return back()
                ->with('failed', "Terlalu banyak percobaan login. Silakan tunggu {$minutes} menit.")
                ->with('lockout_seconds', $seconds)
                ->withInput($request->only('username'));
        }

        // cek captcha dari session
        $answer = (int) $request->session()->get('captcha_answer', -1);
        if ((int)$request->captcha !== $answer) {
            RateLimiter::hit($ipKey, 300);
            RateLimiter::hit($userKey, 300);
            
            // refresh captcha biar nggak bisa ditebak ulang
            $this->generateCaptcha($request);

            return back()
                ->with('failed', 'Captcha salah, coba lagi.')
                ->withInput($request->only('username'));
        }

        // captcha benar -> lanjut login
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

        // kalau password salah, hit limiter
        RateLimiter::hit($ipKey, 300);
        RateLimiter::hit($userKey, 300);

        // kalau password salah, generate captcha baru juga biar aman
        $this->generateCaptcha($request);

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
