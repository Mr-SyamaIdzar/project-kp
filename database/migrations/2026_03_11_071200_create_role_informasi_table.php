<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_informasi', function (Blueprint $table) {
            $table->id();
            $table->string('role')->unique(); // admin, opd, bps
            $table->string('judul')->default('Informasi');
            $table->text('isi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_informasi');
    }
};
