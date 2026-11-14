<?php
/**
 * CareCampus - Session & Authentication Management
 * Aplikasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

if (!ob_get_level()) {
    ob_start();
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    // Parameter session yang aman
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/database.php';

/**
 * Base URL Helper
 * Menghasilkan URL yang akurat di XAMPP localhost atau subdirectory
 */
function base_url($path = '') {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $isHttps ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Tentukan folder root proyek PutriPPL
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    $baseFolder = '';
    if (strpos($scriptName, '/PutriPPL') !== false || strpos($requestUri, '/PutriPPL') === 0) {
        $baseFolder = '/PutriPPL';
    }

    $cleanPath = ltrim($path, '/');
    return rtrim($protocol . $host . $baseFolder, '/') . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * Flash Message Helper
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Cek Status Login
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Dapatkan Data Pengguna Aktif
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }

    static $currentUser = null;
    if ($currentUser !== null) {
        return $currentUser;
    }

    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, nama, nim, email, role, prodi, no_telp FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    if (!$currentUser) {
        // User di database sudah tidak ada
        session_unset();
        session_destroy();
        return null;
    }

    return $currentUser;
}

/**
 * Cek Role Pengguna
 */
function is_admin() {
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function is_mahasiswa() {
    $user = current_user();
    return $user && $user['role'] === 'mahasiswa';
}

/**
 * Route Guards
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak. Halaman tersebut hanya dapat diakses oleh Administrator.');
        header('Location: ' . base_url('mahasiswa/index.php'));
        exit;
    }
}

function require_mahasiswa() {
    require_login();
    if (!is_mahasiswa()) {
        set_flash('warning', 'Halaman ini khusus untuk mahasiswa. Anda dialihkan ke Dashboard Admin.');
        header('Location: ' . base_url('admin/index.php'));
        exit;
    }
}

/**
 * CSRF Protection
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitasi Output XSS
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

