<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Performance Indexes to LKE Tables
 *
 * PROBLEM: autosave() does an updateOrCreate() searching by:
 *   WHERE user_id=? AND tahun_id=? AND domain_id=? AND nama_kegiatan=? AND nomor_rekomendasi=? AND status='draft'
 *
 * Without a composite index, MySQL uses only the single-column user_id FK index,
 * then performs a FULL SCAN of all rows for that user. With 7 indicators × N records
 * per user, this is extremely slow and gets worse as data grows.
 *
 * INDEXES ADDED:
 *  1. lke_lookup_idx   → (user_id, tahun_id, domain_id, status)        — used by autosave updateOrCreate()
 *  2. lke_draft_idx    → (user_id, status, updated_at)                  — used by create() latest draft queries
 *  3. lke_finalize_idx → (user_id, tahun_id, status)                    — used by finalizeAll() mass update
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {

            // Index 1: Core lookup for autosave updateOrCreate()
            // Matches: WHERE user_id=? AND tahun_id=? AND domain_id=? AND status=?
            // Prefix on nama_kegiatan + nomor_rekomendasi can't be indexed efficiently (longtext/varchar255)
            // but this composite brings it from O(all_user_rows) to O(1) for the domain
            $table->index(
                ['user_id', 'tahun_id', 'domain_id', 'status'],
                'lke_lookup_idx'
            );

            // Index 2: For the create() page draft resolution queries
            // Matches: WHERE user_id=? AND status='draft' ORDER BY updated_at DESC
            $table->index(
                ['user_id', 'status', 'updated_at'],
                'lke_draft_idx'
            );

            // Index 3: For finalizeAll() mass update
            // Matches: WHERE user_id=? AND tahun_id=? (then checks status in PHP)
            $table->index(
                ['user_id', 'tahun_id', 'status'],
                'lke_finalize_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->dropIndex('lke_lookup_idx');
            $table->dropIndex('lke_draft_idx');
            $table->dropIndex('lke_finalize_idx');
        });
    }
};
