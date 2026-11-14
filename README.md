# CareCampus - Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus

**CareCampus** adalah aplikasi web sistem informasi pengaduan dan pelaporan kerusakan fasilitas kampus berbasis PHP & MySQL. Aplikasi ini dikembangkan oleh **Putri Alya** untuk mempermudah mahasiswa dalam melaporkan berbagai permasalahan fasilitas fisik di lingkungan kampus (seperti AC rusak, toilet kotor/bau, lampu mati, proyektor bermasalah, kursi rusak, kebocoran air, kebersihan ruangan, dll.) serta membantu pihak pengelola sarana & prasarana (Sarpras) kampus dalam memantau, memverifikasi, dan menindaklanjuti setiap laporan secara terstruktur dan transparan.

---

## 🎨 Konsep dan Desain Tampilan

CareCampus dirancang dengan prinsip **sederhana, bersih, modern, dan bernafas lega (airy spacing)**:
- **Background Utama**: Putih tulang (`#F8F9F7`)
- **Warna Utama**: Abu-abu kebiruan slate (`#64748B`)
- **Warna Pendukung**: Sage green lembut (`#A3B5A0`)
- **Warna Teks**: Abu-abu gelap kontras nyaman (`#374151`)
- **Warna Card**: Putih bersih (`#FFFFFF`)
- **Border**: Abu-abu muda halus (`#E5E7EB`)
- **Tipografi**: Google Fonts (*Poppins* & *Inter*)
- **Responsif**: Nyaman diakses melalui laptop, tablet, maupun smartphone.

---

## 🚀 Fitur Unggulan

### 1. Modul Mahasiswa
- **Dashboard Ringkas**: Sapaan pengguna, kartu statistik jumlah pengaduan (Total, Menunggu, Diproses, Selesai), dan tabel 5 pengaduan terbaru.
- **Formulir Pengaduan Cepat**:
  - Judul pengaduan.
  - Pilihan 8 kategori fasilitas resmi (AC, toilet, lampu, proyektor, meja/kursi, kebocoran, kebersihan, fasilitas lainnya).
  - Lokasi gedung & detail lantai/nomor ruangan.
  - Deskripsi permasalahan rinci.
  - Upload foto bukti pendukung (validasi format JPG/PNG/WEBP, maksimal 3 MB, preview langsung sebelum kirim).
  - Penomoran laporan otomatis (format: `CC-YYYYMM-XXXX`).
- **Riwayat Pengaduan**:
  - Filter status (Semua, Menunggu, Diproses, Selesai, Ditolak) dengan counter badge.
  - Pencarian real-time berdasarkan nomor laporan, judul, atau lokasi.
  - Filter berdasarkan kategori fasilitas.
- **Detail Laporan & Alur Status**:
  - Visual status progress bar (Menunggu ➔ Diproses ➔ Selesai / Ditolak).
  - Keterangan tindak lanjut dan nama teknisi penangan dari pengelola sarpras.
  - Riwayat linimasa (activity log) setiap terjadi perubahan status.
  - Fitur cetak bukti pengaduan (*print-friendly*).
- **Profil Mahasiswa**: Ubah nama, program studi, email, no. telp, dan perbarui kata sandi.

### 2. Modul Admin (Pengelola Sarpras)
- **Dashboard Eksekutif**: Ringkasan total laporan masuk, antrean verifikasi, laporan dalam pengerjaan, laporan tuntas, grafik sebaran kategori kerusakan, dan total mahasiswa aktif.
- **Kelola Seluruh Pengaduan**:
  - Pencarian multi-kriteria (No. Laporan, judul, nama pelapor, NIM).
  - Filter berdasarkan status, kategori, dan rentang tanggal masuk.
  - Perubahan status laporan (Menunggu ➔ Diproses ➔ Selesai ➔ Ditolak).
  - Pengisian catatan tindak lanjut dan penugasan teknisi/petugas penangan.
- **Rekapitulasi & Cetak Dokumen Resmi**:
  - Rekapitulasi laporan pengaduan berformat kop dinas resmi kampus.
  - Filter periode tanggal dan status.
  - Format siap cetak (*print-friendly*) dilengkapi kolom tanda tangan penanggung jawab sarpras.
- **Manajemen Pengguna**:
  - Daftar mahasiswa dan staf admin.
  - Tambah pengguna baru.
  - Fitur reset kata sandi pengguna.
  - Hapus akun dengan konfirmasi.
- **Profil Admin**: Ubah informasi instansi dan perbarui kata sandi administrator.

---

## 🔑 Kredensial Akun Demo

Untuk memudahkan demonstrasi dan pengujian, sistem telah dilengkapi tombol **Login Cepat Demo** di halaman login:

| Peran (Role) | Email / Username | NIM / Identitas | Kata Sandi | Deskripsi |
|---|---|---|---|---|
| **Administrator** | `admin@carecampus.ac.id` | `ADM-001` | `admin123` | Pengelola Sarana & Prasarana Kampus |
| **Mahasiswa 1** | `putri@mahasiswa.ac.id` | `202401001` | `mahasiswa123` | Putri Alya (Pemilik Proyek) |
| **Mahasiswa 2** | `budi@mahasiswa.ac.id` | `202401002` | `mahasiswa123` | Budi Santoso (Mahasiswa) |

> Mahasiswa juga dapat mendaftar akun baru mandiri melalui menu **Daftar Mahasiswa di sini** pada halaman login.

---

## 📁 Struktur Direktori

```
c:\xampp\htdocs\PutriPPL\
├── admin/                     # Modul Pengelola Sarpras
│   ├── detail-pengaduan.php   # Detail & Form Ubah Status / Tindak Lanjut
│   ├── index.php              # Dashboard Administrator
│   ├── laporan.php            # Rekapitulasi & Cetak Dokumen Resmi
│   ├── pengaduan.php          # Manajemen Seluruh Pengaduan & Filter
│   ├── pengguna.php           # Manajemen Akun Mahasiswa & Admin
│   └── profil.php             # Pengaturan Profil Administrator
├── assets/                    # Aset Statis
│   ├── css/
│   │   └── style.css          # Desain Tema Warna Sage & Slate Minimalis
│   ├── img/
│   │   └── logo.svg           # Logo Vektor CareCampus
│   └── js/
│       └── main.js            # Interaksi UI, Preview Upload, Validasi
├── auth/                      # Otentikasi & Sesi
│   ├── login.php              # Halaman Masuk + Tombol Demo Cepat
│   ├── logout.php             # Proses Keluar Aman
│   └── register.php           # Formulir Pendaftaran Mahasiswa Baru
├── config/                    # Konfigurasi Inti
│   ├── database.php           # Koneksi PDO & Auto-Installer Skema
│   └── session.php            # Manajemen Sesi, Role Guard & CSRF
├── database/                  # Skrip Basis Data
│   └── carecampus_db.sql      # Skema DDL & Data Contoh (phpMyAdmin)
├── includes/                  # Komponen Antarmuka Bersama
│   ├── alerts.php             # Notifikasi Flash Alert
│   ├── footer.php             # Footer & Script Loader
│   ├── header.php             # Tag Head, Font & CSS Loader
│   ├── navbar.php             # Navigasi Atas Responsif
│   └── sidebar.php            # Sidebar Menu Navigasi Berbasis Role
├── uploads/                   # Direktori Berkas Unggahan
│   └── complaints/            # Foto Bukti Pengaduan (.htaccess Protected)
├── index.php                  # Router Utama Aplikasi
└── README.md                  # Dokumentasi Proyek
```

---

## ⚙️ Petunjuk Instalasi & Menjalankan di XAMPP

1. **Pastikan XAMPP Terpasang**
   - Pastikan modul **Apache** dan **MySQL** telah dijalankan (*Start*) melalui XAMPP Control Panel.

2. **Letakkan Proyek di Folder `htdocs`**
   - Pastikan folder proyek berada pada:  
     `c:\xampp\htdocs\PutriPPL\`

3. **Impor Basis Data (Database)**
   - Buka peramban (*browser*) dan akses **phpMyAdmin**:  
     `http://localhost/phpmyadmin/`
   - Klik tab **Import** (Impor).
   - Pilih berkas: `c:\xampp\htdocs\PutriPPL\database\carecampus_db.sql`.
   - Klik tombol **Import** di bagian bawah.
   - Database `carecampus_db` beserta tabel `users`, `complaints`, `complaint_logs`, dan data awal akan otomatis dibuat.
   *(Catatan: Aplikasi juga memiliki fitur auto-create jika database belum ada saat pertama kali diakses).*

4. **Akses Aplikasi**
   - Buka tautan berikut di peramban:  
     **`http://localhost/PutriPPL/`**
   - Anda akan diarahkan ke halaman login CareCampus.
   - Klik tombol **Demo Mahasiswa** atau **Demo Admin** untuk masuk secara instan.

---

## 🔒 Aspek Keamanan yang Diterapkan

- **Password Hashing**: Menggunakan algoritma BCRYPT standar industri (`password_hash` & `password_verify`).
- **Pencegahan SQL Injection**: Seluruh query interaksi database menggunakan **PDO Prepared Statements** dengan *parameter binding*.
- **Role-Based Access Control (RBAC)**: Pemisahan hak akses yang ketat antara Mahasiswa dan Admin. Mahasiswa hanya diizinkan melihat dan mengelola pengaduan miliknya sendiri (`WHERE user_id = ?`).
- **Perlindungan CSRF**: Token unik per sesi diverifikasi di setiap pengiriman formulir POST.
- **Sanitasi XSS**: Setiap data masukan pengguna disaring menggunakan fungsi `htmlspecialchars()` sebelum dicetak ke layar.
- **Validasi Unggah Berkas**: Memeriksa ukuran berkas (maks. 3 MB), ekstensi berkas, dan MIME-type asli di sisi server, serta melindungi direktori berkas dengan file `.htaccess`.

---

**CareCampus** &bull; Dibuat dengan dedikasi oleh **Putri Alya**.

