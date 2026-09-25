<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Test;

use PHPUnit\Framework\TestCase;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\LaporanApiController;

class ExportFileTest extends TestCase
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

    private function export(string $jenis, string $format): string
    {
        $_GET = ['format' => $format];
        ob_start();
        $this->api->export($jenis);
        return ob_get_clean();
    }

    public function testExportXlsxAsliTerbaca(): void
    {
        $bin = $this->export('barang', 'xlsx');
        self::assertStringStartsWith('PK', $bin); // xlsx = arsip ZIP

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        file_put_contents($tmp, $bin);
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        $sheet = $spreadsheet->getActiveSheet();
        self::assertStringContainsString('Laporan Data Barang', (string) $sheet->getCell('A1')->getValue());
        self::assertEquals('No', $sheet->getCell('A4')->getValue());
        self::assertEquals('BRG-1', $sheet->getCell('B5')->getValue());
        unlink($tmp);
    }

    public function testExportCsvFputcsvPlusBom(): void
    {
        $csv = $this->export('barang', 'csv');
        self::assertStringStartsWith("\xEF\xBB\xBF", $csv); // BOM UTF-8 untuk Excel

        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, $csv);
        $fp = fopen($tmp, 'r');
        fseek($fp, 3); // lewati BOM
        $header = fgetcsv($fp, null, ',', '"', '');
        $first = fgetcsv($fp, null, ',', '"', '');
        fclose($fp);
        unlink($tmp);

        self::assertContains('Kode', $header);
        self::assertContains('BRG-1', $first);
    }
}
