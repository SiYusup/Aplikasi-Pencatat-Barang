<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Test;

use PHPUnit\Framework\TestCase;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\KategoriApiController;
use UCrazy\AplikasiPencatatBarangCrud\Exception\ApiResponseSent;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserRegisterRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;
use UCrazy\AplikasiPencatatBarangCrud\Service\UserService;

class KategoriApiTest extends TestCase
{
    private \PDO $db;
    private KategoriApiController $api;

    protected function setUp(): void
    {
        Database::reset();
        $this->db = Database::getConnection('test');
        $this->db->exec("TRUNCATE audit_logs, barang_keluar, barang_masuk, barang, kategoris, sessions, users RESTART IDENTITY CASCADE");

        $userRepository = new UserRepository($this->db);
        $userService = new UserService($userRepository);
        $req = new UserRegisterRequest();
        $req->name = 'Admin';
        $req->email = 'admin@example.com';
        $req->password = 'admin1234';
        $user = $userService->register($req);

        $session = (new SessionService(new SessionRepository($this->db), $userRepository))->create($user->id);
        $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

        $this->api = new KategoriApiController();
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
        // CREATE via $_POST (ApiResponse::input membaca $_POST + php://input)
        $_POST = ['nama' => 'Elektronik', 'deskripsi' => 'Barang elektronik'];
        [$code, $body] = $this->call('create');
        self::assertEquals(201, $code);
        self::assertTrue($body['success']);
        $id = $body['data']['id'];

        // CREATE duplikat -> 422
        $_POST = ['nama' => 'Elektronik'];
        [$code] = $this->call('create');
        self::assertEquals(422, $code);

        // LIST + SEARCH
        $_POST = [];
        $_GET = ['search' => 'elek'];
        [$code, $body] = $this->call('list');
        self::assertEquals(200, $code);
        self::assertCount(1, $body['data']);
        self::assertEquals(0, $body['data'][0]['jumlah_barang']);

        // SHOW
        [$code, $body] = $this->call('show', [(string) $id]);
        self::assertEquals(200, $code);
        self::assertEquals('Elektronik', $body['data']['nama']);

        // SHOW tidak ada -> 404
        [$code] = $this->call('show', ['9999']);
        self::assertEquals(404, $code);

        // UPDATE
        $_POST = ['nama' => 'Elektronik Kantor', 'deskripsi' => 'Update'];
        [$code, $body] = $this->call('update', [(string) $id]);
        self::assertEquals(200, $code);
        self::assertEquals('Elektronik Kantor', $body['data']['nama']);

        // Barang pakai kategori -> hapus kategori => kategori_id SET NULL
        $this->db->exec("INSERT INTO barang(kode, nama, kategori_id, satuan) VALUES ('BRG-1', 'Kabel', $id, 'pcs')");
        $_POST = [];
        [$code, $body] = $this->call('delete', [(string) $id]);
        self::assertEquals(200, $code);
        $kategoriId = $this->db->query("SELECT kategori_id FROM barang WHERE kode = 'BRG-1'")->fetchColumn();
        self::assertNull($kategoriId);

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
