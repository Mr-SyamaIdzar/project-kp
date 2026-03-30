<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_informasi', function (Blueprint $table) {
            $table->string('warna', 32)->default('neutral')->after('isi');
        });
    }

    public function down(): void
    {
        Schema::table('role_informasi', function (Blueprint $table) {
            $table->dropColumn('warna');
        });
    }
};

