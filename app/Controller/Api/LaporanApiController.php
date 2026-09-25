<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Controller\Api;

use UCrazy\AplikasiPencatatBarangCrud\App\ApiResponse;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Repository\BarangRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\TransaksiRepository;

class LaporanApiController
{
    private BarangRepository $barangRepository;
    private TransaksiRepository $transaksiRepository;
    private \PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
        $this->barangRepository = new BarangRepository($this->connection);
        $this->transaksiRepository = new TransaksiRepository($this->connection);
    }

    // ---------- DASHBOARD ----------
    public function summary(): void
    {
        $sum = $this->barangRepository->summary();
        $start = $_GET['start_date'] ?? date('Y-m-01');
        $end = $_GET['end_date'] ?? date('Y-m-d');
        $masuk = $this->transaksiRepository->sumMasuk($start, $end);
        $keluar = $this->transaksiRepository->sumKeluar($start, $end);
        $kritis = $this->barangRepository->findAll(onlyLowStock: true);
        ApiResponse::json(['success' => true, 'data' => [
            'total_barang' => $sum['total_barang'],
            'total_kategori' => (int) $this->connection->query("SELECT COUNT(*) FROM kategoris")->fetchColumn(),
            'total_stok' => $sum['total_stok'],
            'nilai_persediaan' => $sum['nilai_persediaan'],
            'barang_masuk_periode' => (int) $masuk['total_qty'],
            'barang_keluar_periode' => (int) $keluar['total_qty'],
            'stok_kritis_count' => count($kritis),
            'stok_kritis' => array_map(fn($b) => $b->toArray(), array_slice($kritis, 0, 5)),
        ]]);
    }

    public function chart(): void
    {
        ApiResponse::json(['success' => true, 'data' => $this->transaksiRepository->chart($_GET['range'] ?? '30d')]);
    }

    public function lowStock(): void
    {
        $items = $this->barangRepository->findAll(onlyLowStock: true);
        ApiResponse::json(['success' => true, 'data' => array_map(fn($b) => $b->toArray(), $items)]);
    }

    public function notifikasi(): void
    {
        $items = $this->barangRepository->findAll(onlyLowStock: true);
        $data = array_map(fn($b) => [
            'id' => 'low-' . $b->id,
            'judul' => 'Stok menipis',
            'pesan' => "{$b->nama} tersisa {$b->stok} (min {$b->stokMinimum})",
            'tipe' => 'low_stock',
            'is_read' => false,
        ], $items);
        ApiResponse::json(['success' => true, 'data' => $data]);
    }

    // ---------- KATEGORI & SUPPLIER ----------
    public function kategoriList(): void
    {
        $rows = $this->connection->query("SELECT k.*, (SELECT COUNT(*) FROM barang b WHERE b.kategori_id = k.id) AS jumlah_barang FROM kategoris k ORDER BY nama")->fetchAll(\PDO::FETCH_ASSOC);
        ApiResponse::json(['success' => true, 'data' => $rows]);
    }

    public function kategoriCreate(): void
    {
        $input = ApiResponse::input();
        if (empty($input['nama'])) {
            ApiResponse::error('Nama kategori wajib diisi', 422);
        }
        $stmt = $this->connection->prepare("INSERT INTO kategoris(nama, deskripsi) VALUES (?, ?) RETURNING id");
        try {
            $stmt->execute([$input['nama'], $input['deskripsi'] ?? null]);
            ApiResponse::json(['success' => true, 'data' => ['id' => (int) $stmt->fetchColumn(), 'nama' => $input['nama']]], 201);
        } catch (\PDOException) {
            ApiResponse::error('Kategori sudah ada', 422);
        }
    }

    public function supplierList(): void
    {
        $rows = $this->connection->query("SELECT * FROM suppliers ORDER BY nama")->fetchAll(\PDO::FETCH_ASSOC);
        ApiResponse::json(['success' => true, 'data' => $rows]);
    }

    public function supplierCreate(): void
    {
        $input = ApiResponse::input();
        if (empty($input['nama'])) {
            ApiResponse::error('Nama supplier wajib diisi', 422);
        }
        $stmt = $this->connection->prepare("INSERT INTO suppliers(nama, kontak, alamat) VALUES (?, ?, ?) RETURNING id");
        $stmt->execute([$input['nama'], $input['kontak'] ?? null, $input['alamat'] ?? null]);
        ApiResponse::json(['success' => true, 'data' => ['id' => (int) $stmt->fetchColumn(), 'nama' => $input['nama']]], 201);
    }

    // ---------- LAPORAN ----------
    public function laporanStok(): void
    {
        $items = $this->barangRepository->findAll(
            '',
            isset($_GET['kategori_id']) && $_GET['kategori_id'] !== '' ? (int) $_GET['kategori_id'] : null,
            ($_GET['stok_kritis'] ?? '0') === '1'
        );
        ApiResponse::json(['success' => true, 'data' => array_map(fn($b) => $b->toArray(), $items)]);
    }

    public function laporanMasuk(): void
    {
        $start = $_GET['start_date'] ?? '';
        $end = $_GET['end_date'] ?? '';
        ApiResponse::json(['success' => true,
            'data' => $this->transaksiRepository->listMasuk($start, $end),
            'summary' => $this->transaksiRepository->sumMasuk($start, $end),
        ]);
    }

    public function laporanKeluar(): void
    {
        $start = $_GET['start_date'] ?? '';
        $end = $_GET['end_date'] ?? '';
        ApiResponse::json(['success' => true,
            'data' => $this->transaksiRepository->listKeluar($start, $end),
            'summary' => $this->transaksiRepository->sumKeluar($start, $end),
        ]);
    }

    public function laporanMutasi(): void
    {
        $start = $_GET['start_date'] ?? '';
        $end = $_GET['end_date'] ?? '';
        $barangId = isset($_GET['barang_id']) && $_GET['barang_id'] !== '' ? (int) $_GET['barang_id'] : null;
        $mutasi = [];
        foreach ($this->transaksiRepository->listMasuk($start, $end) as $r) {
            if ($barangId && (int) $r['barang_id'] !== $barangId) {
                continue;
            }
            $mutasi[] = ['tanggal' => $r['tanggal_masuk'], 'tipe' => 'masuk', 'kode_transaksi' => $r['kode_transaksi'], 'barang' => $r['barang_nama'], 'barang_id' => (int) $r['barang_id'], 'qty_masuk' => (int) $r['jumlah'], 'qty_keluar' => 0, 'keterangan' => $r['keterangan']];
        }
        foreach ($this->transaksiRepository->listKeluar($start, $end) as $r) {
            if ($barangId && (int) $r['barang_id'] !== $barangId) {
                continue;
            }
            $mutasi[] = ['tanggal' => $r['tanggal_keluar'], 'tipe' => 'keluar', 'kode_transaksi' => $r['kode_transaksi'], 'barang' => $r['barang_nama'], 'barang_id' => (int) $r['barang_id'], 'qty_masuk' => 0, 'qty_keluar' => (int) $r['jumlah'], 'keterangan' => $r['keterangan']];
        }
        usort($mutasi, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
        ApiResponse::json(['success' => true, 'data' => array_values($mutasi)]);
    }

    public function auditLogs(): void
    {
        ApiResponse::json(['success' => true, 'data' => $this->transaksiRepository->auditList(100)]);
    }

    // ---------- EXPORT (PDF / Excel / CSV) ----------
    public function export(string $jenis): void
    {
        $format = strtolower($_GET['format'] ?? 'csv');
        $start = $_GET['start_date'] ?? '';
        $end = $_GET['end_date'] ?? '';

        if ($jenis === 'barang') {
            $items = $this->barangRepository->findAll();
            $rows = array_map(fn($b) => ['Kode' => $b->kode, 'Nama' => $b->nama, 'Kategori' => $b->kategoriNama, 'Satuan' => $b->satuan, 'Harga' => $b->harga, 'Stok' => $b->stok, 'Min' => $b->stokMinimum], $items);
            $title = 'Laporan Data Barang';
        } elseif ($jenis === 'masuk') {
            $rows = $this->transaksiRepository->listMasuk($start, $end);
            $title = 'Laporan Barang Masuk';
        } elseif ($jenis === 'keluar') {
            $rows = $this->transaksiRepository->listKeluar($start, $end);
            $title = 'Laporan Barang Keluar';
        } else {
            $rows = array_merge($this->transaksiRepository->listMasuk($start, $end), $this->transaksiRepository->listKeluar($start, $end));
            $title = 'Rekap Laporan';
        }

        if ($format === 'pdf') {
            $this->exportPdf($jenis, $title, $rows, $start, $end);
            return;
        }

        if ($format === 'xlsx') {
            $this->exportXlsx($jenis, $title, $rows, $start, $end);
            return;
        }

        // CSV murni memakai fputcsv + BOM UTF-8 agar rapi dibuka di Excel
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $jenis . '-' . date('Ymd') . '.csv"');
        }
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel baca UTF-8 dengan benar
        if ($rows) {
            fputcsv($out, array_keys($rows[0]), ',', '"', '');
            foreach ($rows as $r) {
                fputcsv($out, array_values(array_map(fn($v) => (string) ($v ?? ''), $r)), ',', '"', '');
            }
        } else {
            fputcsv($out, ['Tidak ada data pada periode ini'], ',', '"', '');
        }
        fclose($out);
        if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod')) != 'test') {
            exit();
        }
    }

    /** Render laporan sebagai file .xlsx asli via PhpSpreadsheet (judul, header gaya, border, freeze). */
    private function exportXlsx(string $jenis, string $title, array $rows, string $start, string $end): void
    {
        $periode = ($start !== '' || $end !== '') ? trim($start . ' s/d ' . $end, ' s/d ') : 'Semua periode';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));
        $set = fn(int $col, int $row, mixed $val) => $sheet->setCellValue(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row, $val
        );

        $set(1, 1, 'Aplikasi Pencatat Barang — ' . $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $set(1, 2, 'Periode: ' . $periode . '  |  Total baris: ' . count($rows) . '  |  Dicetak: ' . date('d M Y H:i'));

        $headerRow = 4;
        $colCount = 1;
        if ($rows) {
            $headers = array_keys($rows[0]);
            $set(1, $headerRow, 'No');
            foreach ($headers as $i => $h) {
                $set($i + 2, $headerRow, ucwords(str_replace('_', ' ', (string) $h)));
            }
            $colCount = count($headers) + 1;
            $rowNum = $headerRow + 1;
            $no = 1;
            foreach ($rows as $r) {
                $set(1, $rowNum, $no++);
                $col = 2;
                foreach ($r as $c) {
                    $set($col++, $rowNum, $c === null ? '-' : $c);
                }
                $rowNum++;
            }
            $lastRow = $rowNum - 1;
        } else {
            $set(1, $headerRow, 'Keterangan');
            $set(1, $headerRow + 1, 'Tidak ada data pada periode ini');
            $lastRow = $headerRow + 1;
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
        ]);
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '999999']]],
        ]);
        for ($c = 1; $c <= $colCount; $c++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
        $sheet->freezePane('A' . ($headerRow + 1));

        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $jenis . '-' . date('Ymd') . '.xlsx"');
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod')) != 'test') {
            exit();
        }
    }

    /** Render laporan sebagai PDF biner via dompdf (A4 landscape, kop + tabel rapi + nomor halaman). */
    private function exportPdf(string $jenis, string $title, array $rows, string $start, string $end): void
    {
        $periode = ($start !== '' || $end !== '') ? trim($start . ' s/d ' . $end, ' s/d ') : 'Semua periode';
        $esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

        $html = '<html><head><meta charset="utf-8"><style>'
            . 'body{font-family:Helvetica,Arial,sans-serif;font-size:10px;color:#111;}'
            . '.kop{text-align:center;border-bottom:3px double #0f172a;padding-bottom:8px;margin-bottom:10px;}'
            . '.kop h1{font-size:18px;margin:0;}'
            . '.kop p{font-size:10px;color:#555;margin:2px 0 0;}'
            . 'h2{font-size:15px;margin:0 0 2px;}'
            . '.meta{font-size:10px;color:#333;margin-bottom:10px;}'
            . 'table{border-collapse:collapse;width:100%;}'
            . 'th{background:#0f172a;color:#fff;padding:6px 5px;text-align:left;font-size:9px;}'
            . 'td{border:1px solid #999;padding:5px;font-size:9px;vertical-align:top;}'
            . 'tr:nth-child(even) td{background:#f1f5f9;}'
            . '.num{text-align:right;}'
            . '.footer{margin-top:10px;font-size:9px;color:#555;}'
            . '</style></head><body>'
            . '<div class="kop"><h1>Aplikasi Pencatat Barang</h1><p>Inventory Management — Laporan Operasional</p></div>'
            . '<h2>' . $esc($title) . '</h2>'
            . '<div class="meta">Periode: ' . $esc($periode) . ' &nbsp;|&nbsp; Total baris: ' . count($rows)
            . ' &nbsp;|&nbsp; Dicetak: ' . date('d M Y H:i') . '</div>';

        if ($rows) {
            $headers = array_keys($rows[0]);
            $html .= '<table><thead><tr><th style="width:30px;">No</th>';
            foreach ($headers as $h) {
                $label = ucwords(str_replace('_', ' ', (string) $h));
                $html .= '<th>' . $esc($label) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            $no = 1;
            foreach ($rows as $r) {
                $html .= '<tr><td class="num">' . $no++ . '</td>';
                foreach ($r as $c) {
                    $cell = $c === null ? '-' : (string) $c;
                    $cls = is_numeric($c) ? ' class="num"' : '';
                    $html .= '<td' . $cls . '>' . $esc($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p><i>Tidak ada data pada periode ini.</i></p>';
        }
        $html .= '<div class="footer">Dokumen dihasilkan otomatis oleh sistem. ' . $esc($title) . '.</div></body></html>';

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(770, 550, 'Hal {PAGE_NUM} / {PAGE_COUNT}', null, 8, [0, 0, 0]);

        $pdf = $dompdf->output();
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $jenis . '-' . date('Ymd') . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
        }
        echo $pdf;
        if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod')) != 'test') {
            exit();
        }
    }
}
