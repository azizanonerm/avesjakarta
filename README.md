# AVES — Avian & Exotic Animal Veterinary Services Jakarta

Sistem pakar diagnosis awal penyakit kulit burung menggunakan metode
Certainty Factor. Basis pengetahuan aktif terdiri dari 30 penyakit, 60 gejala,
dan 120 relasi CF pakar.

## Struktur proyek

```text
Skripsi2/
|-- admin/                         Halaman dan fitur administrator
|   |-- assets/                    Tema antarmuka admin
|   |-- includes/                  Komponen laporan dan pembuat PDF
|   |-- laporan/                   Empat jenis laporan
|   |-- dashboard.php              Dashboard administrator
|   |-- penyakit.php               Pengelolaan penyakit
|   |-- gejala.php                 Pengelolaan gejala
|   |-- aturan.php                 Pengelolaan rule dan CF pakar
|   |-- tambah_penyakit.php        Form penyakit beserta gejala
|   `-- riwayat_diagnosis.php      Riwayat diagnosis seluruh pengguna
|-- assets/                        CSS halaman pengguna
|-- config/                        Koneksi, keamanan, dan pengaturan aplikasi
|-- database/                      Seluruh kebutuhan database
|   |-- data/                      Data yang dipakai oleh migrasi
|   |-- migrations/                Pengisian dan pembaruan basis pengetahuan
|   `-- schema.sql                 Struktur database awal
|-- includes/                      Tombol kembali dan modal konfirmasi keluar
|-- lib/                           Implementasi algoritma Certainty Factor
|-- scripts/                       Utilitas administrasi khusus CLI
|-- tests/                         Pengujian CLI
|-- index.html                     Halaman utama aplikasi
|-- konsultasi.php                 Pemilihan gejala
|-- proses_diagnosa.php            Proses perhitungan dan penyimpanan hasil
|-- hasil_diagnosa.php             Hasil diagnosis terkini
|-- riwayat.php                    Riwayat diagnosis pengguna
|-- detail-riwayat.php             Detail satu riwayat diagnosis
`-- profile.php                    Profil pengguna
```

## Instalasi

1. Pastikan Apache dan MySQL XAMPP aktif.
2. Impor `database/schema.sql` melalui phpMyAdmin.
3. Atur `AVES_APP_ENV=production` pada server publik, lalu isi koneksi
   database melalui environment `AVES_DB_HOST`,
   `AVES_DB_USER`, `AVES_DB_PASS`, dan `AVES_DB_NAME`.
4. Isi basis pengetahuan aktif melalui terminal:

```powershell
C:\xampp\php\php.exe database\migrations\20260723_align_chapter3.php
C:\xampp\php\php.exe database\migrations\20260724_update_disease_descriptions.php
C:\xampp\php\php.exe database\migrations\20260725_update_disease_solutions.php
C:\xampp\php\php.exe database\migrations\20260729_add_disease_latin_names.php
```

5. Buat administrator dengan mengatur `AVES_ADMIN_EMAIL`,
   `AVES_ADMIN_NAME`, dan `AVES_ADMIN_PASSWORD`, lalu jalankan:

```powershell
C:\xampp\php\php.exe scripts\create_admin.php
```

6. Hapus nilai environment `AVES_ADMIN_PASSWORD` setelah akun berhasil dibuat.
7. Buka `http://localhost/Skripsi2/`.

## Alur diagnosis

1. Pengguna memilih gejala dan tingkat keyakinan diskrit.
2. Sistem menghitung `CF gejala = CF pakar x CF pengguna`.
3. Nilai positif digabungkan dengan rumus:
   `CF combine = CF1 + CF2 x (1 - CF1)`.
4. Sistem menyimpan satu penyakit dengan nilai CF akhir tertinggi.
5. Hasil dapat dilihat kembali melalui riwayat dan dicetak sebagai PDF.

## Laporan

Administrator memiliki empat laporan:

- Data penyakit
- Data gejala
- Basis pengetahuan atau rule base
- Hasil diagnosis

Setiap laporan dapat dicetak atau diunduh sebagai PDF dan menyediakan area
nama serta tanda tangan administrator.

## Pengujian

Jalankan pemeriksaan profil seluruh basis pengetahuan:

```powershell
C:\xampp\php\php.exe tests\cf_profile_test.php
```

Pengujian memverifikasi jumlah 30 penyakit, 60 gejala, 120 aturan, empat relasi
per penyakit, dan hasil perhitungan CF.

## Catatan keamanan

- Query database menggunakan prepared statement PDO.
- Cookie sesi memakai HttpOnly, SameSite, strict mode, batas waktu, dan
  regenerasi ID setelah login.
- Formulir perubahan data dilindungi token CSRF.
- Halaman admin dan laporan internal dilindungi pemeriksaan role.
- File konfigurasi, SQL, migrasi, dokumentasi, dan pengujian diblokir dari URL.
- Kredensial database dan administrator dapat diambil dari environment.
- Migrasi dan pengujian hanya dapat dijalankan melalui CLI.
- Hasil sistem merupakan bantuan diagnosis awal dan tetap memerlukan
  pemeriksaan dokter hewan.
