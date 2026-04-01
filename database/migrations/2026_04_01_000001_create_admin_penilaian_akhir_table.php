<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_penilaian_akhir', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // OPD yang dinilai
            $table->year('tahun');                  // tahun penilaian
            $table->decimal('nilai_akhir', 3, 2);   // 1.00 – 5.00 (step 0.01)
            $table->text('catatan')->nullable();
            $table->string('file')->nullable();         // path storage
            $table->string('original_name')->nullable(); // nama file asli
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'tahun']); // 1 penilaian per OPD per tahun
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_penilaian_akhir');
    }
};
