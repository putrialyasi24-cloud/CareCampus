<?php
/**
 * CareCampus - Rekapitulasi & Cetak Laporan (Admin)
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_admin();

$user = current_user();
$pdo = get_db();

// Filter
$statusFilter = trim($_GET['status'] ?? '');
$kategoriFilter = trim($_GET['kategori'] ?? '');
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

if (!empty($tanggalMulai)) {
    $sql .= " AND DATE(c.created_at) >= ?";
    $params[] = $tanggalMulai;
}

if (!empty($tanggalSelesai)) {
    $sql .= " AND DATE(c.created_at) <= ?";
    $params[] = $tanggalSelesai;
}

$sql .= " ORDER BY c.created_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

// Hitung rekap
$totalCount = count($complaints);
$selesaiCount = 0;
$diprosesCount = 0;
$menungguCount = 0;
$ditolakCount = 0;

foreach ($complaints as $c) {
    if ($c['status'] === 'Selesai') $selesaiCount++;
    elseif ($c['status'] === 'Diproses') $diprosesCount++;
    elseif ($c['status'] === 'Menunggu') $menungguCount++;
    elseif ($c['status'] === 'Ditolak') $ditolakCount++;
}

$kategoriList = get_categories();

$page_title = 'Rekapitulasi Laporan Sarpras';
$active_menu = 'admin_laporan';
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

      <!-- Filter Controls (Hidden when printing) -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 btn-no-print">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Rekapitulasi Laporan Kerusakan Kampus</h2>
          <p class="text-muted small mb-0">Cetak rekap laporan pengaduan fasilitas untuk evaluasi sarana prasarana</p>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn-custom btn-primary-custom py-2 px-3" onclick="window.print();">
            <i class="bi bi-printer-fill me-1"></i>Cetak Dokumen
          </button>
        </div>
      </div>

      <div class="custom-card p-3 mb-4 bg-white btn-no-print">
        <form action="<?= base_url('admin/laporan.php') ?>" method="GET" class="row g-2 align-items-center">
          <div class="col-md-3">
            <label class="form-label-custom small mb-1">Status Laporan</label>
            <select name="status" class="form-select-custom">
              <option value="">Semua Status</option>
              <option value="Menunggu" <?= $statusFilter === 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
              <option value="Diproses" <?= $statusFilter === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
              <option value="Selesai" <?= $statusFilter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
              <option value="Ditolak" <?= $statusFilter === 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label-custom small mb-1">Kategori Fasilitas</label>
            <select name="kategori" class="form-select-custom">
              <option value="">Semua Kategori</option>
              <?php foreach ($kategoriList as $kat): ?>
                <option value="<?= e($kat) ?>" <?= $kategoriFilter === $kat ? 'selected' : '' ?>><?= e($kat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label-custom small mb-1">Rentang Tanggal Masuk</label>
            <div class="d-flex gap-1 align-items-center">
              <input type="date" name="tgl_mulai" class="form-control-custom" value="<?= e($tanggalMulai) ?>">
              <span class="text-muted small">-</span>
              <input type="date" name="tgl_selesai" class="form-control-custom" value="<?= e($tanggalSelesai) ?>">
            </div>
          </div>

          <div class="col-md-2 d-flex gap-1 pt-md-4">
            <button type="submit" class="btn-custom btn-primary-custom w-100 py-2">
              <i class="bi bi-funnel"></i> Filter
            </button>
            <?php if (!empty($statusFilter) || !empty($kategoriFilter) || !empty($tanggalMulai) || !empty($tanggalSelesai)): ?>
              <a href="<?= base_url('admin/laporan.php') ?>" class="btn btn-outline-custom py-2" title="Reset">
                <i class="bi bi-x-circle"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Printable Report Document Sheet -->
      <div class="custom-card p-4 p-md-5 bg-white shadow-sm border">
        
        <!-- Official Header (Kop Surat Resmi) -->
        <div class="text-center border-bottom pb-4 mb-4">
          <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
            <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo" width="48" height="48">
            <div class="text-start">
              <h1 class="h5 fw-bold mb-0 text-dark" style="letter-spacing: 0.05em; text-transform: uppercase;">
                SISTEM INFORMASI CARECAMPUS
              </h1>
              <div class="small text-muted fw-semibold">BAGIAN SARANA DAN PRASARANA KAMPUS</div>
            </div>
          </div>
          <h2 class="h6 fw-bold mb-1 text-dark text-uppercase mt-2">
            LAPORAN REKAPITULASI PENGADUAN & KERUSAKAN FASILITAS
          </h2>
          <div class="small text-muted">
            Periode: <?= $tanggalMulai ? format_date($tanggalMulai, false) : 'Awal' ?> s/d <?= $tanggalSelesai ? format_date($tanggalSelesai, false) : 'Sekarang' ?>
            &bull; Dicetak pada: <?= format_date(date('Y-m-d H:i:s')) ?>
          </div>
        </div>

        <!-- Summary Mini Cards -->
        <div class="row g-2 mb-4">
          <div class="col-3">
            <div class="p-2 border rounded text-center bg-light">
              <div class="small text-muted">Total Laporan</div>
              <div class="fw-bold fs-5 text-dark"><?= $totalCount ?></div>
            </div>
          </div>
          <div class="col-3">
            <div class="p-2 border rounded text-center bg-light">
              <div class="small text-muted">Selesai</div>
              <div class="fw-bold fs-5 text-success"><?= $selesaiCount ?></div>
            </div>
          </div>
          <div class="col-3">
            <div class="p-2 border rounded text-center bg-light">
              <div class="small text-muted">Diproses</div>
              <div class="fw-bold fs-5 text-primary"><?= $diprosesCount ?></div>
            </div>
          </div>
          <div class="col-3">
            <div class="p-2 border rounded text-center bg-light">
              <div class="small text-muted">Menunggu</div>
              <div class="fw-bold fs-5 text-secondary"><?= $menungguCount ?></div>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle small mb-0">
            <thead class="table-light">
              <tr class="text-center">
                <th style="width: 40px;">No</th>
                <th style="width: 120px;">No. Laporan</th>
                <th style="width: 130px;">Pelapor (NIM)</th>
                <th>Judul & Deskripsi</th>
                <th style="width: 130px;">Kategori</th>
                <th style="width: 130px;">Lokasi</th>
                <th style="width: 90px;">Tanggal</th>
                <th style="width: 90px;">Status</th>
                <th style="width: 130px;">Teknisi / Catatan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($complaints)): ?>
                <tr>
                  <td colspan="9" class="text-center py-4 text-muted">
                    Tidak ada data pengaduan yang sesuai dengan kriteria filter.
                  </td>
                </tr>
              <?php else: ?>
                <?php $no = 1; foreach ($complaints as $c): ?>
                  <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="font-monospace fw-semibold"><?= e($c['nomor_laporan']) ?></td>
                    <td>
                      <div><?= e($c['nama_pelapor']) ?></div>
                      <small class="text-muted"><?= e($c['nim_pelapor']) ?></small>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark"><?= e($c['judul']) ?></div>
                      <small class="text-muted d-block" style="line-height: 1.3;">
                        <?= e(substr($c['deskripsi'], 0, 80)) ?><?= strlen($c['deskripsi']) > 80 ? '...' : '' ?>
                      </small>
                    </td>
                    <td><?= e($c['kategori']) ?></td>
                    <td>
                      <div><?= e($c['lokasi']) ?></div>
                      <small class="text-muted"><?= e($c['detail_lokasi']) ?></small>
                    </td>
                    <td class="text-center"><?= date('d/m/y', strtotime($c['created_at'])) ?></td>
                    <td class="text-center">
                      <?= status_badge($c['status']) ?>
                    </td>
                    <td>
                      <?php if (!empty($c['petugas'])): ?>
                        <div class="fw-semibold text-dark small"><?= e($c['petugas']) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($c['catatan_admin'])): ?>
                        <small class="text-muted d-block" style="line-height: 1.2;">
                          <?= e($c['catatan_admin']) ?>
                        </small>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Signature Section -->
        <div class="row mt-5 pt-4 text-center justify-content-between">
          <div class="col-4">
            <div class="small text-muted mb-5">Petugas Sarpras,</div>
            <div class="fw-bold text-dark text-decoration-underline"><?= e($user['nama']) ?></div>
            <div class="small text-muted">NIP. ADM-001</div>
          </div>
          <div class="col-4">
            <div class="small text-muted mb-5">
              Mengetahui,<br>Kepala Bagian Sarana & Prasarana
            </div>
            <div class="fw-bold text-dark text-decoration-underline">Ir. Hendra Kusuma, M.T.</div>
            <div class="small text-muted">NIP. 19780512 200501 1 002</div>
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

