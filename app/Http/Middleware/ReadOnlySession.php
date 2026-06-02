<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ReadOnlySession Middleware
 *
 * Membebaskan MySQL session row lock SEBELUM handler utama dieksekusi
 * sehingga request AJAX paralel (autosave, upload, files, dll.) tidak
 * saling serialize (antri) satu sama lain.
 *
 * Cara kerja:
 * 1. Session di-save dan di-flush lebih awal sebelum handler.
 * 2. Handler berjalan tanpa session lock aktif.
 * 3. Response dikembalikan — session tidak ditulis ulang oleh Laravel
 *    karena sudah di-save dan session ID tidak berubah.
 *
 * Catatan: Middleware ini HANYA aman untuk endpoint yang tidak membutuhkan
 * penulisan data ke session (seperti endpoint AJAX read/write data).
 * Jangan terapkan pada endpoint yang butuh flash message atau token CSRF refresh.
 *
 * Applied to: autosave, upload, finalize, files (LKE AJAX endpoints)
 */
class ReadOnlySession
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        // Simpan session sekarang (sebelum handler) dan lepas lock-nya.
        // Ini membebaskan row lock MySQL agar request paralel lain bisa berjalan.
        $session->save();

        $response = $next($request);

        return $response;
    }
}


