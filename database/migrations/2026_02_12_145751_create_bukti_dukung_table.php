<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukti_dukung', function (Blueprint $table) {

            $table->integer('id', true, true)->primary();

            $table->integer('lembar_kerja_id')->unsigned();

            $table->string('file', 255);

            $table->timestamps();

            // Foreign Key
            $table->foreign('lembar_kerja_id')
                ->references('id')
                ->on('lembar_kerja_evaluasi')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_dukung');
    }
};
