-- Kasir Toko Database Schema
CREATE DATABASE IF NOT EXISTS kasir_toko CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kasir_toko;

-- Table: users
-- role = 'admin'  -> pemilik akun toko, akses penuh (Dashboard, Barang, Penjualan, Riwayat, Kelola Pengguna)
-- role = 'staff'  -> akun tambahan di bawah toko yang sama, hanya bisa akses halaman Penjualan
-- owner_id        -> diisi untuk akun staff, menunjuk ke id akun admin pemilik toko
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pengguna VARCHAR(100) NOT NULL,
    nama_toko VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'admin',
    owner_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: barang
CREATE TABLE barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_barang VARCHAR(150) NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    harga_modal DECIMAL(12,2) NOT NULL DEFAULT 0,
    harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: penjualan (transaksi header)
CREATE TABLE penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    uang_dibayar DECIMAL(12,2) NOT NULL DEFAULT 0,
    kembalian DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: penjualan_detail (item per transaksi)
CREATE TABLE penjualan_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penjualan_id INT NOT NULL,
    barang_id INT DEFAULT NULL,
    nama_barang VARCHAR(150) NOT NULL,
    harga_jual DECIMAL(12,2) NOT NULL,
    qty INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (penjualan_id) REFERENCES penjualan(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_barang_user ON barang(user_id);
CREATE INDEX idx_penjualan_user ON penjualan(user_id);
CREATE INDEX idx_penjualan_detail_penjualan ON penjualan_detail(penjualan_id);
CREATE INDEX idx_users_owner ON users(owner_id);

-- Jika database lama sudah ada (dibuat sebelum fitur akun staff ditambahkan),
-- jalankan dua baris ini secara manual untuk migrasi tanpa menghapus data:
-- ALTER TABLE users ADD COLUMN role ENUM('admin','staff') NOT NULL DEFAULT 'admin' AFTER password;
-- ALTER TABLE users ADD COLUMN owner_id INT DEFAULT NULL AFTER role, ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE;
