<?php
/**
 * CareCampus - Database Configuration & Helpers
 * Aplikasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

// Sembunyikan notifikasi deprecated agar tidak merusak pengiriman HTTP headers
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);

// Konfigurasi Database (Mendukung Environment Variables untuk Vercel / Cloud MySQL)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'carecampus_db');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

/**
 * Get PDO Database Connection
 * Mendukung MySQL (XAMPP Lokal / Cloud MySQL) & Fallback ke SQLite di Vercel Serverless
 */
function get_db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $isVercel = getenv('VERCEL') || isset($_SERVER['VERCEL']);
    $dbHost = DB_HOST;

    // 1. Coba koneksi MySQL (Lokal XAMPP atau Cloud Database jika di Vercel)
    if (!$isVercel || ($isVercel && $dbHost !== 'localhost')) {
        try {
            $dsn = "mysql:host=" . $dbHost . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Abaikan verifikasi SSL ketat untuk cloud provider seperti TiDB / Aiven / Railway
            if ($isVercel) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // Jika error 1049 di lokal XAMPP (database belum dibuat), buat otomatis
            if ($e->getCode() == 1049 && !$isVercel) {
                try {
                    $tempPdo = new PDO("mysql:host=" . $dbHost . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $sqlPath = dirname(__DIR__) . '/database/carecampus_db.sql';
                    if (file_exists($sqlPath)) {
                        $tempPdo->exec("USE `" . DB_NAME . "`");
                        $tempPdo->exec(file_get_contents($sqlPath));
                    }
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    return $pdo;
                } catch (Exception $ex) {
                    // Fallback
                }
            }

            // Jika di lokal XAMPP tapi MySQL mati, tampilkan pesan ramah
            if (!$isVercel) {
                die("Koneksi database gagal: " . $e->getMessage() . ". Pastikan MySQL di XAMPP sudah berjalan.");
            }
        }
    }

    // 2. Fallback SQLite Otomatis (Sangat berguna untuk Vercel preview tanpa perlu setup DB terpisah)
    $sqliteFile = sys_get_temp_dir() . '/carecampus.sqlite';
    $needInit = !file_exists($sqliteFile) || filesize($sqliteFile) < 1000;

    $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Daftarkan fungsi NOW() agar kompatibel dengan query MySQL (kompatibel PHP 8.1 s/d 8.5+)
    if (method_exists($pdo, 'createFunction')) {
        @$pdo->createFunction('NOW', function() {
            return date('Y-m-d H:i:s');
        });
    } elseif (method_exists($pdo, 'sqliteCreateFunction')) {
        @$pdo->sqliteCreateFunction('NOW', function() {
            return date('Y-m-d H:i:s');
        });
    }

    if ($needInit) {
        init_sqlite_schema($pdo);
    }

    return $pdo;
}

/**
 * Inisialisasi skema & seed data untuk SQLite saat di Vercel
 */
function init_sqlite_schema($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama TEXT NOT NULL,
            nim TEXT DEFAULT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'mahasiswa',
            prodi TEXT DEFAULT NULL,
            no_telp TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS complaints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nomor_laporan TEXT NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            judul TEXT NOT NULL,
            kategori TEXT NOT NULL,
            lokasi TEXT NOT NULL,
            detail_lokasi TEXT NOT NULL,
            deskripsi TEXT NOT NULL,
            foto TEXT DEFAULT NULL,
            status TEXT NOT NULL DEFAULT 'Menunggu',
            catatan_admin TEXT DEFAULT NULL,
            petugas TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS complaint_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            complaint_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            status_sebelumnya TEXT DEFAULT NULL,
            status_baru TEXT NOT NULL,
            keterangan TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed Akun Pengguna
    $adminPass = '$2y$10$OL12X/QaxCpaEGIyiQ0ZTOvPecwWav15i8/dJTGFVhLof0qSjkZMi'; // admin123
    $mhsPass = '$2y$10$znsT6Kpjw4eWAMK3mjKS7OqTuqFtnjJfhRqBYVmxvrUa8OHIMJXvq';   // mahasiswa123

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (id, nama, nim, email, password, role, prodi, no_telp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'Admin Sarpras Kampus', 'ADM-001', 'admin@carecampus.ac.id', $adminPass, 'admin', 'Bagian Sarana & Prasarana', '081234567890']);
    $stmt->execute([2, 'Putri Alya', '202401001', 'putri@mahasiswa.ac.id', $mhsPass, 'mahasiswa', 'Teknik Informatika', '085712345678']);
    $stmt->execute([3, 'Budi Santoso', '202401002', 'budi@mahasiswa.ac.id', $mhsPass, 'mahasiswa', 'Sistem Informasi', '082198765432']);

    // Seed Data Pengaduan
    $complaintsData = [
        ['CC-202611-0001', 2, 'AC Bocor dan Mengeluarkan Air Terus di Lab Komputer 2', 'AC atau pendingin ruangan bermasalah', 'Gedung Fasilkom', 'Lantai 2, Lab Komputer 204', 'AC unit sebelah kanan mengeluarkan tetesan air cukup deras sehingga membasahi meja komputer.', 'Diproses', 'Laporan diverifikasi. Teknisi pendingin ruangan Pak Hadi sudah dihubungi.', 'Pak Hadi (Teknisi Pendingin)'],
        ['CC-202611-0002', 2, 'Lampu Neon Berkedip dan 2 Titik Lampu Mati Total di Ruang Teater', 'Lampu mati', 'Gedung Kuliah Bersama (GKB)', 'Lantai 1, Ruang Teater Utama', 'Lampu di bagian tengah depan panggung padam total saat presentasi.', 'Selesai', 'Bohlam LED 18W dan trafo telah diganti baru oleh tim elektrikal.', 'Pak Joko (Bagian Elektrikal)'],
        ['CC-202611-0003', 2, 'Proyektor HDMI Tidak Mendeteksi Sinyal Laptop', 'Proyektor atau perangkat kelas bermasalah', 'Gedung Rektorat Baru', 'Lantai 3, Ruang R301', 'Kabel port HDMI proyektor gantung sudah kendor dan layar biru No Signal.', 'Menunggu', null, null],
        ['CC-202611-0004', 3, 'Keran Wastafel Patah dan Air Mengucur Deras di Toilet Pria', 'Kebocoran atau masalah saluran air', 'Gedung Fasilkom', 'Lantai 1, Toilet Pria Dekat Musholla', 'Gagang keran wastafel sebelah barat patah dan air mengucur deras.', 'Diproses', 'Keran sementara telah ditutup melalui valve utama.', 'Pak Slamet (Plumbing Sarpras)'],
        ['CC-202611-0005', 3, 'Kursi Kuliah Sandaran Lepas dan Goyang di Ruang D305', 'Meja dan kursi rusak', 'Gedung D', 'Lantai 3, Ruang Kuliah D305', 'Terdapat sekitar 3 kursi lipat kuliah yang baut sandarannya copot.', 'Selesai', 'Ketiga kursi telah diperbaiki baut penguncinya di bengkel sarpras.', 'Pak Yanto (Sarpras)']
    ];

    $stmtC = $pdo->prepare("INSERT OR IGNORE INTO complaints (nomor_laporan, user_id, judul, kategori, lokasi, detail_lokasi, deskripsi, status, catatan_admin, petugas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($complaintsData as $c) {
        $stmtC->execute($c);
    }
}

/**
 * Generate Nomor Laporan Otomatis (Format: CC-YYYYMM-XXXX)
 */
function generate_report_number() {
    $pdo = get_db();
    $prefix = 'CC-' . date('Ym') . '-';
    
    $stmt = $pdo->prepare("SELECT nomor_laporan FROM complaints WHERE nomor_laporan LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastReport = $stmt->fetch();

    if ($lastReport) {
        $lastNumber = intval(substr($lastReport['nomor_laporan'], -4));
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextNumber = '0001';
    }

    return $prefix . $nextNumber;
}

/**
 * Format Tanggal Indonesia
 */
function format_date($datetime, $with_time = true) {
    if (!$datetime) return '-';
    
    $timestamp = strtotime($datetime);
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $hari = date('d', $timestamp);
    $bulan = $bulanIndo[intval(date('m', $timestamp))];
    $tahun = date('Y', $timestamp);

    if ($with_time) {
        $waktu = date('H:i', $timestamp) . ' WIB';
        return "{$hari} {$bulan} {$tahun}, {$waktu}";
    }

    return "{$hari} {$bulan} {$tahun}";
}

/**
 * Badge Status dengan Palet Warna Lembut
 */
function status_badge($status) {
    $status = trim($status);
    switch ($status) {
        case 'Menunggu':
            return '<span class="status-badge badge-menunggu"><i class="bi bi-clock-history me-1"></i>Menunggu</span>';
        case 'Diproses':
            return '<span class="status-badge badge-diproses"><i class="bi bi-arrow-repeat me-1"></i>Diproses</span>';
        case 'Selesai':
            return '<span class="status-badge badge-selesai"><i class="bi bi-check2-circle me-1"></i>Selesai</span>';
        case 'Ditolak':
            return '<span class="status-badge badge-ditolak"><i class="bi bi-x-circle me-1"></i>Ditolak</span>';
        default:
            return '<span class="status-badge badge-secondary">' . htmlspecialchars($status) . '</span>';
    }
}

/**
 * Daftar Kategori Fasilitas Resmi
 */
function get_categories() {
    return [
        'AC atau pendingin ruangan bermasalah',
        'Toilet kotor atau berbau',
        'Lampu mati',
        'Proyektor atau perangkat kelas bermasalah',
        'Meja dan kursi rusak',
        'Kebocoran atau masalah saluran air',
        'Kebersihan ruangan',
        'Fasilitas lainnya'
    ];
}

/**
 * Daftar Gedung / Lokasi Kampus Populer
 */
function get_locations() {
    return [
        'Gedung Fasilkom',
        'Gedung Kuliah Bersama (GKB)',
        'Gedung Rektorat Baru',
        'Gedung Perpustakaan Pusat',
        'Gedung D (Laboratorium)',
        'Gedung B (Ruang Kuliah)',
        'Gedung C (Fakultas Teknik)',
        'Gedung Serbaguna / Auditorium',
        'Area Lapangan & Parkir Kampus',
        'Kantin & Food Court Kampus'
    ];
}
