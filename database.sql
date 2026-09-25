-- Aplikasi Pencatat Barang — PostgreSQL DDL
-- Buat database manual (sekali saja):
--   CREATE DATABASE pencatat_barang;
--   CREATE DATABASE pencatat_barang_test;

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'staff',
    avatar_url TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS kategoris (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(255) NOT NULL UNIQUE,
    deskripsi TEXT NULL
);

CREATE TABLE IF NOT EXISTS suppliers (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    kontak VARCHAR(100) NULL,
    alamat TEXT NULL
);

CREATE TABLE IF NOT EXISTS barang (
    id SERIAL PRIMARY KEY,
    kode VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(255) NOT NULL,
    kategori_id INT NULL REFERENCES kategoris(id) ON DELETE SET NULL,
    satuan VARCHAR(50) NOT NULL DEFAULT 'pcs',
    harga NUMERIC(15,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    stok_minimum INT NOT NULL DEFAULT 5,
    deskripsi TEXT NULL,
    photo_url TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS barang_masuk (
    id SERIAL PRIMARY KEY,
    kode_transaksi VARCHAR(50) NOT NULL UNIQUE,
    barang_id INT NOT NULL REFERENCES barang(id) ON DELETE CASCADE,
    jumlah INT NOT NULL CHECK (jumlah > 0),
    harga_beli NUMERIC(15,2) NOT NULL DEFAULT 0,
    tanggal_masuk DATE NOT NULL DEFAULT CURRENT_DATE,
    supplier_id INT NULL REFERENCES suppliers(id) ON DELETE SET NULL,
    keterangan TEXT NULL,
    created_by INT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS barang_keluar (
    id SERIAL PRIMARY KEY,
    kode_transaksi VARCHAR(50) NOT NULL UNIQUE,
    barang_id INT NOT NULL REFERENCES barang(id) ON DELETE CASCADE,
    jumlah INT NOT NULL CHECK (jumlah > 0),
    tanggal_keluar DATE NOT NULL DEFAULT CURRENT_DATE,
    penerima VARCHAR(255) NULL,
    keterangan TEXT NULL,
    created_by INT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INT NULL REFERENCES users(id) ON DELETE SET NULL,
    aksi VARCHAR(100) NOT NULL,
    tabel_name VARCHAR(100) NOT NULL,
    record_id INT NULL,
    detail TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed kategori & admin default (password: admin123)
INSERT INTO kategoris (nama, deskripsi) VALUES
    ('Elektronik', 'Barang elektronik kantor'),
    ('ATK', 'Alat tulis kantor'),
    ('Umum', 'Barang umum')
ON CONFLICT (nama) DO NOTHING;
