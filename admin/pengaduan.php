<?php
/**
 * CareCampus - Kelola Seluruh Pengaduan (Admin)
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_admin();

$user = current_user();
$pdo = get_db();

// Filter & Search Parameters
$statusFilter = trim($_GET['status'] ?? '');
$kategoriFilter = trim($_GET['kategori'] ?? '');
$searchKeyword = trim($_GET['q'] ?? '');
$tanggalMulai = trim($_GET['tgl_mulai'] ?? '');
$tanggalSelesai = trim($_GET['tgl_selesai'] ?? '');

$sql = "
    SELECT c.*, u.nama as nama_pelapor, u.nim as nim_pelapor, u.prodi as prodi_pelapor 
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND c.status = ?";
    $params[] = $statusFilter;
}

if (!empty($kategoriFilter)) {
    $sql .= " AND c.kategori = ?";
    $params[] = $kategoriFilter;
}

if (!empty($searchKeyword)) {
    $sql .= " AND (c.nomor_laporan LIKE ? OR c.judul LIKE ? OR c.lokasi LIKE ? OR c.detail_lokasi LIKE ? OR u.nama LIKE ? OR u.nim LIKE ?)";
    $wildcard = "%{$searchKeyword}%";
    for ($i = 0; $i < 6; $i++) {
        $params[] = $wildcard;
    }
}

if (!empty($tanggalMulai)) {
    $sql .= " AND DATE(c.created_at) >= ?";
    $params[] = $tanggalMulai;
}

if (!empty($tanggalSelesai)) {
    $sql .= " AND DATE(c.created_at) <= ?";
    $params[] = $tanggalSelesai;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Hitung total status untuk filter tab
$stmtTotal = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
    FROM complaints
");
$countStats = $stmtTotal->fetch() ?: ['total' => 0, 'menunggu' => 0, 'diproses' => 0, 'selesai' => 0, 'ditolak' => 0];

$kategoriList = get_categories();

$page_title = 'Kelola Pengaduan Mahasiswa';
$active_menu = 'admin_pengaduan';
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

      <!-- Header & Quick Action -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Daftar Seluruh Pengaduan Kampus</h2>
          <p class="text-muted small mb-0">Kelola, verifikasi, dan tindak lanjuti laporan kerusakan fasilitas yang masuk</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= base_url('admin/laporan.php') ?>" class="btn-custom btn-outline-custom py-2 px-3">
            <i class="bi bi-printer me-1"></i>Rekapitulasi Laporan
          </a>
        </div>
      </div>

      <!-- Filter Tabs / Status Pills -->
      <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="<?= base_url('admin/pengaduan.php' . ($searchKeyword ? '?q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= empty($statusFilter) ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Semua (<?= (int)$countStats['total'] ?>)
        </a>
        <a href="<?= base_url('admin/pengaduan.php?status=Menunggu' . ($searchKeyword ? '&q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= $statusFilter === 'Menunggu' ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Menunggu (<?= (int)$countStats['menunggu'] ?>)
        </a>
        <a href="<?= base_url('admin/pengaduan.php?status=Diproses' . ($searchKeyword ? '&q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= $statusFilter === 'Diproses' ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Diproses (<?= (int)$countStats['diproses'] ?>)
        </a>
        <a href="<?= base_url('admin/pengaduan.php?status=Selesai' . ($searchKeyword ? '&q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= $statusFilter === 'Selesai' ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Selesai (<?= (int)$countStats['selesai'] ?>)
        </a>
        <a href="<?= base_url('admin/pengaduan.php?status=Ditolak' . ($searchKeyword ? '&q=' . urlencode($searchKeyword) : '')) ?>" 
           class="btn btn-sm <?= $statusFilter === 'Ditolak' ? 'btn-custom btn-primary-custom' : 'btn-outline-custom' ?>" 
           style="border-radius: 9999px; padding: 0.35rem 0.85rem; font-size: 0.82rem;">
          Ditolak (<?= (int)$countStats['ditolak'] ?>)
        </a>
      </div>

      <!-- Advanced Filter Card -->
      <div class="custom-card p-3 mb-4 bg-white">
        <form action="<?= base_url('admin/pengaduan.php') ?>" method="GET" class="row g-2 align-items-center">
          <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
          <?php endif; ?>

          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 12px 0 0 12px;">
                <i class="bi bi-search"></i>
              </span>
              <input type="text" 
                     name="q" 
                     class="form-control-custom form-control border-start-0" 
                     placeholder="Cari No. Laporan, Judul, Pelapor, NIM..." 
                     value="<?= e($searchKeyword) ?>"
                     style="border-radius: 0 12px 12px 0;">
            </div>
          </div>

          <div class="col-md-3">
            <select name="kategori" class="form-select-custom">
              <option value="">Semua Kategori Fasilitas</option>
              <?php foreach ($kategoriList as $kat): ?>
                <option value="<?= e($kat) ?>" <?= $kategoriFilter === $kat ? 'selected' : '' ?>>
                  <?= e($kat) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <div class="d-flex gap-1 align-items-center">
              <input type="date" name="tgl_mulai" class="form-control-custom" value="<?= e($tanggalMulai) ?>" title="Tanggal Mulai">
              <span class="text-muted small">-</span>
              <input type="date" name="tgl_selesai" class="form-control-custom" value="<?= e($tanggalSelesai) ?>" title="Tanggal Selesai">
            </div>
          </div>

          <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn-custom btn-primary-custom w-100 py-2">
              <i class="bi bi-funnel"></i> Terapkan
            </button>
            <?php if (!empty($searchKeyword) || !empty($kategoriFilter) || !empty($statusFilter) || !empty($tanggalMulai) || !empty($tanggalSelesai)): ?>
              <a href="<?= base_url('admin/pengaduan.php') ?>" class="btn btn-outline-custom py-2" title="Reset filter">
                <i class="bi bi-x-circle"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Complaints Table -->
      <div class="custom-card p-0 overflow-hidden mb-4">
        <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom bg-white">
          <div class="small text-muted">
            Menampilkan <strong><?= count($complaints) ?></strong> laporan pengaduan
          </div>
        </div>

        <?php if (empty($complaints)): ?>
          <div class="text-center py-5 px-3">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-3" style="background-color: var(--primary-light);">
              <i class="bi bi-inbox fs-2" style="color: var(--primary);"></i>
            </div>
            <h4 class="h6 fw-semibold text-dark mb-1">Tidak Ada Laporan yang Sesuai</h4>
            <p class="text-muted small mb-0">Coba ubah kata kunci atau bersihkan kriteria filter pencarian Anda.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="custom-table mb-0">
              <thead>
                <tr>
                  <th style="width: 130px;">No. Laporan</th>
                  <th>Pelapor</th>
                  <th>Judul Pengaduan</th>
                  <th>Kategori</th>
                  <th>Lokasi</th>
                  <th>Tanggal Masuk</th>
                  <th>Status</th>
                  <th class="text-end" style="width: 100px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($complaints as $c): ?>
                  <tr>
                    <td>
                      <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.76rem;">
                        <?= e($c['nomor_laporan']) ?>
                      </span>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark small"><?= e($c['nama_pelapor']) ?></div>
                      <div class="text-muted" style="font-size: 0.72rem;">NIM: <?= e($c['nim_pelapor']) ?></div>
                    </td>
                    <td>
                      <a href="<?= base_url('admin/detail-pengaduan.php?id=' . $c['id']) ?>" class="fw-semibold text-dark text-decoration-none d-block">
                        <?= e($c['judul']) ?>
                      </a>
                      <?php if (!empty($c['petugas'])): ?>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                          <i class="bi bi-person-wrench me-1 text-primary"></i>Teknisi: <?= e($c['petugas']) ?>
                        </small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="small text-muted"><?= e($c['kategori']) ?></span>
                    </td>
                    <td>
                      <div class="small fw-semibold text-dark"><?= e($c['lokasi']) ?></div>
                      <div class="text-muted" style="font-size: 0.72rem;"><?= e($c['detail_lokasi']) ?></div>
                    </td>
                    <td>
                      <span class="small text-muted"><?= date('d/m/Y, H:i', strtotime($c['created_at'])) ?></span>
                    </td>
                    <td>
                      <?= status_badge($c['status']) ?>
                    </td>
                    <td class="text-end">
                      <a href="<?= base_url('admin/detail-pengaduan.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-custom py-1 px-2" style="font-size: 0.78rem;" title="Lihat & Proses Laporan">
                        <i class="bi bi-pencil-square me-1"></i>Kelola
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

