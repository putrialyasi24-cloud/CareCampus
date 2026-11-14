<?php
/**
 * CareCampus - Database Configuration & Helpers
 * Aplikasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carecampus_db');

/**
 * Get PDO Database Connection
 * Otomatis inisialisasi database jika belum ada
 */
function get_db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Jika database belum ada, coba buat database dan import skema
        if ($e->getCode() == 1049) {
            try {
                $tempPdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                $sqlPath = dirname(__DIR__) . '/database/carecampus_db.sql';
                if (file_exists($sqlPath)) {
                    $sqlContent = file_get_contents($sqlPath);
                    $tempPdo->exec("USE `" . DB_NAME . "`");
                    $tempPdo->exec($sqlContent);
                }
                
                // Coba koneksi ulang
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $ex) {
                die("Gagal menginisialisasi database: " . $ex->getMessage());
            }
        } else {
            die("Koneksi database gagal: " . $e->getMessage() . ". Pastikan MySQL di XAMPP sudah berjalan.");
        }
    }

    return $pdo;
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
 * Daftar Kategori Fasilitas Resmi Sesuai Panduan
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

