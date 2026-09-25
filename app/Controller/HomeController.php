<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Controller;

use UCrazy\AplikasiPencatatBarangCrud\App\View;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;

class HomeController
{
    private SessionService $sessionService;

    public function __construct()
    {
        $connection = Database::getConnection();
        $userRepository = new UserRepository($connection);
        $this->sessionService = new SessionService(new SessionRepository($connection), $userRepository);
    }

    private function page(string $view, string $title): void
    {
        View::render($view, ['title' => $title, 'user' => $this->sessionService->current()]);
    }

    public function index(): void
    {
        $this->page('Dashboard/index', 'Dashboard Barang');
    }

    public function barang(): void
    {
        $this->page('Barang/index', 'Data Barang');
    }

    public function kategori(): void
    {
        $this->page('Kategori/index', 'Data Kategori');
    }

    public function supplier(): void
    {
        $this->page('Supplier/index', 'Data Supplier');
    }

    public function barangMasuk(): void
    {
        $this->page('Barang/masuk', 'Barang Masuk');
    }

    public function barangKeluar(): void
    {
        $this->page('Barang/keluar', 'Barang Keluar');
    }

    public function laporan(): void
    {
        $this->page('Barang/laporan', 'Laporan');
    }
}
