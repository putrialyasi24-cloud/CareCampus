<?php
/**
 * CareCampus - Header Component
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

if (!defined('DB_HOST')) {
    require_once dirname(__DIR__) . '/config/session.php';
}

$page_title = isset($page_title) ? $page_title . ' - CareCampus' : 'CareCampus - Sistem Informasi Pengaduan Fasilitas Kampus';
$active_menu = isset($active_menu) ? $active_menu : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CareCampus - Sistem Informasi Pengaduan dan Pelaporan Kerusakan Fasilitas Kampus oleh Putri Alya">
  <meta name="author" content="Putri Alya">
  <title><?= e($page_title) ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">

  <!-- Google Fonts: Poppins & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

