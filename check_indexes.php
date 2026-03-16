<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== lembar_kerja_evaluasi INDEXES ===\n";
$indexes = DB::select('SHOW INDEX FROM lembar_kerja_evaluasi');
foreach($indexes as $i) {
    printf("  %-30s [%d] => %s\n", $i->Key_name, $i->Seq_in_index, $i->Column_name);
}

echo "\n=== bukti_dukung INDEXES ===\n";
$indexes2 = DB::select('SHOW INDEX FROM bukti_dukung');
foreach($indexes2 as $i) {
    printf("  %-30s [%d] => %s\n", $i->Key_name, $i->Seq_in_index, $i->Column_name);
}

echo "\n=== EXPLAIN autosave lookup query ===\n";
$explain = DB::select("EXPLAIN SELECT * FROM lembar_kerja_evaluasi WHERE user_id=1 AND tahun_id=1 AND domain_id=1 AND status='draft'");
foreach($explain as $e) {
    printf("  type=%-8s key=%-30s rows=%s\n", $e->type ?? '-', $e->key ?? 'NULL (FULL SCAN!)', $e->rows ?? '-');
}
