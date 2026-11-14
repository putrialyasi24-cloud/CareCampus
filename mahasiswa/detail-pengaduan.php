<?php
/**
 * CareCampus - Detail Pengaduan Mahasiswa
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_mahasiswa();

$user = current_user();
$pdo = get_db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Keamanan: pastikan pengaduan ini milik mahasiswa yang sedang login!
$stmt = $pdo->prepare("
    SELECT c.*, u.nama as nama_pelapor, u.nim as nim_pelapor, u.prodi as prodi_pelapor, u.email as email_pelapor
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ? AND c.user_id = ?
    LIMIT 1
");
$stmt->execute([$id, $user['id']]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('danger', 'Laporan pengaduan tidak ditemukan atau Anda tidak memiliki akses ke laporan tersebut.');
    header('Location: ' . base_url('mahasiswa/riwayat.php'));
    exit;
}

// Ambil riwayat log penanganan
$stmtLogs = $pdo->prepare("
    SELECT l.*, u.nama as nama_aktor, u.role as role_aktor 
    FROM complaint_logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.complaint_id = ?
    ORDER BY l.created_at ASC
");
$stmtLogs->execute([$id]);
$logs = $stmtLogs->fetchAll();

$page_title = 'Detail Laporan ' . $complaint['nomor_laporan'];
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

      <!-- Breadcrumb & Actions -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="<?= base_url('mahasiswa/index.php') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('mahasiswa/riwayat.php') ?>">Riwayat</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($complaint['nomor_laporan']) ?></li>
          </ol>
        </nav>
        <div class="d-flex gap-2 btn-no-print">
          <button type="button" class="btn btn-sm btn-outline-custom" onclick="window.print();">
            <i class="bi bi-printer me-1"></i>Cetak Bukti Laporan
          </button>
          <a href="<?= base_url('mahasiswa/riwayat.php') ?>" class="btn btn-sm btn-outline-custom">
            <i class="bi bi-arrow-left me-1"></i>Kembali
          </a>
        </div>
      </div>

      <!-- Top Summary Card -->
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
              <i class="bi bi-calendar3 me-1"></i>Dilaporkan pada: <?= format_date($complaint['created_at']) ?>
            </div>
          </div>
        </div>

        <!-- Visual Status Progress Bar -->
        <?php
        $statusCurrent = $complaint['status'];
        $step1Class = 'completed';
        $step2Class = ($statusCurrent === 'Diproses' || $statusCurrent === 'Selesai') ? 'completed' : ($statusCurrent === 'Menunggu' ? 'active' : '');
        $step3Class = ($statusCurrent === 'Selesai') ? 'completed' : ($statusCurrent === 'Diproses' ? 'active' : '');
        ?>
        <div class="pt-3 border-top mt-3">
          <div class="timeline-track">
            <!-- Step 1: Menunggu -->
            <div class="timeline-step <?= $step1Class ?>">
              <div class="timeline-dot"><i class="bi bi-check-lg"></i></div>
              <div class="timeline-title">Laporan Diterima</div>
              <div class="timeline-desc small text-muted">Menunggu verifikasi admin</div>
            </div>

            <!-- Step 2: Diproses -->
            <div class="timeline-step <?= $step2Class ?>">
              <div class="timeline-dot">
                <i class="bi <?= ($statusCurrent === 'Diproses' || $statusCurrent === 'Selesai') ? 'bi-tools' : 'bi-hourglass' ?>"></i>
              </div>
              <div class="timeline-title">Sedang Diproses</div>
              <div class="timeline-desc small text-muted">Teknisi memeriksa sarana</div>
            </div>

            <!-- Step 3: Selesai / Ditolak -->
            <?php if ($statusCurrent === 'Ditolak'): ?>
              <div class="timeline-step active">
                <div class="timeline-dot" style="background-color: var(--status-ditolak-bg); border-color: var(--status-ditolak-border); color: var(--status-ditolak-text);">
                  <i class="bi bi-x-lg"></i>
                </div>
                <div class="timeline-title text-danger">Laporan Ditolak</div>
                <div class="timeline-desc small text-muted">Lihat alasan penolakan di bawah</div>
              </div>
            <?php else: ?>
              <div class="timeline-step <?= $step3Class ?>">
                <div class="timeline-dot">
                  <i class="bi <?= $statusCurrent === 'Selesai' ? 'bi-check-all' : 'bi-flag' ?>"></i>
                </div>
                <div class="timeline-title">Selesai</div>
                <div class="timeline-desc small text-muted">Fasilitas selesai diperbaiki</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Main Details Column -->
        <div class="col-lg-8">
          <div class="custom-card mb-4 bg-white">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-card-text text-primary"></i>
              <span>Rincian Permasalahan</span>
            </h3>

            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label-custom text-muted small mb-0">Kategori Fasilitas</label>
                <div class="fw-semibold text-dark"><?= e($complaint['kategori']) ?></div>
              </div>
              <div class="col-sm-6">
                <label class="form-label-custom text-muted small mb-0">Gedung / Lokasi Utama</label>
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
              <label class="form-label-custom text-muted small mb-1">Deskripsi Lengkap Laporan</label>
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
                  <a href="<?= $fotoUrl ?>" target="_blank" title="Klik untuk memperbesar gambar">
                    <img src="<?= $fotoUrl ?>" 
                         alt="Foto Bukti Kerusakan" 
                         class="img-fluid rounded border shadow-sm" 
                         style="max-height: 280px; object-fit: cover;">
                  </a>
                  <div class="small text-muted mt-1">
                    <i class="bi bi-zoom-in me-1"></i>Klik foto untuk melihat ukuran penuh
                  </div>
                </div>
              <?php else: ?>
                <div class="p-3 rounded bg-light border text-muted small text-center">
                  <i class="bi bi-image text-secondary fs-4 d-block mb-1"></i>
                  Tidak ada foto bukti yang dilampirkan pada pengaduan ini.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Riwayat Penanganan (Timeline Logs) -->
          <div class="custom-card bg-white">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-clock-history text-primary"></i>
              <span>Riwayat Pembaruan Status</span>
            </h3>

            <?php if (empty($logs)): ?>
              <p class="text-muted small mb-0">Belum ada riwayat pembaruan status.</p>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($logs as $log): ?>
                  <div class="d-flex align-items-start gap-3 p-2 rounded bg-light border">
                    <div class="pt-1">
                      <?= status_badge($log['status_baru']) ?>
                    </div>
                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark">
                          <?= e($log['nama_aktor'] ?: 'Sistem') ?> 
                          <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">
                            <?= e($log['role_aktor'] ?: 'System') ?>
                          </span>
                        </span>
                        <span class="text-muted" style="font-size: 0.72rem;">
                          <?= format_date($log['created_at']) ?>
                        </span>
                      </div>
                      <p class="text-muted small mb-0"><?= e($log['keterangan'] ?: 'Pembaruan status laporan.') ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Sidebar Details Column -->
        <div class="col-lg-4">
          <!-- Status & Tindak Lanjut Card -->
          <div class="custom-card bg-white mb-4">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-chat-left-quote text-primary"></i>
              <span>Tindak Lanjut Admin</span>
            </h3>

            <?php if (!empty($complaint['catatan_admin'])): ?>
              <div class="p-3 rounded mb-3" style="background-color: var(--accent-sage-light); border: 1px solid rgba(163,181,160,0.5);">
                <div class="fw-semibold small mb-1" style="color: var(--accent-sage-dark);">
                  <i class="bi bi-info-circle me-1"></i>Catatan Pengelola Sarpras:
                </div>
                <div class="small" style="color: #2F3E2E; line-height: 1.6;">
                  <?= e($complaint['catatan_admin']) ?>
                </div>
              </div>
            <?php else: ?>
              <div class="p-3 rounded bg-light border text-muted small text-center mb-3">
                Belum ada catatan tindak lanjut dari admin sarpras.
              </div>
            <?php endif; ?>

            <?php if (!empty($complaint['petugas'])): ?>
              <div class="mb-3">
                <label class="form-label-custom text-muted small mb-0">Teknisi / Petugas Penangan</label>
                <div class="fw-semibold text-dark small">
                  <i class="bi bi-person-badge text-primary me-1"></i>
                  <?= e($complaint['petugas']) ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="text-muted small pt-2 border-top">
              Terakhir diperbarui: <?= format_date($complaint['updated_at']) ?>
            </div>
          </div>

          <!-- Data Pelapor Info -->
          <div class="custom-card bg-white">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-person-lines-fill text-primary"></i>
              <span>Data Pelapor</span>
            </h3>
            <table class="table table-borderless table-sm small mb-0">
              <tr>
                <td class="text-muted" style="width: 100px;">Nama</td>
                <td class="fw-semibold text-dark">: <?= e($complaint['nama_pelapor']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">NIM</td>
                <td class="fw-semibold text-dark">: <?= e($complaint['nim_pelapor']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">Prodi</td>
                <td class="fw-semibold text-dark">: <?= e($complaint['prodi_pelapor']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">Email</td>
                <td class="fw-semibold text-dark">: <?= e($complaint['email_pelapor']) ?></td>
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

