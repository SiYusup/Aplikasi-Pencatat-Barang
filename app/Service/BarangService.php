<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Service;

use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Domain\Barang;
use UCrazy\AplikasiPencatatBarangCrud\Exception\ValidationException;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangKeluarRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangMasukRequest;
use UCrazy\AplikasiPencatatBarangCrud\Model\BarangRequest;
use UCrazy\AplikasiPencatatBarangCrud\Repository\BarangRepository;
use UCrazy\AplikasiPencatatBarangCrud\Repository\TransaksiRepository;

class BarangService
{
    private BarangRepository $barangRepository;
    private TransaksiRepository $transaksiRepository;

    public function __construct(BarangRepository $barangRepository, TransaksiRepository $transaksiRepository)
    {
        $this->barangRepository = $barangRepository;
        $this->transaksiRepository = $transaksiRepository;
    }

    private function generateKode(): string
    {
        return 'BRG-' . strtoupper(substr(uniqid(), -6));
    }

    public function create(BarangRequest $request, int $userId): Barang
    {
        if ($request->nama === null || trim($request->nama) === '') {
            throw new ValidationException("Nama barang tidak boleh kosong");
        }
        if ($request->satuan === null || trim($request->satuan) === '') {
            throw new ValidationException("Satuan tidak boleh kosong");
        }
        $kode = $request->kode ? strtoupper(trim($request->kode)) : $this->generateKode();
        try {
            Database::beginTransaction();
            if ($this->barangRepository->findByKode($kode) !== null) {
                throw new ValidationException("Kode barang sudah dipakai");
            }
            $barang = new Barang();
            $barang->kode = $kode;
            $barang->nama = trim($request->nama);
            $barang->kategoriId = $request->kategoriId;
            $barang->satuan = trim($request->satuan);
            $barang->harga = (float) ($request->harga ?? 0);
            $barang->stok = (int) ($request->stokAwal ?? 0);
            $barang->stokMinimum = (int) ($request->stokMinimum ?? 5);
            $barang->deskripsi = $request->deskripsi;
            $this->barangRepository->save($barang);
            $this->transaksiRepository->audit($userId, 'create_barang', 'barang', $barang->id, "Tambah barang {$barang->kode}");
            Database::commitTransaction();
            return $this->barangRepository->findById($barang->id);
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function update(int $id, BarangRequest $request, int $userId): Barang
    {
        try {
            Database::beginTransaction();
            $barang = $this->barangRepository->findById($id);
            if ($barang === null) {
                throw new ValidationException("Barang tidak ditemukan");
            }
            if ($request->kode && strtoupper($request->kode) !== $barang->kode) {
                if ($this->barangRepository->findByKode(strtoupper($request->kode)) !== null) {
                    throw new ValidationException("Kode barang sudah dipakai");
                }
                $barang->kode = strtoupper(trim($request->kode));
            }
            if ($request->nama !== null && trim($request->nama) !== '') {
                $barang->nama = trim($request->nama);
            }
            if ($request->kategoriId !== null) {
                $barang->kategoriId = $request->kategoriId;
            }
            if ($request->satuan !== null && trim($request->satuan) !== '') {
                $barang->satuan = trim($request->satuan);
            }
            if ($request->harga !== null) {
                $barang->harga = (float) $request->harga;
            }
            if ($request->stokMinimum !== null) {
                $barang->stokMinimum = (int) $request->stokMinimum;
            }
            if ($request->deskripsi !== null) {
                $barang->deskripsi = $request->deskripsi;
            }
            $this->barangRepository->update($barang);
            $this->transaksiRepository->audit($userId, 'update_barang', 'barang', $id, "Update barang {$barang->kode}");
            Database::commitTransaction();
            return $this->barangRepository->findById($id);
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function delete(int $id, int $userId): void
    {
        try {
            Database::beginTransaction();
            $barang = $this->barangRepository->findById($id);
            if ($barang === null) {
                throw new ValidationException("Barang tidak ditemukan");
            }
            $this->barangRepository->deleteById($id);
            $this->transaksiRepository->audit($userId, 'delete_barang', 'barang', $id, "Hapus barang {$barang->kode}");
            Database::commitTransaction();
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function masuk(BarangMasukRequest $request, int $userId): array
    {
        $items = $request->items ?? [];
        if (!empty($items)) {
            $results = [];
            foreach ($items as $item) {
                $single = new BarangMasukRequest();
                $single->barangId = (int) ($item['barang_id'] ?? 0);
                $single->jumlah = $item['jumlah'] ?? 0;
                $single->hargaBeli = $item['harga_beli'] ?? 0;
                $single->tanggalMasuk = $request->tanggalMasuk;
                $single->supplierId = $request->supplierId ?? ($item['supplier_id'] ?? null);
                $single->keterangan = $request->keterangan ?? ($item['keterangan'] ?? null);
                $results[] = $this->masukSingle($single, $userId);
            }
            return $results;
        }
        return [$this->masukSingle($request, $userId)];
    }

    private function masukSingle(BarangMasukRequest $request, int $userId): array
    {
        if (!$request->barangId || (int) $request->jumlah < 1) {
            throw new ValidationException("barang_id & jumlah (min 1) wajib diisi");
        }
        try {
            Database::beginTransaction();
            $barang = $this->barangRepository->findById($request->barangId);
            if ($barang === null) {
                throw new ValidationException("Barang tidak ditemukan");
            }
            $row = $this->transaksiRepository->insertMasuk([
                'barang_id' => $barang->id,
                'jumlah' => (int) $request->jumlah,
                'harga_beli' => (float) ($request->hargaBeli ?? $barang->harga),
                'tanggal_masuk' => $request->tanggalMasuk ?: date('Y-m-d'),
                'supplier_id' => $request->supplierId,
                'keterangan' => $request->keterangan,
                'created_by' => $userId,
            ]);
            $this->barangRepository->updateStok($barang->id, $barang->stok + (int) $request->jumlah);
            $this->transaksiRepository->audit($userId, 'create_barang_masuk', 'barang_masuk', $row['id'], "Masuk {$request->jumlah} x {$barang->kode}");
            Database::commitTransaction();
            $row['barang'] = $this->barangRepository->findById($barang->id)->toArray();
            return $row;
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function keluar(BarangKeluarRequest $request, int $userId): array
    {
        if (!$request->barangId || (int) $request->jumlah < 1) {
            throw new ValidationException("barang_id & jumlah (min 1) wajib diisi");
        }
        try {
            Database::beginTransaction();
            $barang = $this->barangRepository->findById($request->barangId);
            if ($barang === null) {
                throw new ValidationException("Barang tidak ditemukan");
            }
            if ($barang->stok < (int) $request->jumlah) {
                throw new ValidationException("Stok tidak mencukupi (sisa {$barang->stok}, diminta {$request->jumlah})");
            }
            $row = $this->transaksiRepository->insertKeluar([
                'barang_id' => $barang->id,
                'jumlah' => (int) $request->jumlah,
                'tanggal_keluar' => $request->tanggalKeluar ?: date('Y-m-d'),
                'penerima' => $request->penerima,
                'keterangan' => $request->keterangan,
                'created_by' => $userId,
            ]);
            $this->barangRepository->updateStok($barang->id, $barang->stok - (int) $request->jumlah);
            $this->transaksiRepository->audit($userId, 'create_barang_keluar', 'barang_keluar', $row['id'], "Keluar {$request->jumlah} x {$barang->kode}");
            Database::commitTransaction();
            $row['barang'] = $this->barangRepository->findById($barang->id)->toArray();
            return $row;
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function batalMasuk(int $id, int $userId): void
    {
        try {
            Database::beginTransaction();
            $trx = $this->transaksiRepository->findMasukById($id);
            if ($trx === null) {
                throw new ValidationException("Transaksi masuk tidak ditemukan");
            }
            $barang = $this->barangRepository->findById((int) $trx['barang_id']);
            $this->transaksiRepository->deleteMasuk($id);
            if ($barang) {
                $this->barangRepository->updateStok($barang->id, max(0, $barang->stok - (int) $trx['jumlah']));
            }
            $this->transaksiRepository->audit($userId, 'delete_barang_masuk', 'barang_masuk', $id, "Batal masuk {$trx['kode_transaksi']}");
            Database::commitTransaction();
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function batalKeluar(int $id, int $userId): void
    {
        try {
            Database::beginTransaction();
            $trx = $this->transaksiRepository->findKeluarById($id);
            if ($trx === null) {
                throw new ValidationException("Transaksi keluar tidak ditemukan");
            }
            $barang = $this->barangRepository->findById((int) $trx['barang_id']);
            $this->transaksiRepository->deleteKeluar($id);
            if ($barang) {
                $this->barangRepository->updateStok($barang->id, $barang->stok + (int) $trx['jumlah']);
            }
            $this->transaksiRepository->audit($userId, 'delete_barang_keluar', 'barang_keluar', $id, "Batal keluar {$trx['kode_transaksi']}");
            Database::commitTransaction();
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }

    public function opname(int $id, int $stokFisik, string $keterangan, int $userId): Barang
    {
        if ($stokFisik < 0) {
            throw new ValidationException("Stok fisik tidak boleh negatif");
        }
        try {
            Database::beginTransaction();
            $barang = $this->barangRepository->findById($id);
            if ($barang === null) {
                throw new ValidationException("Barang tidak ditemukan");
            }
            $selisih = $stokFisik - $barang->stok;
            $this->barangRepository->updateStok($id, $stokFisik);
            $this->transaksiRepository->audit($userId, 'opname', 'barang', $id, "Opname {$barang->kode}: sistem {$barang->stok} -> fisik $stokFisik (selisih $selisih). $keterangan");
            Database::commitTransaction();
            return $this->barangRepository->findById($id);
        } catch (\Exception $e) {
            Database::rollbackTransaction();
            throw $e;
        }
    }
}
