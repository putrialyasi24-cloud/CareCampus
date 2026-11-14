<?php
/**
 * CareCampus - Riwayat Pengaduan Mahasiswa
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_mahasiswa();

$user = current_user();
$pdo = get_db();

// Filter & Search Parameters
$statusFilter = trim($_GET['status'] ?? '');
$searchKeyword = trim($_GET['q'] ?? '');
$kategoriFilter = trim($_GET['kategori'] ?? '');

$sql = "SELECT * FROM complaints WHERE user_id = ?";
$params = [$user['id']];

if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

if (!empty($kategoriFilter)) {
    $sql .= " AND kategori = ?";
    $params[] = $kategoriFilter;
}

if (!empty($searchKeyword)) {
    $sql .= " AND (nomor_laporan LIKE ? OR judul LIKE ? OR lokasi LIKE ? OR detail_lokasi LIKE ?)";
    $searchWildcard = "%{$searchKeyword}%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Hitung total per status untuk filter counter
$stmtCount = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM complaints 
    WHERE user_id = ?
");
$stmtCount->execute([$user['id']]);
$countStats = $stmtCount->fetch() ?: ['total' => 0, 'menunggu' => 0, 'diproses' => 0, 'selesai' => 0, 'ditolak' => 0];

$kategoriList = get_categories();

$page_title = 'Riwayat Pengaduan Saya';
$active_menu = 'riwayat';
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

      <!-- Header & Action -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Riwayat Pengaduan Fasilitas</h2>
          <p class="text-muted small mb-0">Pantau seluruh laporan kerusakan yang pernah Anda sampaikan</p>
        </div>
        <a href="<?= base_url('mahasiswa/buat-pengaduan.php') ?>" class="btn-custom btn-primary-custom py-2 px-3">
          <i class="bi bi-plus-circle"></i>
          <span>Buat Pengaduan Baru</span>
        </a>
      </div>

      <!-- Filter Tabs / Pills -->
      <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="<?= base_url('mahasiswa/riwayat.php' . ($searchKeyword ? '?q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= empty($statusFilter) ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Semua (<?= (int)$countStats['total'] ?>)
        </a>
        <a href="<?= base_url('mahasiswa/riwayat.php?status=Menunggu' . ($searchKeyword ? '&q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= $statusFilter === 'Menunggu' ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Menunggu (<?= (int)$countStats['menunggu'] ?>)
        </a>
        <a href="<?= base_url('mahasiswa/riwayat.php?status=Diproses' . ($searchKeyword ? '&q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= $statusFilter === 'Diproses' ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Diproses (<?= (int)$countStats['diproses'] ?>)
        </a>
        <a href="<?= base_url('mahasiswa/riwayat.php?status=Selesai' . ($searchKeyword ? '&q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= $statusFilter === 'Selesai' ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Selesai (<?= (int)$countStats['selesai'] ?>)
        </a>
        <a href="<?= base_url('mahasiswa/riwayat.php?status=Ditolak' . ($searchKeyword ? '&q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= $statusFilter === 'Ditolak' ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Ditolak (<?= (int)$countStats['ditolak'] ?>)
        </a>
      </div>

      <!-- Search & Category Filter Box -->
      <div class="custom-card p-3 mb-4">
        <form action="<?= base_url('mahasiswa/riwayat.php') ?>" method="GET" class="row g-2 align-items-center">
          <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
          <?php endif; ?>

          <div class="col-md-6 col-lg-7">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 12px 0 0 12px;">
                <i class="bi bi-search"></i>
              </span>
              <input type="text" 
                     name="q" 
                     class="form-control-custom form-control border-start-0" 
                     placeholder="Cari nomor laporan, judul, atau lokasi..." 
                     value="<?= e($searchKeyword) ?>"
                     style="border-radius: 0 12px 12px 0;">
            </div>
          </div>

          <div class="col-md-4 col-lg-3">
            <select name="kategori" class="form-select-custom">
              <option value="">Semua Kategori</option>
              <?php foreach ($kategoriList as $kat): ?>
                <option value="<?= e($kat) ?>" <?= $kategoriFilter === $kat ? 'selected' : '' ?>>
                  <?= e($kat) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2 col-lg-2 d-flex gap-1">
            <button type="submit" class="btn-custom btn-primary-custom w-100 py-2">
              Filter
            </button>
            <?php if (!empty($searchKeyword) || !empty($kategoriFilter) || !empty($statusFilter)): ?>
              <a href="<?= base_url('mahasiswa/riwayat.php') ?>" class="btn btn-outline-custom py-2" title="Reset filter">
                <i class="bi bi-x-circle"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Complaints List Table -->
      <div class="custom-card p-0 overflow-hidden mb-4">
        <?php if (empty($complaints)): ?>
          <div class="text-center py-5 px-3">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-3" style="background-color: var(--accent-sage-light);">
              <i class="bi bi-search fs-2" style="color: var(--accent-sage-dark);"></i>
            </div>
            <h4 class="h6 fw-semibold text-dark mb-1">Tidak Ada Pengaduan yang Ditemukan</h4>
            <p class="text-muted small mb-3">
              <?= (!empty($searchKeyword) || !empty($statusFilter) || !empty($kategoriFilter)) 
                  ? 'Coba ganti kata kunci atau reset filter pencarian Anda.' 
                  : 'Anda belum pernah membuat pengaduan fasilitas kampus.' ?>
            </p>
            <?php if (!empty($searchKeyword) || !empty($statusFilter) || !empty($kategoriFilter)): ?>
              <a href="<?= base_url('mahasiswa/riwayat.php') ?>" class="btn-custom btn-outline-custom py-1 px-3">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
              </a>
            <?php else: ?>
              <a href="<?= base_url('mahasiswa/buat-pengaduan.php') ?>" class="btn-custom btn-primary-custom py-1 px-3">
                <i class="bi bi-plus-circle"></i> Buat Pengaduan Sekarang
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="custom-table mb-0">
              <thead>
                <tr>
                  <th style="width: 140px;">No. Laporan</th>
                  <th>Judul Pengaduan</th>
                  <th>Kategori</th>
                  <th>Lokasi Kejadian</th>
                  <th>Tanggal Lapor</th>
                  <th>Status</th>
                  <th class="text-end" style="width: 110px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($complaints as $c): ?>
                  <tr>
                    <td>
                      <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.78rem;">
                        <?= e($c['nomor_laporan']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= base_url('mahasiswa/detail-pengaduan.php?id=' . $c['id']) ?>" class="fw-semibold text-dark text-decoration-none d-block">
                        <?= e($c['judul']) ?>
                      </a>
                      <?php if (!empty($c['catatan_admin'])): ?>
                        <small class="text-muted d-block text-truncate" style="max-width: 280px; font-size: 0.75rem;">
                          <i class="bi bi-chat-left-dots text-primary me-1"></i><?= e($c['catatan_admin']) ?>
                        </small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="small text-muted"><?= e($c['kategori']) ?></span>
                    </td>
                    <td>
                      <div class="small fw-semibold text-dark"><?= e($c['lokasi']) ?></div>
                      <div class="text-muted" style="font-size: 0.74rem;"><?= e($c['detail_lokasi']) ?></div>
                    </td>
                    <td>
                      <span class="small text-muted"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></span>
                    </td>
                    <td>
                      <?= status_badge($c['status']) ?>
                    </td>
                    <td class="text-end">
                      <a href="<?= base_url('mahasiswa/detail-pengaduan.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-custom py-1 px-2" style="font-size: 0.78rem;">
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

    </div>

    <!-- Footer -->
    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
  </div>
</div>

</body>
</html>

