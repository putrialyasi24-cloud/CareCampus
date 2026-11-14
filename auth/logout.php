<?php
/**
 * CareCampus - Logout Action
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';

// Hapus session
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Mulai session baru hanya untuk flash message
session_start();
set_flash('info', 'Anda telah berhasil keluar dari sistem CareCampus.');

header('Location: ' . base_url('auth/login.php'));
exit;

