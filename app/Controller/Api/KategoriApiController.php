<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Controller\Api;

use UCrazy\AplikasiPencatatBarangCrud\App\ApiResponse;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Repository\SessionRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\TransaksiRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\UserRepository;
use UCrazy\AplikasiPencatatBarangCrud\Service\SessionService;

class KategoriApiController
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
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM barang WHERE kategori_id = ?");
        $stmt->execute([$row['id']]);
        $row['jumlah_barang'] = (int) $stmt->fetchColumn();
        return $row;
    }

    public function list(): void
    {
        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $stmt = $this->connection->prepare("SELECT * FROM kategoris WHERE nama ILIKE ? ORDER BY nama");
            $stmt->execute(["%$search%"]);
        } else {
            $stmt = $this->connection->query("SELECT * FROM kategoris ORDER BY nama");
        }
        $rows = array_map([$this, 'withCount'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
        ApiResponse::json(['success' => true, 'data' => $rows]);
    }

    public function show(string $id): void
    {
        $stmt = $this->connection->prepare("SELECT * FROM kategoris WHERE id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            ApiResponse::error('Kategori tidak ditemukan', 404);
        }
        ApiResponse::json(['success' => true, 'data' => $this->withCount($row)]);
    }

    public function create(): void
    {
        $input = ApiResponse::input();
        $nama = trim($input['nama'] ?? '');
        if (strlen($nama) < 2) {
            ApiResponse::error('Nama kategori minimal 2 karakter', 422);
        }
        $stmt = $this->connection->prepare("INSERT INTO kategoris(nama, deskripsi) VALUES (?, ?) RETURNING id, nama, deskripsi");
        try {
            $stmt->execute([$nama, $input['deskripsi'] ?? null]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->transaksiRepository->audit($this->userId(), 'create_kategori', 'kategoris', (int) $row['id'], "Tambah kategori $nama");
            ApiResponse::json(['success' => true, 'message' => 'Kategori berhasil ditambahkan', 'data' => $this->withCount($row)], 201);
        } catch (\PDOException) {
            ApiResponse::error('Kategori sudah ada', 422);
        }
    }

    public function update(string $id): void
    {
        $stmt = $this->connection->prepare("SELECT * FROM kategoris WHERE id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            ApiResponse::error('Kategori tidak ditemukan', 404);
        }
        $input = ApiResponse::input();
        $nama = trim($input['nama'] ?? $row['nama']);
        if (strlen($nama) < 2) {
            ApiResponse::error('Nama kategori minimal 2 karakter', 422);
        }
        $deskripsi = array_key_exists('deskripsi', $input) ? $input['deskripsi'] : $row['deskripsi'];
        try {
            $upd = $this->connection->prepare("UPDATE kategoris SET nama = ?, deskripsi = ? WHERE id = ? RETURNING id, nama, deskripsi");
            $upd->execute([$nama, $deskripsi, (int) $id]);
            $updated = $upd->fetch(\PDO::FETCH_ASSOC);
            $this->transaksiRepository->audit($this->userId(), 'update_kategori', 'kategoris', (int) $id, "Update kategori $nama");
            ApiResponse::json(['success' => true, 'message' => 'Kategori berhasil diupdate', 'data' => $this->withCount($updated)]);
        } catch (\PDOException) {
            ApiResponse::error('Nama kategori sudah dipakai', 422);
        }
    }

    public function delete(string $id): void
    {
        $stmt = $this->connection->prepare("SELECT * FROM kategoris WHERE id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            ApiResponse::error('Kategori tidak ditemukan', 404);
        }
        // Barang terkait ikut SET NULL via FK ON DELETE SET NULL
        $del = $this->connection->prepare("DELETE FROM kategoris WHERE id = ?");
        $del->execute([(int) $id]);
        $this->transaksiRepository->audit($this->userId(), 'delete_kategori', 'kategoris', (int) $id, "Hapus kategori {$row['nama']}");
        ApiResponse::json(['success' => true, 'message' => 'Kategori berhasil dihapus']);
    }
}
