<?php
/**
 * CareCampus - Dashboard Mahasiswa
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_mahasiswa();

$user = current_user();
$pdo = get_db();

// Statistik pengaduan milik mahasiswa ini
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM complaints 
    WHERE user_id = ?
");
$stmtStats->execute([$user['id']]);
$stats = $stmtStats->fetch() ?: [
    'total' => 0, 'menunggu' => 0, 'diproses' => 0, 'selesai' => 0, 'ditolak' => 0
];

// Daftar 5 pengaduan terbaru milik mahasiswa
$stmtRecent = $pdo->prepare("
    SELECT * FROM complaints 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmtRecent->execute([$user['id']]);
$recentComplaints = $stmtRecent->fetchAll();

$page_title = 'Dashboard Mahasiswa';
$active_menu = 'dashboard';
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

      <!-- Welcome Banner -->
      <div class="custom-card mb-4 border-0" style="background: linear-gradient(135deg, #FFFFFF 0%, #F1F5F9 100%);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge" style="background-color: var(--accent-sage-light); color: var(--accent-sage-dark);">
                Mahasiswa Aktif
              </span>
              <span class="text-muted small">NIM: <?= e($user['nim'] ?? '-') ?></span>
            </div>
            <h2 class="h4 fw-bold mb-1" style="color: var(--text-dark);">
              Selamat Datang, <?= e($user['nama']) ?> 👋
            </h2>
            <p class="text-muted mb-0 small" style="max-width: 600px;">
              Ada fasilitas kampus yang rusak, kotor, atau butuh perbaikan? Laporkan dengan cepat melalui CareCampus agar segera ditindaklanjuti oleh pengelola sarpras kampus.
            </p>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= base_url('mahasiswa/buat-pengaduan.php') ?>" class="btn-custom btn-primary-custom shadow-sm py-2 px-3">
              <i class="bi bi-plus-circle"></i>
              <span>Buat Pengaduan Baru</span>
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
              <small class="text-muted" style="font-size: 0.72rem;">Semua laporan Anda</small>
            </div>
            <div class="stat-icon slate">
              <i class="bi bi-folder2-open"></i>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div>
              <div class="stat-label">Menunggu</div>
              <div class="stat-value text-secondary"><?= (int)$stats['menunggu'] ?></div>
              <small class="text-muted" style="font-size: 0.72rem;">Belum ditinjau admin</small>
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
              <small class="text-muted" style="font-size: 0.72rem;">Dalam penanganan teknisi</small>
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
              <small class="text-muted" style="font-size: 0.72rem;">Perbaikan rampung</small>
            </div>
            <div class="stat-icon green">
              <i class="bi bi-check-circle"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Complaints Table -->
      <div class="custom-card p-0 overflow-hidden mb-4">
        <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom bg-white">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-muted"></i>
            <h3 class="h6 fw-bold mb-0 text-dark">Pengaduan Terbaru Anda</h3>
          </div>
          <a href="<?= base_url('mahasiswa/riwayat.php') ?>" class="small fw-semibold text-secondary text-decoration-none">
            Lihat Semua Riwayat <i class="bi bi-arrow-right"></i>
          </a>
        </div>

        <?php if (empty($recentComplaints)): ?>
          <div class="text-center py-5 px-3">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-3" style="background-color: var(--accent-sage-light);">
              <i class="bi bi-inbox fs-2" style="color: var(--accent-sage-dark);"></i>
            </div>
            <h4 class="h6 fw-semibold text-dark mb-1">Belum Ada Pengaduan yang Dibuat</h4>
            <p class="text-muted small mb-3">Jika Anda menemukan fasilitas rusak di kampus, silakan buat laporan pertama Anda sekarang.</p>
            <a href="<?= base_url('mahasiswa/buat-pengaduan.php') ?>" class="btn-custom btn-primary-custom py-1 px-3">
              <i class="bi bi-plus-circle"></i> Buat Pengaduan Sekarang
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="custom-table mb-0">
              <thead>
                <tr>
                  <th style="width: 130px;">No. Laporan</th>
                  <th>Judul Pengaduan</th>
                  <th>Kategori</th>
                  <th>Lokasi</th>
                  <th>Tanggal</th>
                  <th>Status</th>
                  <th class="text-end" style="width: 100px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentComplaints as $c): ?>
                  <tr>
                    <td>
                      <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.76rem;">
                        <?= e($c['nomor_laporan']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= base_url('mahasiswa/detail-pengaduan.php?id=' . $c['id']) ?>" class="fw-semibold text-dark text-decoration-none d-block text-truncate" style="max-width: 280px;">
                        <?= e($c['judul']) ?>
                      </a>
                    </td>
                    <td>
                      <span class="small text-muted"><?= e($c['kategori']) ?></span>
                    </td>
                    <td>
                      <span class="small text-muted"><?= e($c['lokasi']) ?></span>
                    </td>
                    <td>
                      <span class="small text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></span>
                    </td>
                    <td>
                      <?= status_badge($c['status']) ?>
                    </td>
                    <td class="text-end">
                      <a href="<?= base_url('mahasiswa/detail-pengaduan.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-custom py-1 px-2" style="font-size: 0.78rem;" title="Lihat Detail">
                        <i class="bi bi-eye"></i> Detail
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Quick Steps / Information Section -->
      <div class="row g-3">
        <div class="col-md-4">
          <div class="custom-card p-3 h-100 bg-white">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 28px; height: 28px; background-color: var(--accent-sage-light); color: var(--accent-sage-dark); font-weight: 700; font-size: 0.8rem;">1</span>
              <h4 class="h6 fw-semibold mb-0 text-dark">Laporkan Masalah</h4>
            </div>
            <p class="text-muted small mb-0">Isi formulir pengaduan dengan detail lokasi dan foto bukti kerusakan yang ditemukan.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="custom-card p-3 h-100 bg-white">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 28px; height: 28px; background-color: #EEF2FF; color: #4338CA; font-weight: 700; font-size: 0.8rem;">2</span>
              <h4 class="h6 fw-semibold mb-0 text-dark">Pantau Proses</h4>
            </div>
            <p class="text-muted small mb-0">Admin sarpras akan meninjau dan menugaskan teknisi terkait untuk perbaikan langsung di lokasi.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="custom-card p-3 h-100 bg-white">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 28px; height: 28px; background-color: #F0FDF4; color: #166534; font-weight: 700; font-size: 0.8rem;">3</span>
              <h4 class="h6 fw-semibold mb-0 text-dark">Fasilitas Selesai</h4>
            </div>
            <p class="text-muted small mb-0">Status laporan akan diperbarui menjadi 'Selesai' beserta catatan tindak lanjut dan teknisi penangan.</p>
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

