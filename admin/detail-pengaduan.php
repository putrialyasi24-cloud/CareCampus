<?php
/**
 * CareCampus - Detail & Kelola Pengaduan (Admin)
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_admin();

$user = current_user();
$pdo = get_db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data pengaduan beserta data mahasiswa pelapor
$stmt = $pdo->prepare("
    SELECT c.*, u.nama as nama_pelapor, u.nim as nim_pelapor, u.prodi as prodi_pelapor, u.email as email_pelapor, u.no_telp as telp_pelapor
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('danger', 'Laporan pengaduan tidak ditemukan.');
    header('Location: ' . base_url('admin/pengaduan.php'));
    exit;
}

$errors = [];

// Proses Update Status & Catatan Tindak Lanjut oleh Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $status_baru = trim($_POST['status'] ?? '');
    $catatan_admin = trim($_POST['catatan_admin'] ?? '');
    $petugas = trim($_POST['petugas'] ?? '');

    if (!verify_csrf_token($token)) {
        $errors[] = 'Token keamanan tidak valid. Silakan coba kembali.';
    }

    $validStatuses = ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'];
    if (!in_array($status_baru, $validStatuses)) {
        $errors[] = 'Status pengaduan tidak valid.';
    }

    if ($status_baru === 'Ditolak' && empty($catatan_admin)) {
        $errors[] = 'Jika laporan ditolak, Anda wajib memberikan alasan/catatan penolakan.';
    }

    if (empty($errors)) {
        $status_sebelumnya = $complaint['status'];

        $stmtUpdate = $pdo->prepare("
            UPDATE complaints 
            SET status = ?, catatan_admin = ?, petugas = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$status_baru, $catatan_admin, $petugas, $id]);

        // Catat ke complaint_logs jika ada perubahan status atau catatan
        $logKeterangan = $catatan_admin ?: ("Status diubah dari '{$status_sebelumnya}' menjadi '{$status_baru}' oleh Admin.");
        if (!empty($petugas)) {
            $logKeterangan .= " (Teknisi: {$petugas})";
        }

        $stmtLog = $pdo->prepare("
            INSERT INTO complaint_logs (complaint_id, user_id, status_sebelumnya, status_baru, keterangan, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtLog->execute([$id, $user['id'], $status_sebelumnya, $status_baru, $logKeterangan]);

        set_flash('success', "Status laporan <strong>{$complaint['nomor_laporan']}</strong> berhasil diperbarui menjadi <strong>{$status_baru}</strong>.");
        header('Location: ' . base_url('admin/detail-pengaduan.php?id=' . $id));
        exit;
    }
}

// Ambil riwayat log penanganan
$stmtLogs = $pdo->prepare("
    SELECT l.*, u.nama as nama_aktor, u.role as role_aktor 
    FROM complaint_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.complaint_id = ?
    ORDER BY l.created_at DESC
");
$stmtLogs->execute([$id]);
$logs = $stmtLogs->fetchAll();

$page_title = 'Kelola Laporan ' . $complaint['nomor_laporan'];
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

      <!-- Breadcrumb & Top Buttons -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('admin/pengaduan.php') ?>">Semua Pengaduan</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($complaint['nomor_laporan']) ?></li>
          </ol>
        </nav>
        <div class="d-flex gap-2 btn-no-print">
          <button type="button" class="btn btn-sm btn-outline-custom" onclick="window.print();">
            <i class="bi bi-printer me-1"></i>Cetak Laporan
          </button>
          <a href="<?= base_url('admin/pengaduan.php') ?>" class="btn btn-sm btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i>Kembali
          </a>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger border-danger-subtle d-flex align-items-start gap-2 py-2 px-3 mb-4" style="border-radius: 12px; font-size: 0.88rem;">
          <i class="bi bi-exclamation-triangle-fill fs-6 mt-1 text-danger"></i>
          <div>
            <strong>Pemberitahuan:</strong>
            <ul class="mb-0 ps-3 mt-1">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <!-- Top Header Card -->
      <div class="custom-card mb-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.85rem;">
                <?= e($complaint['nomor_laporan']) ?>
              </span>
              <?= status_badge($complaint['status']) ?>
            </div>
            <h2 class="h4 fw-bold mb-1 text-dark"><?= e($complaint['judul']) ?></h2>
            <div class="text-muted small">
              <i class="bi bi-calendar3 me-1"></i>Tanggal Masuk: <?= format_date($complaint['created_at']) ?>
              &bull; Terakhir Diperbarui: <?= format_date($complaint['updated_at']) ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Kolom Kiri: Detail Laporan, Foto, dan Riwayat Log (7 columns) -->
        <div class="col-lg-7">
          <div class="custom-card mb-4 bg-white">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-card-text text-primary"></i>
              <span>Rincian Pengaduan Fasilitas</span>
            </h3>

            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label-custom text-muted small mb-0">Kategori Fasilitas</label>
                <div class="fw-semibold text-dark"><?= e($complaint['kategori']) ?></div>
              </div>
              <div class="col-sm-6">
                <label class="form-label-custom text-muted small mb-0">Lokasi Gedung / Area</label>
                <div class="fw-semibold text-dark"><?= e($complaint['lokasi']) ?></div>
              </div>
              <div class="col-12">
                <label class="form-label-custom text-muted small mb-0">Detail Lokasi & Nomor Ruangan</label>
                <div class="p-2 rounded bg-light border text-dark small">
                  <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                  <?= e($complaint['detail_lokasi']) ?>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label-custom text-muted small mb-1">Deskripsi Permasalahan oleh Mahasiswa</label>
              <div class="p-3 rounded bg-light border text-dark" style="white-space: pre-line; line-height: 1.7;">
                <?= e($complaint['deskripsi']) ?>
              </div>
            </div>

            <!-- Foto Bukti -->
            <div class="mt-4 pt-3 border-top">
              <label class="form-label-custom text-muted small mb-2 d-block">Foto Bukti Kerusakan</label>
              <?php if (!empty($complaint['foto'])): ?>
                <?php $fotoUrl = base_url('uploads/complaints/' . $complaint['foto']); ?>
                <div class="d-inline-block position-relative">
                  <a href="<?= $fotoUrl ?>" target="_blank" title="Klik untuk membuka ukuran penuh">
                    <img src="<?= $fotoUrl ?>" 
                         alt="Bukti Foto" 
                         class="img-fluid rounded border shadow-sm" 
                         style="max-height: 300px; object-fit: cover;">
                  </a>
                  <div class="small text-muted mt-1">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Klik gambar untuk membuka ukuran penuh di tab baru
                  </div>
                </div>
              <?php else: ?>
                <div class="p-3 rounded bg-light border text-muted small text-center">
                  <i class="bi bi-image text-secondary fs-4 d-block mb-1"></i>
                  Mahasiswa tidak melampirkan foto pada pengaduan ini.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Riwayat Log Penanganan -->
          <div class="custom-card bg-white">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-clock-history text-primary"></i>
              <span>Riwayat Aktivitas & Tindak Lanjut</span>
            </h3>

            <?php if (empty($logs)): ?>
              <p class="text-muted small mb-0">Belum ada catatan aktivitas.</p>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($logs as $log): ?>
                  <div class="p-3 rounded bg-light border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <?= status_badge($log['status_baru']) ?>
                        <span class="small fw-semibold text-dark">
                          <?= e($log['nama_aktor'] ?: 'Admin') ?>
                        </span>
                      </div>
                      <span class="text-muted" style="font-size: 0.72rem;">
                        <?= format_date($log['created_at']) ?>
                      </span>
                    </div>
                    <p class="text-muted small mb-0 mt-2"><?= e($log['keterangan']) ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Kolom Kanan: Form Update Status & Data Pelapor (5 columns) -->
        <div class="col-lg-5">
          <!-- Form Tindak Lanjut Admin -->
          <div class="custom-card bg-white mb-4">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-sliders text-primary"></i>
              <span>Proses & Tindak Lanjut Laporan</span>
            </h3>

            <form action="<?= base_url('admin/detail-pengaduan.php?id=' . $id) ?>" method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

              <div class="mb-3">
                <label for="status" class="form-label-custom">
                  Status Pengaduan <span class="text-danger">*</span>
                </label>
                <select class="form-select-custom" id="status" name="status" required>
                  <option value="Menunggu" <?= $complaint['status'] === 'Menunggu' ? 'selected' : '' ?>>
                    Menunggu (Baru Diterima)
                  </option>
                  <option value="Diproses" <?= $complaint['status'] === 'Diproses' ? 'selected' : '' ?>>
                    Diproses (Sedang Ditangani)
                  </option>
                  <option value="Selesai" <?= $complaint['status'] === 'Selesai' ? 'selected' : '' ?>>
                    Selesai (Perbaikan Rampung)
                  </option>
                  <option value="Ditolak" <?= $complaint['status'] === 'Ditolak' ? 'selected' : '' ?>>
                    Ditolak (Tidak Dapat Diproses)
                  </option>
                </select>
                <div class="form-text-custom">Pilih status terkini dari progres penanganan fasilitas ini.</div>
              </div>

              <div class="mb-3">
                <label for="petugas" class="form-label-custom">Teknisi / Petugas Penangan</label>
                <input type="text" 
                       class="form-control-custom" 
                       id="petugas" 
                       name="petugas" 
                       value="<?= e($complaint['petugas'] ?? '') ?>" 
                       placeholder="Contoh: Pak Hadi (Teknisi Listrik)">
                <div class="form-text-custom">Nama teknisi atau staf sarpras yang ditugaskan.</div>
              </div>

              <div class="mb-4">
                <label for="catatan_admin" class="form-label-custom">
                  Catatan / Keterangan Tindak Lanjut
                </label>
                <textarea class="form-control-custom" 
                          id="catatan_admin" 
                          name="catatan_admin" 
                          rows="4" 
                          placeholder="Tuliskan keterangan perbaikan, jadwal kunjungan teknisi, suku cadang yang diganti, atau alasan jika laporan ditolak..."><?= e($complaint['catatan_admin'] ?? '') ?></textarea>
                <div class="form-text-custom">Catatan ini dapat dibaca oleh mahasiswa pelapor.</div>
              </div>

              <button type="submit" class="btn-custom btn-primary-custom w-100 py-2">
                <i class="bi bi-check2-circle me-1"></i>Simpan Perubahan Status
              </button>
            </form>
          </div>

          <!-- Data Pelapor (Mahasiswa) -->
          <div class="custom-card bg-white">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-person-badge text-primary"></i>
              <span>Informasi Pelapor</span>
            </h3>

            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="user-avatar-circle" style="width: 44px; height: 44px; font-size: 1.1rem;">
                <?= strtoupper(substr($complaint['nama_pelapor'], 0, 1)) ?>
              </div>
              <div>
                <div class="fw-bold text-dark"><?= e($complaint['nama_pelapor']) ?></div>
                <div class="text-muted small">NIM: <?= e($complaint['nim_pelapor']) ?></div>
              </div>
            </div>

            <table class="table table-borderless table-sm small mb-0">
              <tr>
                <td class="text-muted" style="width: 110px;">Program Studi</td>
                <td class="fw-semibold text-dark">: <?= e($complaint['prodi_pelapor'] ?: '-') ?></td>
              </tr>
              <tr>
                <td class="text-muted">Email</td>
                <td class="fw-semibold text-dark">: <?= e($complaint['email_pelapor']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">No. Telepon</td>
                <td class="fw-semibold text-dark">: <?= e($complaint['telp_pelapor'] ?: '-') ?></td>
              </tr>
            </table>
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

