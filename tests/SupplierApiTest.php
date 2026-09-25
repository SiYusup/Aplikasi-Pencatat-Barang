<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Test;

use PHPUnit\Framework\TestCase;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\SupplierApiController;
use UCrazy\AplikasiPencatatBarangCrud\Exception\ApiResponseSent;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserRegisterRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;
use UCrazy\AplikasiPencatatBarangCrud\Service\UserService;

class SupplierApiTest extends TestCase
{
    private \PDO $db;
    private SupplierApiController $api;

    protected function setUp(): void
    {
        Database::reset();
        $this->db = Database::getConnection('test');
        $this->db->exec("TRUNCATE audit_logs, barang_keluar, barang_masuk, barang, kategoris, suppliers, sessions, users RESTART IDENTITY CASCADE");

        $userRepository = new UserRepository($this->db);
        $userService = new UserService($userRepository);
        $req = new UserRegisterRequest();
        $req->name = 'Admin';
        $req->email = 'admin@example.com';
        $req->password = 'admin1234';
        $user = $userService->register($req);

        $session = (new SessionService(new SessionRepository($this->db), $userRepository))->create($user->id);
        $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

        $this->api = new SupplierApiController();
        $_GET = [];
    }

    /** Panggil endpoint controller dan ambil [httpCode, body] */
    private function call(string $method, array $args = []): array
    {
        http_response_code(200);
        ob_start();
        try {
            $this->api->$method(...$args);
            $out = ob_get_clean();
            return [http_response_code(), json_decode($out, true)];
        } catch (ApiResponseSent $e) {
            ob_get_clean();
            return [$e->httpCode, $e->body];
        }
    }

    public function testCrudLengkap(): void
    {
        // CREATE
        $_POST = ['nama' => 'PT Maju Jaya', 'kontak' => '081234567890', 'alamat' => 'Jl. Merdeka'];
        [$code, $body] = $this->call('create');
        self::assertEquals(201, $code);
        self::assertTrue($body['success']);
        $id = $body['data']['id'];

        // LIST + SEARCH
        $_POST = [];
        $_GET = ['search' => 'maju'];
        [$code, $body] = $this->call('list');
        self::assertEquals(200, $code);
        self::assertCount(1, $body['data']);
        self::assertEquals(0, $body['data'][0]['jumlah_transaksi']);

        // SHOW
        [$code, $body] = $this->call('show', [(string) $id]);
        self::assertEquals(200, $code);
        self::assertEquals('PT Maju Jaya', $body['data']['nama']);

        // SHOW tidak ada -> 404
        [$code] = $this->call('show', ['9999']);
        self::assertEquals(404, $code);

        // UPDATE
        $_POST = ['nama' => 'PT Maju Jaya Abadi', 'kontak' => '081111'];
        [$code, $body] = $this->call('update', [(string) $id]);
        self::assertEquals(200, $code);
        self::assertEquals('PT Maju Jaya Abadi', $body['data']['nama']);
        self::assertEquals('Jl. Merdeka', $body['data']['alamat']); // tidak berubah

        // Transaksi pakai supplier -> hapus supplier => supplier_id SET NULL
        $this->db->exec("INSERT INTO barang(kode, nama, satuan) VALUES ('BRG-1', 'Kabel', 'pcs')");
        $barangId = $this->db->query("SELECT id FROM barang WHERE kode = 'BRG-1'")->fetchColumn();
        $this->db->exec("INSERT INTO barang_masuk(kode_transaksi, barang_id, jumlah, supplier_id) VALUES ('BM-1', $barangId, 5, $id)");
        $_POST = [];
        [$code] = $this->call('delete', [(string) $id]);
        self::assertEquals(200, $code);
        $supplierId = $this->db->query("SELECT supplier_id FROM barang_masuk WHERE kode_transaksi = 'BM-1'")->fetchColumn();
        self::assertNull($supplierId);

        // DELETE tidak ada -> 404
        [$code] = $this->call('delete', ['9999']);
        self::assertEquals(404, $code);
    }

    public function testCreateNamaPendekGagal(): void
    {
        $_POST = ['nama' => 'A'];
        [$code] = $this->call('create');
        self::assertEquals(422, $code);
    }
}
