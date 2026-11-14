<?php
/**
 * CareCampus - Dashboard Admin
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_admin();

$user = current_user();
$pdo = get_db();

// Statistik Total Pengaduan Kampus
$stmtStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM complaints
");
$stats = $stmtStats->fetch() ?: [
    'total' => 0, 'menunggu' => 0, 'diproses' => 0, 'selesai' => 0, 'ditolak' => 0
];

// Total Mahasiswa Terdaftar
$stmtUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'");
$totalMahasiswa = (int)$stmtUsers->fetchColumn();

// 5 Pengaduan Terbaru yang Masuk
$stmtRecent = $pdo->query("
    SELECT c.*, u.nama as nama_pelapor, u.nim as nim_pelapor 
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC 
    LIMIT 6
");
$recentComplaints = $stmtRecent->fetchAll();

// Breakdown Pengaduan per Kategori
$stmtKat = $pdo->query("
    SELECT kategori, COUNT(*) as count 
    FROM complaints 
    GROUP BY kategori 
    ORDER BY count DESC
");
$kategoriBreakdown = $stmtKat->fetchAll();

$page_title = 'Dashboard Admin';
$active_menu = 'admin_dashboard';
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="app-wrapper">
  <!-- Sidebar -->
  <?php require dirname(__DIR__) . '/includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Navbar -->
    <?php require dirname(__DIR__) . '/includes/navbar.php'; ?>

    <div class="content-body">
      <!-- Flash Alert -->
      <?php require dirname(__DIR__) . '/includes/alerts.php'; ?>

      <!-- Welcome Admin Banner -->
      <div class="custom-card mb-4 border-0" style="background: linear-gradient(135deg, #FFFFFF 0%, #F1F5F9 100%);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge" style="background-color: var(--primary-light); color: var(--primary); border: 1px solid rgba(100,116,139,0.3);">
                <i class="bi bi-shield-check me-1"></i>Panel Pengelola Sarpras
              </span>
              <span class="text-muted small">CareCampus Management</span>
            </div>
            <h2 class="h4 fw-bold mb-1 text-dark">
              Selamat Bertugas, <?= e($user['nama']) ?>
            </h2>
            <p class="text-muted mb-0 small" style="max-width: 650px;">
              Pantau laporan kerusakan sarana dan prasarana kampus dari mahasiswa secara berkala. Segera lakukan verifikasi dan tindak lanjut perbaikan.
            </p>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= base_url('admin/pengaduan.php?status=Menunggu') ?>" class="btn-custom btn-primary-custom shadow-sm py-2 px-3">
              <i class="bi bi-inbox-fill"></i>
              <span>Periksa Laporan Baru</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Stat Cards Grid -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div>
              <div class="stat-label">Total Pengaduan</div>
              <div class="stat-value"><?= (int)$stats['total'] ?></div>
              <small class="text-muted" style="font-size: 0.72rem;">Seluruh laporan masuk</small>
            </div>
            <div class="stat-icon slate">
              <i class="bi bi-folder2-open"></i>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div>
              <div class="stat-label">Menunggu Tindakan</div>
              <div class="stat-value text-secondary"><?= (int)$stats['menunggu'] ?></div>
              <small class="text-muted" style="font-size: 0.72rem;">Perlu diverifikasi segera</small>
            </div>
            <div class="stat-icon slate" style="background-color: #F1F5F9; color: #475569;">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div>
              <div class="stat-label">Sedang Diproses</div>
              <div class="stat-value" style="color: #4338CA;"><?= (int)$stats['diproses'] ?></div>
              <small class="text-muted" style="font-size: 0.72rem;">Dalam pengerjaan teknisi</small>
            </div>
            <div class="stat-icon blue">
              <i class="bi bi-tools"></i>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div>
              <div class="stat-label">Selesai Ditangani</div>
              <div class="stat-value" style="color: #166534;"><?= (int)$stats['selesai'] ?></div>
              <small class="text-muted" style="font-size: 0.72rem;">Perbaikan telah tuntas</small>
            </div>
            <div class="stat-icon green">
              <i class="bi bi-check2-circle"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <!-- Recent Complaints Table (8 columns) -->
        <div class="col-lg-8">
          <div class="custom-card p-0 overflow-hidden mb-0 h-100">
            <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom bg-white">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-inbox text-muted"></i>
                <h3 class="h6 fw-bold mb-0 text-dark">Laporan Pengaduan Terbaru</h3>
              </div>
              <a href="<?= base_url('admin/pengaduan.php') ?>" class="small fw-semibold text-secondary text-decoration-none">
                Semua Pengaduan <i class="bi bi-arrow-right"></i>
              </a>
            </div>

            <?php if (empty($recentComplaints)): ?>
              <div class="text-center py-5 px-3">
                <p class="text-muted small mb-0">Belum ada laporan pengaduan dari mahasiswa.</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="custom-table mb-0">
                  <thead>
                    <tr>
                      <th style="width: 130px;">No. Laporan</th>
                      <th>Pelapor</th>
                      <th>Judul & Fasilitas</th>
                      <th>Lokasi</th>
                      <th>Status</th>
                      <th class="text-end" style="width: 90px;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recentComplaints as $c): ?>
                      <tr>
                        <td>
                          <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.75rem;">
                            <?= e($c['nomor_laporan']) ?>
                          </span>
                        </td>
                        <td>
                          <div class="fw-semibold text-dark small"><?= e($c['nama_pelapor']) ?></div>
                          <div class="text-muted" style="font-size: 0.72rem;"><?= e($c['nim_pelapor']) ?></div>
                        </td>
                        <td>
                          <a href="<?= base_url('admin/detail-pengaduan.php?id=' . $c['id']) ?>" class="fw-semibold text-dark text-decoration-none d-block text-truncate" style="max-width: 220px;">
                            <?= e($c['judul']) ?>
                          </a>
                          <span class="text-muted" style="font-size: 0.72rem;"><?= e($c['kategori']) ?></span>
                        </td>
                        <td>
                          <span class="small text-muted"><?= e($c['lokasi']) ?></span>
                        </td>
                        <td>
                          <?= status_badge($c['status']) ?>
                        </td>
                        <td class="text-end">
                          <a href="<?= base_url('admin/detail-pengaduan.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-custom py-1 px-2" style="font-size: 0.78rem;">
                            Kelola
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Breakdown by Category (4 columns) -->
        <div class="col-lg-4">
          <div class="custom-card bg-white h-100">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center justify-content-between">
              <span>Sebaran Kategori</span>
              <span class="badge bg-light text-secondary border"><?= count($kategoriBreakdown) ?> Kategori</span>
            </h3>

            <?php if (empty($kategoriBreakdown)): ?>
              <p class="text-muted small mb-0">Belum ada data kategori.</p>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php 
                $totalCount = max(1, (int)$stats['total']);
                foreach ($kategoriBreakdown as $kb): 
                    $percentage = round(($kb['count'] / $totalCount) * 100);
                ?>
                  <div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                      <span class="text-dark fw-medium text-truncate" style="max-width: 200px;">
                        <?= e($kb['kategori']) ?>
                      </span>
                      <span class="text-muted fw-semibold"><?= $kb['count'] ?> laporan</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: #F1F5F9;">
                      <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%; background-color: var(--accent-sage);" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="mt-4 pt-3 border-top">
              <div class="d-flex align-items-center justify-content-between small text-muted">
                <span>Mahasiswa Terdaftar</span>
                <span class="fw-bold text-dark"><?= $totalMahasiswa ?> Akun</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer -->
    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
  </div>
</div>

</body>
</html>

