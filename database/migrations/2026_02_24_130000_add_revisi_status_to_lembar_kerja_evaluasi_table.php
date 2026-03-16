<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE lembar_kerja_evaluasi MODIFY COLUMN status ENUM('draft','final','revisi') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE lembar_kerja_evaluasi MODIFY COLUMN status ENUM('draft','final') NOT NULL DEFAULT 'draft'");
        }
    }
};

