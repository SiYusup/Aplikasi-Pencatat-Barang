<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Controller\Api;

use UCrazy\AplikasiPencatatBarangCrud\App\ApiResponse;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Exception\ValidationException;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangKeluarRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangMasukRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\BarangRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\TransaksiRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\BarangService;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;

class BarangApiController
{
    private BarangService $barangService;
    private BarangRepository $barangRepository;
    private TransaksiRepository $transaksiRepository;
    private SessionService $sessionService;

    public function __construct()
    {
        $connection = Database::getConnection();
        $this->barangRepository = new BarangRepository($connection);
        $this->transaksiRepository = new TransaksiRepository($connection);
        $this->barangService = new BarangService($this->barangRepository, $this->transaksiRepository);
        $this->sessionService = new SessionService(new SessionRepository($connection), new UserRepository($connection));
    }

    private function userId(): int
    {
        return $this->sessionService->current()->id;
    }

    // ---------- MASTER BARANG ----------
    public function list(): void
    {
        $items = $this->barangRepository->findAll(
            $_GET['search'] ?? '',
            isset($_GET['kategori_id']) && $_GET['kategori_id'] !== '' ? (int) $_GET['kategori_id'] : null,
            ($_GET['stok_kritis'] ?? '0') === '1',
            $_GET['sort_by'] ?? 'created_at',
            strtolower($_GET['sort_dir'] ?? 'desc')
        );
        // Pagination sederhana di PHP (data umumnya < ribuan)
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 15)));
        $total = count($items);
        $slice = array_slice($items, ($page - 1) * $perPage, $perPage);
        ApiResponse::json([
            'success' => true,
            'data' => array_map(fn($b) => $b->toArray(), $slice),
            'meta' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => (int) ceil($total / $perPage)],
        ]);
    }

    public function create(): void
    {
        $input = ApiResponse::input();
        $request = $this->mapBarangRequest($input);
        try {
            $barang = $this->barangService->create($request, $this->userId());
            ApiResponse::json(['success' => true, 'message' => 'Barang berhasil ditambahkan', 'data' => $barang->toArray()], 201);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function show(string $id): void
    {
        $barang = $this->barangRepository->findById((int) $id);
        if ($barang === null) {
            ApiResponse::error('Barang tidak ditemukan', 404);
        }
        ApiResponse::json(['success' => true, 'data' => $barang->toArray()]);
    }

    public function update(string $id): void
    {
        $input = ApiResponse::input();
        $request = $this->mapBarangRequest($input);
        try {
            $barang = $this->barangService->update((int) $id, $request, $this->userId());
            ApiResponse::json(['success' => true, 'message' => 'Barang berhasil diupdate', 'data' => $barang->toArray()]);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), $e->getMessage() === 'Barang tidak ditemukan' ? 404 : 422);
        }
    }

    public function delete(string $id): void
    {
        try {
            $this->barangService->delete((int) $id, $this->userId());
            ApiResponse::json(['success' => true, 'message' => 'Barang berhasil dihapus']);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 404);
        }
    }

    public function opname(string $id): void
    {
        $input = ApiResponse::input();
        try {
            $barang = $this->barangService->opname((int) $id, (int) ($input['stok_fisik'] ?? -1), $input['keterangan'] ?? '', $this->userId());
            ApiResponse::json(['success' => true, 'message' => 'Stok opname berhasil', 'data' => $barang->toArray()]);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function history(string $id): void
    {
        $barang = $this->barangRepository->findById((int) $id);
        if ($barang === null) {
            ApiResponse::error('Barang tidak ditemukan', 404);
        }
        $masuk = array_filter(
            $this->transaksiRepository->listMasuk($_GET['start_date'] ?? '', $_GET['end_date'] ?? ''),
            fn($r) => (int) $r['barang_id'] === (int) $id
        );
        $keluar = array_filter(
            $this->transaksiRepository->listKeluar($_GET['start_date'] ?? '', $_GET['end_date'] ?? ''),
            fn($r) => (int) $r['barang_id'] === (int) $id
        );
        $mutasi = [];
        foreach ($masuk as $r) {
            $mutasi[] = ['tanggal' => $r['tanggal_masuk'], 'tipe' => 'masuk', 'kode_transaksi' => $r['kode_transaksi'], 'barang_id' => (int) $r['barang_id'], 'qty_masuk' => (int) $r['jumlah'], 'qty_keluar' => 0, 'keterangan' => $r['keterangan']];
        }
        foreach ($keluar as $r) {
            $mutasi[] = ['tanggal' => $r['tanggal_keluar'], 'tipe' => 'keluar', 'kode_transaksi' => $r['kode_transaksi'], 'barang_id' => (int) $r['barang_id'], 'qty_masuk' => 0, 'qty_keluar' => (int) $r['jumlah'], 'keterangan' => $r['keterangan']];
        }
        usort($mutasi, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
        ApiResponse::json(['success' => true, 'data' => array_values($mutasi)]);
    }

    // ---------- BARANG MASUK ----------
    public function listMasuk(): void
    {
        $data = $this->transaksiRepository->listMasuk($_GET['start_date'] ?? '', $_GET['end_date'] ?? '', $_GET['search'] ?? '');
        ApiResponse::json(['success' => true, 'data' => $data, 'meta' => ['total' => count($data)]]);
    }

    public function createMasuk(): void
    {
        $input = ApiResponse::input();
        $request = new BarangMasukRequest();
        $request->barangId = isset($input['barang_id']) ? (int) $input['barang_id'] : null;
        $request->jumlah = $input['jumlah'] ?? 0;
        $request->hargaBeli = $input['harga_beli'] ?? 0;
        $request->tanggalMasuk = $input['tanggal_masuk'] ?? null;
        $request->supplierId = isset($input['supplier_id']) && $input['supplier_id'] !== '' ? (int) $input['supplier_id'] : null;
        $request->keterangan = $input['keterangan'] ?? null;
        $request->items = $input['items'] ?? null;
        try {
            $results = $this->barangService->masuk($request, $this->userId());
            $single = $request->items ? $results : $results[0];
            ApiResponse::json(['success' => true, 'message' => 'Barang masuk berhasil dicatat, stok bertambah', 'data' => $single], 201);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function showMasuk(string $id): void
    {
        $row = $this->transaksiRepository->findMasukById((int) $id);
        if ($row === null) {
            ApiResponse::error('Transaksi tidak ditemukan', 404);
        }
        ApiResponse::json(['success' => true, 'data' => $row]);
    }

    public function deleteMasuk(string $id): void
    {
        try {
            $this->barangService->batalMasuk((int) $id, $this->userId());
            ApiResponse::json(['success' => true, 'message' => 'Transaksi masuk dibatalkan, stok dikembalikan']);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 404);
        }
    }

    // ---------- BARANG KELUAR ----------
    public function listKeluar(): void
    {
        $data = $this->transaksiRepository->listKeluar($_GET['start_date'] ?? '', $_GET['end_date'] ?? '', $_GET['search'] ?? '');
        ApiResponse::json(['success' => true, 'data' => $data, 'meta' => ['total' => count($data)]]);
    }

    public function createKeluar(): void
    {
        $input = ApiResponse::input();
        $request = new BarangKeluarRequest();
        $request->barangId = isset($input['barang_id']) ? (int) $input['barang_id'] : null;
        $request->jumlah = $input['jumlah'] ?? 0;
        $request->tanggalKeluar = $input['tanggal_keluar'] ?? null;
        $request->penerima = $input['penerima'] ?? null;
        $request->keterangan = $input['keterangan'] ?? null;
        try {
            $row = $this->barangService->keluar($request, $this->userId());
            ApiResponse::json(['success' => true, 'message' => 'Barang keluar berhasil dicatat, stok berkurang', 'data' => $row], 201);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function showKeluar(string $id): void
    {
        $row = $this->transaksiRepository->findKeluarById((int) $id);
        if ($row === null) {
            ApiResponse::error('Transaksi tidak ditemukan', 404);
        }
        ApiResponse::json(['success' => true, 'data' => $row]);
    }

    public function deleteKeluar(string $id): void
    {
        try {
            $this->barangService->batalKeluar((int) $id, $this->userId());
            ApiResponse::json(['success' => true, 'message' => 'Transaksi keluar dibatalkan, stok dikembalikan']);
        } catch (ValidationException $e) {
            ApiResponse::error($e->getMessage(), 404);
        }
    }

    private function mapBarangRequest(array $input): BarangRequest
    {
        $request = new BarangRequest();
        $request->kode = $input['kode'] ?? null;
        $request->nama = $input['nama'] ?? null;
        $request->kategoriId = isset($input['kategori_id']) && $input['kategori_id'] !== '' ? (int) $input['kategori_id'] : null;
        $request->satuan = $input['satuan'] ?? null;
        $request->harga = $input['harga'] ?? null;
        $request->stokAwal = $input['stok_awal'] ?? ($input['stok'] ?? null);
        $request->stokMinimum = $input['stok_minimum'] ?? null;
        $request->deskripsi = $input['deskripsi'] ?? null;
        return $request;
    }
}
