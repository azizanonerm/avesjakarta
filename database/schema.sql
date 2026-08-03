-- AVES Jakarta
-- Struktur database awal. Setelah mengimpor file ini, jalankan seluruh migrasi
-- di database/migrations melalui PHP CLI untuk mengisi basis pengetahuan aktif.

CREATE DATABASE IF NOT EXISTS aves_jakarta
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE aves_jakarta;

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS penyakit (
    id_penyakit INT PRIMARY KEY AUTO_INCREMENT,
    kode_penyakit VARCHAR(10) UNIQUE NOT NULL,
    nama_penyakit VARCHAR(100) NOT NULL,
    nama_latin VARCHAR(100),
    kategori VARCHAR(50) NOT NULL DEFAULT 'Lainnya',
    deskripsi TEXT,
    solusi TEXT,
    sumber_referensi VARCHAR(500),
    status_validasi VARCHAR(30) NOT NULL DEFAULT 'perlu_validasi_pakar',
    is_unggulan TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gejala (
    id_gejala INT PRIMARY KEY AUTO_INCREMENT,
    kode_gejala VARCHAR(10) UNIQUE NOT NULL,
    nama_gejala VARCHAR(150) NOT NULL,
    kategori VARCHAR(50) NOT NULL DEFAULT 'Gejala Umum',
    deskripsi TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aturan (
    id_aturan INT PRIMARY KEY AUTO_INCREMENT,
    id_penyakit INT NOT NULL,
    id_gejala INT NOT NULL,
    cf_pakar DECIMAL(3,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_aturan_penyakit
        FOREIGN KEY (id_penyakit) REFERENCES penyakit(id_penyakit)
        ON DELETE CASCADE,
    CONSTRAINT fk_aturan_gejala
        FOREIGN KEY (id_gejala) REFERENCES gejala(id_gejala)
        ON DELETE CASCADE,
    UNIQUE KEY unique_rule (id_penyakit, id_gejala)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS riwayat (
    id_riwayat INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    tanggal TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    penyakit_tertinggi VARCHAR(100),
    nilai_cf DECIMAL(3,2),
    detail_gejala LONGTEXT,
    detail_hasil LONGTEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_riwayat_user
        FOREIGN KEY (id_user) REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_user_date (id_user, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Akun administrator tidak dibuat otomatis agar tidak ada kredensial bawaan.
-- Buat admin melalui scripts/create_admin.php setelah mengatur environment
-- AVES_ADMIN_EMAIL, AVES_ADMIN_NAME, dan AVES_ADMIN_PASSWORD.
