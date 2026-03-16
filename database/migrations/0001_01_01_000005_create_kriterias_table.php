<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kriterias', function (Blueprint $table) {
            $table->integer('id', true, true)->primary();
            $table->integer('domain_id', false, true);
            $table->integer('tingkat');
            $table->string('kriteria', 500);
            $table->timestamps();
            
            // UNIQUE COMBINATION
            $table->unique(['domain_id', 'tingkat']);   
            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kriterias');
    }
};
