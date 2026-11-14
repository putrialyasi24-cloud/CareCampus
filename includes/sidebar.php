<?php
/**
 * CareCampus - Sidebar Component
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

$user = current_user();
$active_menu = $active_menu ?? '';

// Badge counts for sidebar
$pdo = get_db();
$menungguCount = 0;
if (is_admin()) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Menunggu'");
    $menungguCount = (int)$stmt->fetchColumn();
} elseif (is_mahasiswa()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'Menunggu'");
    $stmt->execute([$user['id']]);
    $menungguCount = (int)$stmt->fetchColumn();
}
?>
<aside class="sidebar-wrapper">
  <!-- Sidebar Header -->
  <div class="sidebar-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="CareCampus Logo" width="30" height="30">
      <div>
        <span class="fw-bold" style="color: var(--primary-hover); font-size: 1.1rem;">CareCampus</span>
      </div>
    </div>
    <!-- Mobile close button -->
    <button class="btn btn-sm btn-outline-custom d-lg-none" id="sidebarCloseBtn" aria-label="Tutup Menu">
      <i class="bi bi-x fs-5"></i>
    </button>
  </div>

  <!-- Sidebar Menu List -->
  <ul class="sidebar-menu">
    <?php if (is_mahasiswa()): ?>
      <li class="sidebar-menu-title">Menu Mahasiswa</li>
      
      <li class="sidebar-item">
        <a href="<?= base_url('mahasiswa/index.php') ?>" class="sidebar-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
          <i class="bi bi-grid-1x2"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <li class="sidebar-item">
        <a href="<?= base_url('mahasiswa/buat-pengaduan.php') ?>" class="sidebar-link <?= $active_menu === 'buat-pengaduan' ? 'active' : '' ?>">
          <i class="bi bi-plus-square"></i>
          <span>Buat Pengaduan</span>
        </a>
      </li>

      <li class="sidebar-item">
        <a href="<?= base_url('mahasiswa/riwayat.php') ?>" class="sidebar-link <?= $active_menu === 'riwayat' ? 'active' : '' ?>">
          <i class="bi bi-journal-text"></i>
          <span class="flex-grow-1">Riwayat Pengaduan</span>
          <?php if ($menungguCount > 0): ?>
            <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.7rem;">
              <?= $menungguCount ?>
            </span>
          <?php endif; ?>
        </a>
      </li>

      <li class="sidebar-item">
        <a href="<?= base_url('mahasiswa/profil.php') ?>" class="sidebar-link <?= $active_menu === 'profil' ? 'active' : '' ?>">
          <i class="bi bi-person"></i>
          <span>Profil Saya</span>
        </a>
      </li>

    <?php elseif (is_admin()): ?>
      <li class="sidebar-menu-title">Menu Pengelola</li>

      <li class="sidebar-item">
        <a href="<?= base_url('admin/index.php') ?>" class="sidebar-link <?= $active_menu === 'admin_dashboard' ? 'active' : '' ?>">
          <i class="bi bi-speedometer2"></i>
          <span>Dashboard Admin</span>
        </a>
      </li>

      <li class="sidebar-item">
        <a href="<?= base_url('admin/pengaduan.php') ?>" class="sidebar-link <?= $active_menu === 'admin_pengaduan' ? 'active' : '' ?>">
          <i class="bi bi-inbox"></i>
          <span class="flex-grow-1">Semua Pengaduan</span>
          <?php if ($menungguCount > 0): ?>
            <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.7rem;" title="<?= $menungguCount ?> pengaduan belum diproses">
              <?= $menungguCount ?> Baru
            </span>
          <?php endif; ?>
        </a>
      </li>

      <li class="sidebar-item">
        <a href="<?= base_url('admin/laporan.php') ?>" class="sidebar-link <?= $active_menu === 'admin_laporan' ? 'active' : '' ?>">
          <i class="bi bi-printer"></i>
          <span>Rekap Laporan</span>
        </a>
      </li>

      <li class="sidebar-item">
        <a href="<?= base_url('admin/pengguna.php') ?>" class="sidebar-link <?= $active_menu === 'admin_pengguna' ? 'active' : '' ?>">
          <i class="bi bi-people"></i>
          <span>Data Pengguna</span>
        </a>
      </li>

      <li class="sidebar-item">
        <a href="<?= base_url('admin/profil.php') ?>" class="sidebar-link <?= $active_menu === 'admin_profil' ? 'active' : '' ?>">
          <i class="bi bi-shield-check"></i>
          <span>Profil Admin</span>
        </a>
      </li>
    <?php endif; ?>

    <li class="sidebar-menu-title mt-3">Akun</li>
    <li class="sidebar-item">
      <a href="<?= base_url('auth/logout.php') ?>" class="sidebar-link text-danger" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
        <i class="bi bi-box-arrow-right text-danger"></i>
        <span>Keluar</span>
      </a>
    </li>
  </ul>

  <!-- Sidebar Footer User Profile info -->
  <div class="sidebar-footer">
    <div class="user-profile-badge">
      <div class="user-avatar-circle <?= ($user && $user['role'] === 'admin') ? 'admin' : '' ?>">
        <?= $user ? strtoupper(substr($user['nama'], 0, 1)) : 'U' ?>
      </div>
      <div class="overflow-hidden">
        <div class="fw-semibold text-truncate text-dark" style="font-size: 0.85rem;">
          <?= $user ? e($user['nama']) : 'Tamu' ?>
        </div>
        <div class="text-muted text-truncate" style="font-size: 0.72rem;">
          <?= $user ? ($user['role'] === 'admin' ? 'Administrator' : e($user['prodi'] ?? 'Mahasiswa')) : '' ?>
        </div>
      </div>
    </div>
  </div>
</aside>

