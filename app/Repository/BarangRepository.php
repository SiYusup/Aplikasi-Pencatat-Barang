<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Repository;

use UCrazy\AplikasiPencatatBarangCrud\Domain\Barang;

class BarangRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function save(Barang $barang): Barang
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO barang(kode, nama, kategori_id, satuan, harga, stok, stok_minimum, deskripsi, photo_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id"
        );
        $stmt->execute([
            $barang->kode, $barang->nama, $barang->kategoriId, $barang->satuan,
            $barang->harga, $barang->stok, $barang->stokMinimum, $barang->deskripsi, $barang->photoUrl
        ]);
        $barang->id = (int) $stmt->fetchColumn();
        return $barang;
    }

    public function update(Barang $barang): Barang
    {
        $stmt = $this->connection->prepare(
            "UPDATE barang SET kode=?, nama=?, kategori_id=?, satuan=?, harga=?, stok=?, stok_minimum=?, deskripsi=?, photo_url=?, updated_at=CURRENT_TIMESTAMP WHERE id=?"
        );
        $stmt->execute([
            $barang->kode, $barang->nama, $barang->kategoriId, $barang->satuan,
            $barang->harga, $barang->stok, $barang->stokMinimum, $barang->deskripsi, $barang->photoUrl, $barang->id
        ]);
        return $barang;
    }

    public function updateStok(int $id, int $stok): void
    {
        $stmt = $this->connection->prepare("UPDATE barang SET stok=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->execute([$stok, $id]);
    }

    public function findById(int $id): ?Barang
    {
        $stmt = $this->connection->prepare(
            "SELECT b.*, k.nama AS kategori_nama FROM barang b LEFT JOIN kategoris k ON k.id = b.kategori_id WHERE b.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->map($row) : null;
    }

    public function findByKode(string $kode): ?Barang
    {
        $stmt = $this->connection->prepare("SELECT b.*, k.nama AS kategori_nama FROM barang b LEFT JOIN kategoris k ON k.id = b.kategori_id WHERE b.kode = ?");
        $stmt->execute([$kode]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->map($row) : null;
    }

    /** @return Barang[] */
    public function findAll(string $search = '', ?int $kategoriId = null, bool $onlyLowStock = false, string $sortBy = 'created_at', string $sortDir = 'DESC'): array
    {
        $allowedSort = ['nama' => 'b.nama', 'stok' => 'b.stok', 'harga' => 'b.harga', 'created_at' => 'b.created_at'];
        $order = ($allowedSort[$sortBy] ?? 'b.created_at') . ' ' . ($sortDir === 'asc' ? 'ASC' : 'DESC');
        $sql = "SELECT b.*, k.nama AS kategori_nama FROM barang b LEFT JOIN kategoris k ON k.id = b.kategori_id WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (b.nama ILIKE ? OR b.kode ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($kategoriId) {
            $sql .= " AND b.kategori_id = ?";
            $params[] = $kategoriId;
        }
        if ($onlyLowStock) {
            $sql .= " AND b.stok <= b.stok_minimum";
        }
        $sql .= " ORDER BY $order";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'map'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function countLowStock(): int
    {
        return (int) $this->connection->query("SELECT COUNT(*) FROM barang WHERE stok <= stok_minimum")->fetchColumn();
    }

    public function summary(): array
    {
        $row = $this->connection->query(
            "SELECT COUNT(*) AS total_barang, COALESCE(SUM(stok),0) AS total_stok, COALESCE(SUM(stok*harga),0) AS nilai FROM barang"
        )->fetch(\PDO::FETCH_ASSOC);
        return [
            'total_barang' => (int) $row['total_barang'],
            'total_stok' => (int) $row['total_stok'],
            'nilai_persediaan' => (float) $row['nilai'],
        ];
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->connection->prepare("DELETE FROM barang WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function deleteAll(): void
    {
        $this->connection->exec("DELETE FROM barang_keluar");
        $this->connection->exec("DELETE FROM barang_masuk");
        $this->connection->exec("DELETE FROM barang");
    }

    private function map(array $row): Barang
    {
        $b = new Barang();
        $b->id = (int) $row['id'];
        $b->kode = $row['kode'];
        $b->nama = $row['nama'];
        $b->kategoriId = $row['kategori_id'] !== null ? (int) $row['kategori_id'] : null;
        $b->kategoriNama = $row['kategori_nama'] ?? null;
        $b->satuan = $row['satuan'];
        $b->harga = (float) $row['harga'];
        $b->stok = (int) $row['stok'];
        $b->stokMinimum = (int) $row['stok_minimum'];
        $b->deskripsi = $row['deskripsi'] ?? null;
        $b->photoUrl = $row['photo_url'] ?? null;
        $b->createdAt = $row['created_at'] ?? null;
        $b->updatedAt = $row['updated_at'] ?? null;
        return $b;
    }
}
