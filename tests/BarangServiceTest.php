<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Test;

use PHPUnit\Framework\TestCase;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangKeluarRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangMasukRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\UserRegisterRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\BarangRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\TransaksiRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\BarangService;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;
use UCrazy\AplikasiPencatatBarangCrud\Service\UserService;

class BarangServiceTest extends TestCase
{
    private BarangService $barangService;
    private BarangRepository $barangRepository;
    private int $userId;

    protected function setUp(): void
    {
        Database::reset();
        $connection = Database::getConnection('test');
        $connection->exec("TRUNCATE audit_logs, barang_keluar, barang_masuk, barang, sessions, users RESTART IDENTITY CASCADE");
        $connection->exec("INSERT INTO kategoris(nama) VALUES ('Umum') ON CONFLICT (nama) DO NOTHING");

        $userRepository = new UserRepository($connection);
        $userService = new UserService($userRepository);
        $req = new UserRegisterRequest();
        $req->name = 'Admin';
        $req->email = 'admin@example.com';
        $req->password = 'admin1234';
        $user = $userService->register($req);
        $this->userId = $user->id;

        $sessionService = new SessionService(new SessionRepository($connection), $userRepository);
        $session = $sessionService->create($user->id);
        $_COOKIE[SessionService::$COOKIE_NAME] = $session->id;

        $this->barangRepository = new BarangRepository($connection);
        $this->barangService = new BarangService($this->barangRepository, new TransaksiRepository($connection));
    }

    public function testCrudMasukKeluar(): void
    {
        $req = new BarangRequest();
        $req->nama = 'Kabel UTP';
        $req->satuan = 'pcs';
        $req->harga = 75000;
        $req->stokAwal = 10;
        $barang = $this->barangService->create($req, $this->userId);
        self::assertEquals(10, $barang->stok);

        $masuk = new BarangMasukRequest();
        $masuk->barangId = $barang->id;
        $masuk->jumlah = 5;
        $this->barangService->masuk($masuk, $this->userId);
        self::assertEquals(15, $this->barangRepository->findById($barang->id)->stok);

        $keluar = new BarangKeluarRequest();
        $keluar->barangId = $barang->id;
        $keluar->jumlah = 7;
        $this->barangService->keluar($keluar, $this->userId);
        self::assertEquals(8, $this->barangRepository->findById($barang->id)->stok);

        $opname = $this->barangService->opname($barang->id, 20, 'opname test', $this->userId);
        self::assertEquals(20, $opname->stok);
    }

    public function testKeluarMelebihiStokGagal(): void
    {
        $req = new BarangRequest();
        $req->nama = 'Mouse';
        $req->satuan = 'pcs';
        $req->stokAwal = 2;
        $barang = $this->barangService->create($req, $this->userId);

        $keluar = new BarangKeluarRequest();
        $keluar->barangId = $barang->id;
        $keluar->jumlah = 5;
        $this->expectException(\Exception::class);
        $this->barangService->keluar($keluar, $this->userId);
    }
}
