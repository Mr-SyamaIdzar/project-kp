<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LembarKerjaEvaluasi;
use App\Models\Indikator;
use App\Models\LkeRevisiRequest;
use App\Models\Tahun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class LembarKerjaEvaluasiController extends Controller
{
    public function index(Request $request)
    {
        $tahunId = (int) $request->get('tahun_id', 0);
        $userId  = (int) $request->get('user_id', 0);
        $namaKegiatan = trim((string) $request->get('nama_kegiatan', ''));
        $nomorRekomendasi = trim((string) $request->get('nomor_rekomendasi', ''));
        $exportYear = (int) $request->get('export_year', 0);

        $tahuns = Tahun::orderByDesc('tahun')->get();
        $opds   = User::where('role','opd')->orderBy('nama')->get();
        $exportYears = LembarKerjaEvaluasi::query()
            ->whereIn('status', ['final', 'revisi'])
            ->selectRaw('DISTINCT YEAR(created_at) as y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 0)
            ->unique()
            ->sortDesc()
            ->values();

        /**
         * Karena tabel LKE per-indikator, index admin kita buat “grouped list”
         * berdasarkan (user_id, tahun_id, nama_kegiatan, nomor_rekomendasi)
         * agar tampil seperti 1 baris per paket LKE OPD.
         */
        $rows = LembarKerjaEvaluasi::query()
            ->selectRaw("
                user_id,
                tahun_id,
                nama_kegiatan,
                nomor_rekomendasi,
                MAX(updated_at) as last_update,
                COUNT(DISTINCT CASE WHEN status='final' THEN domain_id END) as cnt_final,
                COUNT(DISTINCT CASE WHEN status='draft' THEN domain_id END) as cnt_draft,
                COUNT(DISTINCT domain_id) as cnt_total
            ")
            ->when($tahunId > 0, fn($q) => $q->where('tahun_id', $tahunId))
            ->when($userId > 0, fn($q) => $q->where('user_id', $userId))
            ->when($exportYear > 0, fn($q) => $q->whereYear('created_at', $exportYear))
            ->when($namaKegiatan !== '', fn($q) => $q->where('nama_kegiatan', 'like', '%' . $namaKegiatan . '%'))
            ->when($nomorRekomendasi !== '', fn($q) => $q->where('nomor_rekomendasi', 'like', '%' . $nomorRekomendasi . '%'))
            ->groupBy('user_id','tahun_id','nama_kegiatan','nomor_rekomendasi')
            ->orderByDesc('last_update')
            ->paginate(10)
            ->withQueryString();

        // preload user + tahun biar gampang di blade
        $userMap  = $rows->pluck('user_id')->unique()->filter()->isNotEmpty()
            ? User::whereIn('id', $rows->pluck('user_id')->unique())->get()->keyBy('id')
            : collect();

        $tahunMap = $rows->pluck('tahun_id')->unique()->filter()->isNotEmpty()
            ? Tahun::whereIn('id', $rows->pluck('tahun_id')->unique())->get()->keyBy('id')
            : collect();

        return view('admin.lke.index', compact(
            'rows','tahuns','opds','tahunId','userId','namaKegiatan','nomorRekomendasi','userMap','tahunMap',
            'exportYear', 'exportYears'
        ));
    }

    /**
     * Show per “paket” LKE:
     * parameter diambil dari query: user_id, tahun_id, nama_kegiatan, nomor_rekomendasi
     */
    public function show(Request $request)
    {
        $userId = (int) $request->get('user_id', 0);
        $tahunId = (int) $request->get('tahun_id', 0);
        $namaKegiatan = (string) $request->get('nama_kegiatan', '');
        $nomorRek = (string) $request->get('nomor_rekomendasi', '');

        abort_if($userId <= 0 || $tahunId <= 0 || $namaKegiatan === '' || $nomorRek === '', 404);

        $user  = User::findOrFail($userId);
        $tahun = Tahun::findOrFail($tahunId);

        // Ambil semua indikator LKE untuk paket ini
        $rawItems = LembarKerjaEvaluasi::query()
            ->with([
                'domain.kriterias',
                'kriteria',
                'buktiDukung'
            ])
            ->where('user_id', $userId)
            ->where('tahun_id', $tahunId)
            ->where('nama_kegiatan', $namaKegiatan)
            ->where('nomor_rekomendasi', $nomorRek)
            ->orderByDesc('id')
            ->get();

        $items = $rawItems
            ->unique('domain_id')
            ->keyBy('domain_id');

        $beforeRevisiItems = $rawItems
            ->filter(fn ($row) => (string) $row->status !== 'revisi')
            ->unique('domain_id')
            ->keyBy('domain_id');

        // semua domain agar urut & tampil walau belum ada row LKE
        $domains = Indikator::with(['kriterias' => function($q){
                $q->orderBy('tingkat');
            }])
            ->orderBy('kode')
            ->get();

        $revisedRequestMap = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('lke_revisi_requests')) {
            $revisedRequestMap = \App\Models\LkeRevisiRequest::query()
                ->with(['revisedLke.buktiDukung'])
                ->where('user_id', $userId)
                ->where('tahun_id', $tahunId)
                ->where('nama_kegiatan', $namaKegiatan)
                ->where('nomor_rekomendasi', $nomorRek)
                ->where('status', 'revised')
                ->get()
                ->keyBy('domain_id');
        }

        $domainRecordsMap = $rawItems->groupBy('domain_id');

        return view('admin.lke.show', compact('user','tahun','domains','items','beforeRevisiItems','domainRecordsMap','revisedRequestMap','namaKegiatan','nomorRek'));
    }

    public function exportExcel(Request $request)
    {
        /**
         * Export Excel monitoring LKE (Admin/BPS export).
         *
         * Format export mengikuti kebutuhan stakeholder:
         * - Penjelasan menampilkan histori: Sebelum Revisi / Revisi 1 / Revisi 2 (jika ada)
         * - Catatan BPS menampilkan: Catatan Evaluasi + Alasan Revisi 1 + Alasan Revisi 2 (jika ada)
         * - Nilai final BPS diambil dari input BPS paling akhir (berdasarkan updated_at) per indikator
         *
         * Catatan implementasi:
         * - Karena histori revisi disimpan sebagai record baru (status='revisi'), export perlu mengelompokkan
         *   full history per paket+domain (`$byPackageDomain`) untuk menyusun 3 panel teks.
         * - Alasan revisi per ronde dibaca dari `lke_revisi_requests.catatan` (mapping domain_id + round).
         */
        $tahunId = (int) $request->get('tahun_id', 0);
        $userId  = (int) $request->get('user_id', 0);
        $namaKegiatan = trim((string) $request->get('nama_kegiatan', ''));
        $nomorRekomendasi = trim((string) $request->get('nomor_rekomendasi', ''));
        $exportYear = (int) $request->get('export_year', 0);

        $query = LembarKerjaEvaluasi::query()
            ->with(['user:id,nama,username', 'tahun:id,tahun', 'domain:id,kode'])
            ->whereIn('status', ['final', 'revisi'])
            ->when($tahunId > 0, fn($q) => $q->where('tahun_id', $tahunId))
            ->when($userId > 0, fn($q) => $q->where('user_id', $userId))
            ->when($namaKegiatan !== '', fn($q) => $q->where('nama_kegiatan', 'like', '%' . $namaKegiatan . '%'))
            ->when($nomorRekomendasi !== '', fn($q) => $q->where('nomor_rekomendasi', 'like', '%' . $nomorRekomendasi . '%'));

        if ($exportYear > 0) {
            $query->whereYear('created_at', $exportYear);
        }

        $items = $query
            ->orderBy('user_id')
            ->orderBy('tahun_id')
            ->orderBy('nama_kegiatan')
            ->orderBy('nomor_rekomendasi')
            ->orderBy('domain_id')
            ->get();

        // Group full history per paket+domain (dibutuhkan untuk penjelasan sebelum/rev1/rev2 + nilai final BPS terakhir).
        $byPackageDomain = $items->groupBy(function ($row) {
            return $row->user_id.'|'.$row->tahun_id.'|'.$row->nama_kegiatan.'|'.$row->nomor_rekomendasi.'|'.$row->domain_id;
        });

        // Urutan indikator untuk kolom dinamis.
        $domainOrder = $items
            ->map(function ($row) {
                return [
                    'id' => (int) $row->domain_id,
                    'kode' => (string) ($row->domain->kode ?? ''),
                ];
            })
            ->unique('id')
            ->sortBy('kode')
            ->values();

        $packages = $items
            ->groupBy(function ($row) {
                return $row->user_id.'|'.$row->tahun_id.'|'.$row->nama_kegiatan.'|'.$row->nomor_rekomendasi;
            })
            ->map(function ($group) use ($byPackageDomain) {
                $first = $group->sortByDesc('id')->first();
                $keyBase = $first->user_id.'|'.$first->tahun_id.'|'.$first->nama_kegiatan.'|'.$first->nomor_rekomendasi;

                $domainGroups = collect();
                foreach ($group->pluck('domain_id')->unique() as $domainId) {
                    $k = $keyBase.'|'.$domainId;
                    if (isset($byPackageDomain[$k])) {
                        $domainGroups[(int) $domainId] = $byPackageDomain[$k];
                    }
                }

                return [
                    'user_id' => (int) $first->user_id,
                    'opd_name' => (string) ($first->user->nama ?? $first->user->username ?? '-'),
                    'tahun' => (string) ($first->tahun->tahun ?? '-'),
                    'nama_kegiatan' => (string) $first->nama_kegiatan,
                    'nomor_rekomendasi' => (string) $first->nomor_rekomendasi,
                    'tahun_id' => (int) $first->tahun_id,
                    'domain_groups' => $domainGroups, // domain_id => Collection<LKE history>
                ];
            })
            ->sortBy([
                ['user_id', 'asc'],
                ['tahun', 'asc'],
                ['nama_kegiatan', 'asc'],
                ['nomor_rekomendasi', 'asc'],
            ])
            ->values();

        // Map alasan revisi per paket+domain+round untuk kebutuhan XLSX
        $alasanMap = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('lke_revisi_requests')) {
            $reqs = LkeRevisiRequest::query()
                ->whereIn('user_id', $items->pluck('user_id')->unique())
                ->whereIn('tahun_id', $items->pluck('tahun_id')->unique())
                ->whereIn('domain_id', $items->pluck('domain_id')->unique())
                ->get();

            $alasanMap = $reqs
                ->groupBy(function ($r) {
                    return $r->user_id.'|'.$r->tahun_id.'|'.$r->nama_kegiatan.'|'.$r->nomor_rekomendasi.'|'.$r->domain_id.'|'.$r->round;
                })
                ->map(function ($g) {
                    $last = $g->sortByDesc('id')->first();
                    return trim((string) ($last->catatan ?? ''));
                });
        }

        $headers = [
            'Nama Perangkat Daerah',
            'Nama Kegiatan Statistik',
            'Tahun Kegiatan Statistik',
            'Nomor Rekomendasi',
        ];
        foreach ($domainOrder as $domain) {
            $headers[] = 'Kode Indikator';
            $headers[] = 'Nilai OPD';
            $headers[] = 'Penjelasan (Setiap tingkatan kriteria diberi Bukti Dukung)';
            $headers[] = 'Penilaian BPS';
            $headers[] = 'Catatan BPS';
        }

        $rows = [];
        foreach ($packages as $package) {
            $rowData = [
                (string) $package['opd_name'],
                (string) $package['nama_kegiatan'],
                (string) $package['tahun'],
                (string) $package['nomor_rekomendasi'],
            ];

            foreach ($domainOrder as $domain) {
                $hist = $package['domain_groups'][(int) $domain['id']] ?? null;
                if ($hist && $hist->count() > 0) {
                    /** @var \Illuminate\Support\Collection $hist */
                    $base = $hist->where('status', '!=', 'revisi')->sortByDesc('id')->first();
                    $rev1 = $hist->where('status', 'revisi')->where('revisi_round', 1)->sortByDesc('id')->first();
                    $rev2 = $hist->where('status', 'revisi')->where('revisi_round', 2)->sortByDesc('id')->first();

                    $kode  = (string) (($base?->domain->kode ?? null) ?: ($hist->first()?->domain->kode ?? '-') ?: '-');
                    $nilai = (string) (($base?->nilai ?? null) ?: ($hist->sortByDesc('id')->first()?->nilai ?? '-'));

                    $p0 = trim((string) ($base?->penjelasan ?? ''));
                    $p1 = trim((string) ($rev1?->penjelasan ?? ''));
                    $p2 = trim((string) ($rev2?->penjelasan ?? ''));

                    $penjelasanParts = [];
                    if ($p0 !== '') $penjelasanParts[] = "Sebelum: {$p0}";
                    if ($p1 !== '') $penjelasanParts[] = "Revisi 1: {$p1}";
                    if ($p2 !== '') $penjelasanParts[] = "Revisi 2: {$p2}";
                    $penjelasan = $penjelasanParts ? implode("\n", $penjelasanParts) : '-';

                    $lastBps = $hist->whereNotNull('penilaian_bps')->sortByDesc('updated_at')->first();
                    $penilaianBps = $lastBps?->penilaian_bps ? (string) $lastBps->penilaian_bps : '-';

                    $catEval = trim((string) ($hist->sortByDesc('updated_at')->first(fn ($r) => trim((string) ($r->catatan_bps ?? '')) !== '')?->catatan_bps ?? ''));
                    $keyBase = $package['user_id'].'|'.$package['tahun_id'].'|'.$package['nama_kegiatan'].'|'.$package['nomor_rekomendasi'].'|'.(int) $domain['id'];
                    $a1 = trim((string) ($alasanMap[$keyBase.'|1'] ?? ''));
                    $a2 = trim((string) ($alasanMap[$keyBase.'|2'] ?? ''));

                    $catParts = [];
                    if ($catEval !== '') $catParts[] = "Catatan Evaluasi: {$catEval}";
                    if ($a1 !== '') $catParts[] = "Alasan Revisi 1: {$a1}";
                    if ($a2 !== '') $catParts[] = "Alasan Revisi 2: {$a2}";
                    $catatanBps = $catParts ? implode("\n", $catParts) : '-';

                    $rowData[] = $kode;
                    $rowData[] = $nilai;
                    $rowData[] = $penjelasan;
                    $rowData[] = $penilaianBps;
                    $rowData[] = $catatanBps;
                } else {
                    $rowData[] = '-';
                    $rowData[] = '-';
                    $rowData[] = '-';
                    $rowData[] = '-';
                    $rowData[] = '-';
                }
            }

            $rows[] = $rowData;
        }

        $xlsxBinary = $this->buildXlsx($headers, $rows, count($domainOrder));

        $filename = 'rekap-lke-bps-' . now()->format('Ymd-His') . '.xlsx';

        return response($xlsxBinary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildXlsx(array $headers, array $rows, int $domainCount = 0): string
    {
        $allRows = array_merge([$headers], $rows);

        $sharedStringMap = [];
        $sharedStrings = [];
        $getSharedIndex = function (string $value) use (&$sharedStringMap, &$sharedStrings): int {
            if (array_key_exists($value, $sharedStringMap)) {
                return $sharedStringMap[$value];
            }
            $idx = count($sharedStrings);
            $sharedStringMap[$value] = $idx;
            $sharedStrings[] = $value;
            return $idx;
        };

        $sheetRowsXml = '';
        foreach ($allRows as $rIdx => $row) {
            $excelRow = $rIdx + 1;
            $cellsXml = '';
            foreach ($row as $cIdx => $val) {
                $col = $this->xlsxColumnName($cIdx + 1);
                $index = $getSharedIndex((string) $val);

                // Default alignment (Normal)
                $sAttr = ' s="0"'; 

                // Header row always gets centered or bold later if needed, but for now apply Normal.
                if ($rIdx > 0) {
                    if ($cIdx >= 4) {
                        // 5 kolom per domain: Kode(0), Nilai(1), Penjelasan(2), Penilaian BPS(3), Catatan BPS(4)
                        $colGroupIndex = ($cIdx - 4) % 5;
                        if ($colGroupIndex === 0) {
                            $sAttr = ' s="1"'; // Center (Kode Indikator)
                        } elseif ($colGroupIndex === 1) {
                            $sAttr = ' s="2"'; // Center (Nilai OPD)
                        } elseif ($colGroupIndex === 2) {
                            $sAttr = ' s="3"'; // Left, Wrap, Top (Penjelasan)
                        } elseif ($colGroupIndex === 3) {
                            $sAttr = ' s="2"'; // Center (Penilaian BPS)
                        } elseif ($colGroupIndex === 4) {
                            $sAttr = ' s="3"'; // Left, Wrap, Top (Catatan BPS)
                        }
                    } else {
                        // first 4 columns: OPD, Kegiatan, Tahun, Norek
                        $sAttr = ' s="0"'; 
                    }
                }

                $cellsXml .= '<c r="'.$col.$excelRow.'" t="s"'.$sAttr.'><v>'.$index.'</v></c>';
            }
            $sheetRowsXml .= '<row r="'.$excelRow.'">'.$cellsXml.'</row>';
        }

        $sharedItemsXml = '';
        foreach ($sharedStrings as $text) {
            $sharedItemsXml .= '<si><t xml:space="preserve">'.$this->xmlEscape($text).'</t></si>';
        }

        $colCount = max(1, count($headers));
        $rowCount = max(1, count($allRows));
        $dimension = 'A1:' . $this->xlsxColumnName($colCount) . $rowCount;

        $colsXml = '<cols>';
        $colsXml .= '<col min="1" max="1" width="30" customWidth="1"/>';
        $colsXml .= '<col min="2" max="2" width="45" customWidth="1"/>';
        $colsXml .= '<col min="3" max="3" width="15" customWidth="1"/>';
        $colsXml .= '<col min="4" max="4" width="25" customWidth="1"/>';
        
        $currentColIndex = 5;
        for ($i = 0; $i < $domainCount; $i++) {
            // Kode (Center)
            $colsXml .= '<col min="'.$currentColIndex.'" max="'.$currentColIndex.'" width="15" customWidth="1"/>';
            $currentColIndex++;
            // Nilai OPD (Center)
            $colsXml .= '<col min="'.$currentColIndex.'" max="'.$currentColIndex.'" width="12" customWidth="1"/>';
            $currentColIndex++;
            // Penjelasan (Left-Top Wrap)
            $colsXml .= '<col min="'.$currentColIndex.'" max="'.$currentColIndex.'" width="50" customWidth="1"/>';
            $currentColIndex++;
            // Penilaian BPS (Center)
            $colsXml .= '<col min="'.$currentColIndex.'" max="'.$currentColIndex.'" width="15" customWidth="1"/>';
            $currentColIndex++;
            // Catatan BPS (Left-Top Wrap)
            $colsXml .= '<col min="'.$currentColIndex.'" max="'.$currentColIndex.'" width="40" customWidth="1"/>';
            $currentColIndex++;
        }
        $colsXml .= '</cols>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="'.$dimension.'"/>'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $colsXml
            . '<sheetData>'.$sheetRowsXml.'</sheetData>'
            . '</worksheet>';

        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">'
            . $sharedItemsXml
            . '</sst>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Rekap LKE" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '</Types>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '</Relationships>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'  // s=0 Normal
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'  // s=1 Center
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'  // s=2 Center (Nilai)
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment wrapText="1" horizontal="left" vertical="top"/></xf>'  // s=3 Wrap
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        return $this->buildZipBinary([
            '[Content_Types].xml' => $contentTypesXml,
            '_rels/.rels' => $relsXml,
            'xl/workbook.xml' => $workbookXml,
            'xl/_rels/workbook.xml.rels' => $workbookRelsXml,
            'xl/worksheets/sheet1.xml' => $sheetXml,
            'xl/styles.xml' => $stylesXml,
            'xl/sharedStrings.xml' => $sharedStringsXml,
        ]);
    }

    private function xlsxColumnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public function destroy(Request $request)
    {
        $userId = (int) $request->get('user_id', 0);
        $tahunId = (int) $request->get('tahun_id', 0);
        $namaKegiatan = (string) $request->get('nama_kegiatan', '');
        $nomorRek = (string) $request->get('nomor_rekomendasi', '');

        abort_if($userId <= 0 || $tahunId <= 0 || $namaKegiatan === '' || $nomorRek === '', 400, 'Parameter tidak lengkap');

        DB::beginTransaction();
        try {
            // 1. Ambil semua LKE records dalam paket ini
            $lkeRecords = LembarKerjaEvaluasi::query()
                ->where('user_id', $userId)
                ->where('tahun_id', $tahunId)
                ->where('nama_kegiatan', $namaKegiatan)
                ->where('nomor_rekomendasi', $nomorRek)
                ->get();

            foreach ($lkeRecords as $lke) {
                // 2. Delete Bukti Dukung (files & database)
                $buktiDukungs = \App\Models\BuktiDukung::where('lembar_kerja_id', $lke->id)->get();
                foreach ($buktiDukungs as $bd) {
                    if (Storage::disk('public')->exists($bd->file)) {
                        Storage::disk('public')->delete($bd->file);
                    }
                    $bd->delete();
                }

                // 3. Delete Revisi Requests
                \App\Models\LkeRevisiRequest::where('revised_lke_id', $lke->id)
                    ->orWhere(function($q) use ($lke) {
                        $q->where('user_id', $lke->user_id)
                          ->where('tahun_id', $lke->tahun_id)
                          ->where('domain_id', $lke->domain_id)
                          ->where('nama_kegiatan', $lke->nama_kegiatan)
                          ->where('nomor_rekomendasi', $lke->nomor_rekomendasi);
                    })
                    ->delete();

                // 4. Delete the LKE record itself
                $lke->delete();
            }

            DB::commit();
            return back()->with('success', 'Paket LKE Berhasil Dihapus Beserta File Terkait.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus LKE: ' . $e->getMessage());
        }
    }

    private function buildZipBinary(array $entries): string
    {
        $localData = '';
        $centralData = '';
        $count = 0;

        foreach ($entries as $name => $content) {
            $name = (string) $name;
            $content = (string) $content;

            $offset = strlen($localData);
            $size = strlen($content);
            $crc = (int) sprintf('%u', crc32($content));
            $nameLen = strlen($name);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $nameLen,
                0
            );

            $localData .= $localHeader . $name . $content;

            $centralHeader = pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                0x0314,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $nameLen,
                0,
                0,
                0,
                0,
                32,
                $offset
            );

            $centralData .= $centralHeader . $name;
            $count++;
        }

        $endRecord = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $count,
            $count,
            strlen($centralData),
            strlen($localData),
            0
        );

        return $localData . $centralData . $endRecord;
    }
}
