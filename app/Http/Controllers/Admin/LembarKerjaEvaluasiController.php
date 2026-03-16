<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LembarKerjaEvaluasi;
use App\Models\Tahun;
use App\Models\User;
use Illuminate\Http\Request;

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

        // semua domain agar urut & tampil walau belum ada row LKE
        $domains = \App\Models\Domain::with(['kriterias' => function($q){
                $q->orderBy('tingkat');
            }])
            ->orderBy('kode')
            ->get();

        return view('admin.lke.show', compact('user','tahun','domains','items','namaKegiatan','nomorRek'));
    }

    public function exportExcel(Request $request)
    {
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

        // Ambil record terbaru per paket+domain agar tidak dobel.
        $latestPerPackageDomain = $items
            ->groupBy(function ($row) {
                return $row->user_id.'|'.$row->tahun_id.'|'.$row->nama_kegiatan.'|'.$row->nomor_rekomendasi.'|'.$row->domain_id;
            })
            ->map(fn($group) => $group->sortByDesc('id')->first())
            ->values();

        // Urutan indikator untuk kolom dinamis.
        $domainOrder = $latestPerPackageDomain
            ->map(function ($row) {
                return [
                    'id' => (int) $row->domain_id,
                    'kode' => (string) ($row->domain->kode ?? ''),
                ];
            })
            ->unique('id')
            ->sortBy('kode')
            ->values();

        $packages = $latestPerPackageDomain
            ->groupBy(function ($row) {
                return $row->user_id.'|'.$row->tahun_id.'|'.$row->nama_kegiatan.'|'.$row->nomor_rekomendasi;
            })
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'user_id' => (int) $first->user_id,
                    'opd_name' => (string) ($first->user->nama ?? $first->user->username ?? '-'),
                    'tahun' => (string) ($first->tahun->tahun ?? '-'),
                    'nama_kegiatan' => (string) $first->nama_kegiatan,
                    'nomor_rekomendasi' => (string) $first->nomor_rekomendasi,
                    'by_domain' => $group->keyBy('domain_id'),
                ];
            })
            ->sortBy([
                ['user_id', 'asc'],
                ['tahun', 'asc'],
                ['nama_kegiatan', 'asc'],
                ['nomor_rekomendasi', 'asc'],
            ])
            ->values();

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
                $row = $package['by_domain'][(int) $domain['id']] ?? null;
                if ($row) {
                    $kode      = (string) ($row->domain->kode ?? '-');
                    $nilai     = (string) ($row->nilai ?? '-');
                    $penjelasanRaw = trim((string) ($row->penjelasan ?? ''));
                    $penjelasan = $penjelasanRaw === '' ? '-' : $penjelasanRaw;
                    $penilaianBps  = $row->penilaian_bps ? (string) $row->penilaian_bps : '-';
                    $catatanBpsRaw = trim((string) ($row->catatan_bps ?? ''));
                    $catatanBps    = $catatanBpsRaw === '' ? '-' : $catatanBpsRaw;

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
