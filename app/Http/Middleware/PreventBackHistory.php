<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PreventBackHistory Middleware
 *
 * Menyetel header HTTP agar browser tidak meng-cache halaman yang terproteksi.
 * Tanpa ini, tombol "back" di browser dapat menampilkan halaman dari cache
 * meskipun user sudah logout, yang merupakan celah keamanan.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}
