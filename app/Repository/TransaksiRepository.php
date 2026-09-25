<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Repository;

class TransaksiRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function insertMasuk(array $data): array
    {
        $kode = $data['kode_transaksi'] ?? ('BM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)));
        $stmt = $this->connection->prepare(
            "INSERT INTO barang_masuk(kode_transaksi, barang_id, jumlah, harga_beli, tanggal_masuk, supplier_id, keterangan, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id"
        );
        $stmt->execute([
            $kode, $data['barang_id'], $data['jumlah'], $data['harga_beli'] ?? 0,
            $data['tanggal_masuk'] ?? date('Y-m-d'), $data['supplier_id'] ?? null,
            $data['keterangan'] ?? null, $data['created_by'] ?? null
        ]);
        $data['id'] = (int) $stmt->fetchColumn();
        $data['kode_transaksi'] = $kode;
        return $data;
    }

    public function insertKeluar(array $data): array
    {
        $kode = $data['kode_transaksi'] ?? ('BK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)));
        $stmt = $this->connection->prepare(
            "INSERT INTO barang_keluar(kode_transaksi, barang_id, jumlah, tanggal_keluar, penerima, keterangan, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id"
        );
        $stmt->execute([
            $kode, $data['barang_id'], $data['jumlah'],
            $data['tanggal_keluar'] ?? date('Y-m-d'), $data['penerima'] ?? null,
            $data['keterangan'] ?? null, $data['created_by'] ?? null
        ]);
        $data['id'] = (int) $stmt->fetchColumn();
        $data['kode_transaksi'] = $kode;
        return $data;
    }

    public function listMasuk(string $start = '', string $end = '', string $search = ''): array
    {
        $sql = "SELECT m.*, b.nama AS barang_nama, b.kode AS barang_kode, s.nama AS supplier_nama, u.name AS created_by_name
                FROM barang_masuk m JOIN barang b ON b.id = m.barang_id
                LEFT JOIN suppliers s ON s.id = m.supplier_id LEFT JOIN users u ON u.id = m.created_by WHERE 1=1";
        $params = [];
        if ($start !== '') {
            $sql .= " AND m.tanggal_masuk >= ?";
            $params[] = $start;
        }
        if ($end !== '') {
            $sql .= " AND m.tanggal_masuk <= ?";
            $params[] = $end;
        }
        if ($search !== '') {
            $sql .= " AND (b.nama ILIKE ? OR m.kode_transaksi ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY m.tanggal_masuk DESC, m.id DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listKeluar(string $start = '', string $end = '', string $search = ''): array
    {
        $sql = "SELECT k.*, b.nama AS barang_nama, b.kode AS barang_kode, u.name AS created_by_name
                FROM barang_keluar k JOIN barang b ON b.id = k.barang_id
                LEFT JOIN users u ON u.id = k.created_by WHERE 1=1";
        $params = [];
        if ($start !== '') {
            $sql .= " AND k.tanggal_keluar >= ?";
            $params[] = $start;
        }
        if ($end !== '') {
            $sql .= " AND k.tanggal_keluar <= ?";
            $params[] = $end;
        }
        if ($search !== '') {
            $sql .= " AND (b.nama ILIKE ? OR k.kode_transaksi ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY k.tanggal_keluar DESC, k.id DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findMasukById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM barang_masuk WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findKeluarById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM barang_keluar WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deleteMasuk(int $id): void
    {
        $stmt = $this->connection->prepare("DELETE FROM barang_masuk WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function deleteKeluar(int $id): void
    {
        $stmt = $this->connection->prepare("DELETE FROM barang_keluar WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function sumMasuk(string $start = '', string $end = ''): array
    {
        $sql = "SELECT COUNT(*) AS total_transaksi, COALESCE(SUM(jumlah),0) AS total_qty, COALESCE(SUM(jumlah*harga_beli),0) AS total_nilai FROM barang_masuk WHERE 1=1";
        $params = [];
        if ($start !== '') {
            $sql .= " AND tanggal_masuk >= ?";
            $params[] = $start;
        }
        if ($end !== '') {
            $sql .= " AND tanggal_masuk <= ?";
            $params[] = $end;
        }
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function sumKeluar(string $start = '', string $end = ''): array
    {
        $sql = "SELECT COUNT(*) AS total_transaksi, COALESCE(SUM(jumlah),0) AS total_qty FROM barang_keluar WHERE 1=1";
        $params = [];
        if ($start !== '') {
            $sql .= " AND tanggal_keluar >= ?";
            $params[] = $start;
        }
        if ($end !== '') {
            $sql .= " AND tanggal_keluar <= ?";
            $params[] = $end;
        }
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function chart(string $range = '30d'): array
    {
        $days = $range === '7d' ? 7 : ($range === '12m' ? 365 : 30);
        $stmt = $this->connection->prepare(
            "SELECT d::date AS tanggal,
                (SELECT COALESCE(SUM(jumlah),0) FROM barang_masuk WHERE tanggal_masuk = d::date) AS masuk,
                (SELECT COALESCE(SUM(jumlah),0) FROM barang_keluar WHERE tanggal_keluar = d::date) AS keluar
             FROM generate_series(CURRENT_DATE - INTERVAL '" . (int) ($days - 1) . " days', CURRENT_DATE, '1 day') d
             ORDER BY tanggal"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function audit(int $userId, string $aksi, string $tabel, ?int $recordId, ?string $detail): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO audit_logs(user_id, aksi, tabel_name, record_id, detail) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $aksi, $tabel, $recordId, $detail]);
    }

    public function auditList(int $limit = 50): array
    {
        $stmt = $this->connection->query(
            "SELECT a.*, u.email AS user_email FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT " . (int) $limit
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
