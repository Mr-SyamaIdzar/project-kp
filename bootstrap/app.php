<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust all proxies (Cloudflare → Nginx → PHP) agar HTTPS terdeteksi
        $middleware->trustProxies(at: '*');

        // Tambahkan PreventBackHistory ke semua request web agar halaman
        // tidak di-cache oleh browser; mencegah tombol "back" menampilkan
        // halaman terproteksi setelah logout.
        $middleware->web(append: [
            \App\Http\Middleware\PreventBackHistory::class,
        ]);

        $middleware->alias([
            'role'               => \App\Http\Middleware\RoleMiddleware::class,
            'no-cache'           => \App\Http\Middleware\PreventBackHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /**
         * Custom handler untuk AuthenticationException (sesi berakhir atau tidak terautentikasi).
         * Jika bukan dari API (request HTTP biasa di browser), redirect user kembali ke halaman login,
         * dan kirim pesan flash (menggunakan session('failed')) sebagai feedback untuk login form.
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'))
                ->with('failed', 'Session telah berakhir karena Anda tidak aktif selama lebih dari 15 menit. Silakan login kembali.');
        });
    })->create();
