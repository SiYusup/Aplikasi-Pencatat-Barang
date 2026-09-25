<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Domain;

class Barang
{
    public ?int $id = null;
    public string $kode = '';
    public string $nama = '';
    public ?int $kategoriId = null;
    public ?string $kategoriNama = null;
    public string $satuan = 'pcs';
    public float $harga = 0;
    public int $stok = 0;
    public int $stokMinimum = 5;
    public ?string $deskripsi = null;
    public ?string $photoUrl = null;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kategori_id' => $this->kategoriId,
            'kategori' => $this->kategoriId ? ['id' => $this->kategoriId, 'nama' => $this->kategoriNama] : null,
            'satuan' => $this->satuan,
            'harga' => (float) $this->harga,
            'stok' => $this->stok,
            'stok_minimum' => $this->stokMinimum,
            'is_low_stock' => $this->stok <= $this->stokMinimum,
            'deskripsi' => $this->deskripsi,
            'photo_url' => $this->photoUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
