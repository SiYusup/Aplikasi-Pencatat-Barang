<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Model;

class UserRegisterRequest
{
    public ?string $name = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $role = null;
}

class UserLoginRequest
{
    public ?string $email = null;
    public ?string $password = null;
}

class UserProfileUpdateRequest
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
}

class UserPasswordUpdateRequest
{
    public ?int $id = null;
    public ?string $oldPassword = null;
    public ?string $newPassword = null;
}

class BarangRequest
{
    public ?string $kode = null;
    public ?string $nama = null;
    public ?int $kategoriId = null;
    public ?string $satuan = null;
    public mixed $harga = null;
    public mixed $stokAwal = null;
    public mixed $stokMinimum = null;
    public ?string $deskripsi = null;
}

class BarangMasukRequest
{
    public ?int $barangId = null;
    public mixed $jumlah = null;
    public mixed $hargaBeli = null;
    public ?string $tanggalMasuk = null;
    public ?int $supplierId = null;
    public ?string $keterangan = null;
    /** @var array|null untuk input banyak barang sekaligus */
    public ?array $items = null;
}

class BarangKeluarRequest
{
    public ?int $barangId = null;
    public mixed $jumlah = null;
    public ?string $tanggalKeluar = null;
    public ?string $penerima = null;
    public ?string $keterangan = null;
}
