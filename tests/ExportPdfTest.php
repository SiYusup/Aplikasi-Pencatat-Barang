<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Test;

use PHPUnit\Framework\TestCase;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\LaporanApiController;

class ExportPdfTest extends TestCase
{
    private \PDO $db;
    private LaporanApiController $api;

    protected function setUp(): void
    {
        Database::reset();
        $this->db = Database::getConnection('test');
        $this->db->exec("TRUNCATE audit_logs, barang_keluar, barang_masuk, barang, kategoris, suppliers, sessions, users RESTART IDENTITY CASCADE");
        $this->db->exec("INSERT INTO kategoris(nama) VALUES ('Umum') ON CONFLICT (nama) DO NOTHING");
        $this->db->exec("INSERT INTO barang(kode, nama, satuan, harga, stok) VALUES ('BRG-1', 'Kabel UTP', 'pcs', 75000, 10)");
        $this->api = new LaporanApiController();
    }

    private function exportPdf(string $jenis): string
    {
        $_GET = ['format' => 'pdf'];
        ob_start();
        $this->api->export($jenis);
        return ob_get_clean();
    }

    public function testExportBarangPdfValid(): void
    {
        $pdf = $this->exportPdf('barang');
        self::assertStringStartsWith('%PDF', $pdf);
        self::assertGreaterThan(1000, strlen($pdf));
    }

    public function testExportMasukPdfKosongTetapValid(): void
    {
        $_GET = ['format' => 'pdf', 'start_date' => '2999-01-01', 'end_date' => '2999-12-31'];
        ob_start();
        $this->api->export('masuk');
        $pdf = ob_get_clean();
        self::assertStringStartsWith('%PDF', $pdf);
    }
}
