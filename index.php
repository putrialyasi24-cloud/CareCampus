<?php
/**
 * CareCampus - Entry Point / Router
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once __DIR__ . '/config/session.php';

if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . base_url('admin/index.php'));
        exit;
    } else {
        header('Location: ' . base_url('mahasiswa/index.php'));
        exit;
    }
}

// Jika belum login, arahkan ke halaman login
header('Location: ' . base_url('auth/login.php'));
exit;

