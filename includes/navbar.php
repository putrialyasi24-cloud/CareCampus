<?php
/**
 * CareCampus - Top Navbar Component
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

$user = current_user();
?>
<header class="top-navbar">
  <div class="d-flex align-items-center gap-3">
    <!-- Mobile hamburger button -->
    <button class="btn btn-outline-custom d-lg-none p-2" type="button" id="sidebarToggleBtn" aria-label="Buka Menu">
      <i class="bi bi-list fs-5"></i>
    </button>

    <!-- Brand / Title -->
    <a href="<?= is_admin() ? base_url('admin/index.php') : base_url('mahasiswa/index.php') ?>" class="navbar-brand-text">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo CareCampus" width="34" height="34">
      <div>
        <h1 class="brand-title">CareCampus</h1>
        <p class="brand-subtitle d-none d-sm-block">Sistem Pengaduan Fasilitas Kampus</p>
      </div>
    </a>
    
    <span class="brand-badge d-none d-md-inline-block">
      <i class="bi bi-code-slash me-1"></i>By Putri Alya
    </span>
  </div>

  <div class="d-flex align-items-center gap-3">
    <?php if (is_mahasiswa()): ?>
      <a href="<?= base_url('mahasiswa/buat-pengaduan.php') ?>" class="btn-custom btn-sage-custom d-none d-sm-inline-flex py-1 px-3">
        <i class="bi bi-plus-circle"></i>
        <span>Buat Pengaduan</span>
      </a>
    <?php endif; ?>

    <!-- User Profile Dropdown -->
    <?php if ($user): ?>
      <div class="dropdown">
        <button class="btn btn-outline-custom d-flex align-items-center gap-2 py-1 px-2 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="user-avatar-circle <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
            <?= strtoupper(substr($user['nama'], 0, 1)) ?>
          </div>
          <div class="text-start d-none d-md-block">
            <div class="fw-semibold text-truncate" style="max-width: 130px; font-size: 0.88rem;">
              <?= e($user['nama']) ?>
            </div>
            <div class="text-muted" style="font-size: 0.72rem;">
              <?= $user['role'] === 'admin' ? 'Administrator' : 'Mahasiswa (' . e($user['nim'] ?? 'NIM') . ')' ?>
            </div>
          </div>
          <i class="bi bi-chevron-down text-muted" style="font-size: 0.75rem;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border" style="border-radius: 12px; font-size: 0.88rem; min-width: 200px;">
          <li class="px-3 py-2 border-bottom">
            <div class="fw-semibold text-dark"><?= e($user['nama']) ?></div>
            <div class="text-muted small"><?= e($user['email']) ?></div>
          </li>
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= is_admin() ? base_url('admin/profil.php') : base_url('mahasiswa/profil.php') ?>">
              <i class="bi bi-person text-muted"></i>
              <span>Profil Akun</span>
            </a>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li>
            <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="<?= base_url('auth/logout.php') ?>" onclick="return confirm('Apakah Anda yakin ingin keluar dari CareCampus?');">
              <i class="bi bi-box-arrow-right"></i>
              <span>Keluar</span>
            </a>
          </li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</header>

