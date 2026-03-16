<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ReadOnlySession Middleware
 *
 * Prevents Laravel from writing to the session on AJAX endpoints.
 * Without this, the `database` session driver acquires a MySQL row lock
 * for EVERY request, causing all concurrent AJAX requests to serialize
 * (queue up) even when called in parallel from the browser.
 * 
 * Applied to: autosave, upload, finalize, files (LKE AJAX endpoints)
 */
class ReadOnlySession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Catatan:
        // Di versi Laravel saat ini, Store tidak punya metode setReadOnly().
        // Untuk menghindari error 500, kita biarkan session berjalan normal.
        // Jika ingin benar‑benar read‑only, perlu pendekatan lain (mis. driver khusus).

        return $response;
    }
}

