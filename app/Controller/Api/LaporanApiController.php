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
            // PDF sederhana: HTML printable (buka di browser → Print → Save as PDF).
            // Untuk PDF biner native, pasang dompdf/mpdf lalu render $rows di sini.
            header('Content-Type: text/html; charset=utf-8');
            echo "<html><head><title>" . htmlspecialchars($title) . "</title>";
            echo "<style>body{font-family:Arial}table{border-collapse:collapse;width:100%}th,td{border:1px solid #333;padding:6px;font-size:12px}</style></head><body>";
            echo "<h2>" . htmlspecialchars($title) . "</h2><p>Periode: " . htmlspecialchars($start . ' s/d ' . $end) . " — Total: " . count($rows) . "</p>";
            if ($rows) {
                echo "<table><tr>";
                foreach (array_keys($rows[0]) as $h) {
                    echo "<th>" . htmlspecialchars((string) $h) . "</th>";
                }
                echo "</tr>";
                foreach ($rows as $r) {
                    echo "<tr>";
                    foreach ($r as $c) {
                        echo "<td>" . htmlspecialchars((string) ($c ?? '')) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Tidak ada data.</p>";
            }
            echo "<script>window.print()</script></body></html>";
            if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod')) != 'test') {
                exit();
            }
            return;
        }

        // CSV & XLSX (XLSX disajikan sebagai spreadsheet-compatible CSV agar tanpa dependency tambahan)
        $mime = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv';
        $ext = $format === 'xlsx' ? 'xlsx.csv' : 'csv';
        header('Content-Type: ' . $mime);
        header("Content-Disposition: attachment; filename=\"{$jenis}-" . date('Ymd') . ".{$ext}\"");
        $out = fopen('php://output', 'w');
        if ($rows) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $r) {
                fputcsv($out, array_values(array_map(fn($v) => (string) ($v ?? ''), $r)));
            }
        }
        fclose($out);
        if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod')) != 'test') {
            exit();
        }
    }
}
