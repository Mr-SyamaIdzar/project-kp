<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_menu_settings', function (Blueprint $table) {
            $table->integer('id')->unsigned()->autoIncrement()->primary();
            $table->integer('user_id')->unsigned()->unique();
            $table->boolean('can_fill_data_umum')->default(true);
            $table->boolean('can_fill_indikator')->default(true);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_menu_settings');
    }
};
