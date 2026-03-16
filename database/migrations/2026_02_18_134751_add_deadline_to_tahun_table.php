<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('tahun', function (Blueprint $table) {
      $table->dateTime('deadline_submit')->nullable()->after('tahun');
    });
  }
  public function down(): void {
    Schema::table('tahun', function (Blueprint $table) {
      $table->dropColumn('deadline_submit');
    });
  }
};