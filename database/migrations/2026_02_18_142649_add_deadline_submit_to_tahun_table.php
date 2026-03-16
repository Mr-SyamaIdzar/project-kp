<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tahun', 'deadline_submit')) {
            Schema::table('tahun', function (Blueprint $table) {
                $table->dateTime('deadline_submit')->nullable()->after('tahun');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tahun', 'deadline_submit')) {
            Schema::table('tahun', function (Blueprint $table) {
                $table->dropColumn('deadline_submit');
            });
        }
    }
};
