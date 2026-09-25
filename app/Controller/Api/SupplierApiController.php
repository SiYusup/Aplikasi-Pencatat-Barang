<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Controller\Api;

use UCrazy\AplikasiPencatatBarangCrud\App\ApiResponse;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\TransaksiRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;

class SupplierApiController
{
    private \PDO $connection;
    private TransaksiRepository $transaksiRepository;
    private SessionService $sessionService;

    public function __construct()
    {
        $this->connection = Database::getConnection();
        $this->transaksiRepository = new TransaksiRepository($this->connection);
        $this->sessionService = new SessionService(
            new SessionRepository($this->connection),
            new UserRepository($this->connection)
        );
    }

    private function userId(): int
    {
        return $this->sessionService->current()->id;
    }

    private function withCount(array $row): array
    {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM barang_masuk WHERE supplier_id = ?");
        $stmt->execute([$row['id']]);
        $row['jumlah_transaksi'] = (int) $stmt->fetchColumn();
        return $row;
    }

    public function list(): void
    {
        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $stmt = $this->connection->prepare("SELECT * FROM suppliers WHERE nama ILIKE ? ORDER BY nama");
            $stmt->execute(["%$search%"]);
        } else {
            $stmt = $this->connection->query("SELECT * FROM suppliers ORDER BY nama");
        }
        $rows = array_map([$this, 'withCount'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
        ApiResponse::json(['success' => true, 'data' => $rows]);
    }

    public function show(string $id): void
    {
        $stmt = $this->connection->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            ApiResponse::error('Supplier tidak ditemukan', 404);
        }
        ApiResponse::json(['success' => true, 'data' => $this->withCount($row)]);
    }

    public function create(): void
    {
        $input = ApiResponse::input();
        $nama = trim($input['nama'] ?? '');
        if (strlen($nama) < 2) {
            ApiResponse::error('Nama supplier minimal 2 karakter', 422);
        }
        $stmt = $this->connection->prepare("INSERT INTO suppliers(nama, kontak, alamat) VALUES (?, ?, ?) RETURNING id, nama, kontak, alamat");
        $stmt->execute([$nama, $input['kontak'] ?? null, $input['alamat'] ?? null]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->transaksiRepository->audit($this->userId(), 'create_supplier', 'suppliers', (int) $row['id'], "Tambah supplier $nama");
        ApiResponse::json(['success' => true, 'message' => 'Supplier berhasil ditambahkan', 'data' => $this->withCount($row)], 201);
    }

    public function update(string $id): void
    {
        $stmt = $this->connection->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            ApiResponse::error('Supplier tidak ditemukan', 404);
        }
        $input = ApiResponse::input();
        $nama = trim($input['nama'] ?? $row['nama']);
        if (strlen($nama) < 2) {
            ApiResponse::error('Nama supplier minimal 2 karakter', 422);
        }
        $kontak = array_key_exists('kontak', $input) ? $input['kontak'] : $row['kontak'];
        $alamat = array_key_exists('alamat', $input) ? $input['alamat'] : $row['alamat'];
        $upd = $this->connection->prepare("UPDATE suppliers SET nama = ?, kontak = ?, alamat = ? WHERE id = ? RETURNING id, nama, kontak, alamat");
        $upd->execute([$nama, $kontak, $alamat, (int) $id]);
        $updated = $upd->fetch(\PDO::FETCH_ASSOC);
        $this->transaksiRepository->audit($this->userId(), 'update_supplier', 'suppliers', (int) $id, "Update supplier $nama");
        ApiResponse::json(['success' => true, 'message' => 'Supplier berhasil diupdate', 'data' => $this->withCount($updated)]);
    }

    public function delete(string $id): void
    {
        $stmt = $this->connection->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            ApiResponse::error('Supplier tidak ditemukan', 404);
        }
        // Transaksi masuk terkait ikut SET NULL via FK ON DELETE SET NULL
        $del = $this->connection->prepare("DELETE FROM suppliers WHERE id = ?");
        $del->execute([(int) $id]);
        $this->transaksiRepository->audit($this->userId(), 'delete_supplier', 'suppliers', (int) $id, "Hapus supplier {$row['nama']}");
        ApiResponse::json(['success' => true, 'message' => 'Supplier berhasil dihapus']);
    }
}
