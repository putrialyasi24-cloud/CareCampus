<?php
/**
 * CareCampus - Formulir Buat Pengaduan
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_mahasiswa();

$user = current_user();
$errors = [];
$formData = [
    'judul' => '',
    'kategori' => '',
    'lokasi' => '',
    'detail_lokasi' => '',
    'deskripsi' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['judul'] = trim($_POST['judul'] ?? '');
    $formData['kategori'] = trim($_POST['kategori'] ?? '');
    $formData['lokasi'] = trim($_POST['lokasi'] ?? '');
    $formData['detail_lokasi'] = trim($_POST['detail_lokasi'] ?? '');
    $formData['deskripsi'] = trim($_POST['deskripsi'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token keamanan tidak valid. Silakan coba kirim ulang.';
    }

    if (empty($formData['judul'])) {
        $errors[] = 'Judul pengaduan wajib diisi.';
    }

    if (empty($formData['kategori'])) {
        $errors[] = 'Silakan pilih kategori fasilitas.';
    }

    if (empty($formData['lokasi'])) {
        $errors[] = 'Lokasi kejadian (gedung/area) wajib diisi.';
    }

    if (empty($formData['detail_lokasi'])) {
        $errors[] = 'Detail lokasi (gedung, lantai, nomor ruangan) wajib diisi.';
    }

    if (empty($formData['deskripsi'])) {
        $errors[] = 'Deskripsi permasalahan wajib diisi secara jelas.';
    }

    // Handle Upload Foto (Opsional)
    $fotoFilename = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah foto bukti.';
        } else {
            // Cek batas ukuran (max 3MB)
            if ($file['size'] > 3 * 1024 * 1024) {
                $errors[] = 'Ukuran foto maksimal adalah 3 MB.';
            }

            // Cek format ekstensi & MIME type
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($fileExtension, $allowedExtensions) || !in_array($mimeType, $allowedMimes)) {
                $errors[] = 'Format foto tidak valid. Gunakan format JPG, PNG, atau WEBP.';
            }

            if (empty($errors)) {
                $uploadDir = dirname(__DIR__) . '/uploads/complaints/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Beri nama acak unik dan aman
                $fotoFilename = 'bukti_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $fileExtension;
                $destination = $uploadDir . $fotoFilename;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $errors[] = 'Gagal menyimpan foto yang diunggah ke server.';
                    $fotoFilename = null;
                }
            }
        }
    }

    // Jika tidak ada error, simpan laporan ke database
    if (empty($errors)) {
        $pdo = get_db();
        $nomorLaporan = generate_report_number();

        $stmt = $pdo->prepare("
            INSERT INTO complaints (
                nomor_laporan, user_id, judul, kategori, lokasi, detail_lokasi, deskripsi, foto, status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu', NOW()
            )
        ");

        $stmt->execute([
            $nomorLaporan,
            $user['id'],
            $formData['judul'],
            $formData['kategori'],
            $formData['lokasi'],
            $formData['detail_lokasi'],
            $formData['deskripsi'],
            $fotoFilename
        ]);

        $complaintId = $pdo->lastInsertId();

        // Buat catatan log awal
        $stmtLog = $pdo->prepare("
            INSERT INTO complaint_logs (complaint_id, user_id, status_sebelumnya, status_baru, keterangan, created_at)
            VALUES (?, ?, NULL, 'Menunggu', 'Laporan pengaduan berhasil disampaikan oleh mahasiswa.', NOW())
        ");
        $stmtLog->execute([$complaintId, $user['id']]);

        set_flash('success', 'Pengaduan dengan nomor laporan <strong>' . e($nomorLaporan) . '</strong> berhasil dikirimkan! Pengelola kampus akan segera meninjau laporan Anda.');
        header('Location: ' . base_url('mahasiswa/detail-pengaduan.php?id=' . $complaintId));
        exit;
    }
}

$kategoriList = get_categories();
$lokasiList = get_locations();

$page_title = 'Buat Pengaduan Fasilitas';
$active_menu = 'buat-pengaduan';
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

      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
          <li class="breadcrumb-item"><a href="<?= base_url('mahasiswa/index.php') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Buat Pengaduan</li>
        </ol>
      </nav>

      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
        <div>
          <h2 class="h4 fw-bold mb-1 text-dark">Formulir Pengaduan Fasilitas</h2>
          <p class="text-muted small mb-0">Laporkan kerusakan atau kendala fasilitas kampus dengan informasi yang akurat</p>
        </div>
        <a href="<?= base_url('mahasiswa/riwayat.php') ?>" class="btn btn-sm btn-outline-custom">
          <i class="bi bi-journal-text me-1"></i>Riwayat Pengaduan
        </a>
      </div>

      <!-- Error box -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger border-danger-subtle d-flex align-items-start gap-2 py-2 px-3 mb-4" style="border-radius: 12px; font-size: 0.88rem;">
          <i class="bi bi-exclamation-triangle-fill fs-6 mt-1 text-danger"></i>
          <div>
            <strong>Mohon perbaiki data berikut:</strong>
            <ul class="mb-0 ps-3 mt-1">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="custom-card">
            <form action="<?= base_url('mahasiswa/buat-pengaduan.php') ?>" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

              <!-- Judul -->
              <div class="mb-3">
                <label for="judul" class="form-label-custom">
                  Judul Pengaduan <span class="text-danger">*</span>
                </label>
                <input type="text" 
                       class="form-control-custom" 
                       id="judul" 
                       name="judul" 
                       value="<?= e($formData['judul']) ?>" 
                       placeholder="Contoh: AC Bocor dan Tidak Dingin di Lab Komputer 2" 
                       required>
                <div class="form-text-custom">Ringkasan singkat kendala yang Anda alami.</div>
              </div>

              <!-- Kategori & Lokasi -->
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label for="kategori" class="form-label-custom">
                    Kategori Fasilitas <span class="text-danger">*</span>
                  </label>
                  <select class="form-select-custom" id="kategori" name="kategori" required>
                    <option value="">-- Pilih Kategori Fasilitas --</option>
                    <?php foreach ($kategoriList as $kat): ?>
                      <option value="<?= e($kat) ?>" <?= $formData['kategori'] === $kat ? 'selected' : '' ?>>
                        <?= e($kat) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6">
                  <label for="lokasi" class="form-label-custom">
                    Lokasi Gedung / Area <span class="text-danger">*</span>
                  </label>
                  <select class="form-select-custom" id="lokasi" name="lokasi" required>
                    <option value="">-- Pilih Gedung / Area --</option>
                    <?php foreach ($lokasiList as $lok): ?>
                      <option value="<?= e($lok) ?>" <?= $formData['lokasi'] === $lok ? 'selected' : '' ?>>
                        <?= e($lok) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Detail Lokasi -->
              <div class="mb-3">
                <label for="detail_lokasi" class="form-label-custom">
                  Detail Lokasi (Gedung, Lantai, Nomor Ruangan) <span class="text-danger">*</span>
                </label>
                <input type="text" 
                       class="form-control-custom" 
                       id="detail_lokasi" 
                       name="detail_lokasi" 
                       value="<?= e($formData['detail_lokasi']) ?>" 
                       placeholder="Contoh: Gedung Fasilkom, Lantai 2, Ruang Lab 204 meja sisi barat" 
                       required>
                <div class="form-text-custom">Tuliskan selengkap mungkin agar petugas sarpras mudah menemukan titik lokasi.</div>
              </div>

              <!-- Deskripsi -->
              <div class="mb-3">
                <label for="deskripsi" class="form-label-custom">
                  Deskripsi Permasalahan <span class="text-danger">*</span>
                </label>
                <textarea class="form-control-custom" 
                          id="deskripsi" 
                          name="deskripsi" 
                          rows="4" 
                          placeholder="Jelaskan secara rinci kondisi kerusakan, sejak kapan terjadi, dan dampaknya terhadap kegiatan perkuliahan..." 
                          required><?= e($formData['deskripsi']) ?></textarea>
              </div>

              <!-- Upload Foto -->
              <div class="mb-4">
                <label class="form-label-custom">
                  Upload Foto Bukti Pendukung <span class="text-muted">(Opsional)</span>
                </label>
                
                <div class="upload-dropzone" id="upload_dropzone" onclick="document.getElementById('foto_bukti').click();">
                  <i class="bi bi-cloud-arrow-up upload-icon"></i>
                  <div class="fw-semibold text-dark mb-1">Klik untuk memilih foto atau seret ke sini</div>
                  <div class="text-muted small">Format didukung: JPG, PNG, WEBP (Maksimal 3 MB)</div>
                </div>

                <input type="file" 
                       id="foto_bukti" 
                       name="foto" 
                       class="d-none" 
                       accept="image/jpeg,image/png,image/webp">

                <!-- Image Preview Area -->
                <div id="preview_container" class="preview-container d-none">
                  <img id="preview_img" src="#" alt="Preview Foto Bukti">
                  <button type="button" 
                          id="remove_img_btn" 
                          class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle p-1" 
                          style="line-height: 1; width: 24px; height: 24px;" 
                          title="Hapus foto">
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                <a href="<?= base_url('mahasiswa/index.php') ?>" class="btn-custom btn-outline-custom">
                  Batal
                </a>
                <button type="submit" class="btn-custom btn-primary-custom px-4">
                  <i class="bi bi-send me-1"></i>
                  <span>Kirim Pengaduan</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Information Column -->
        <div class="col-lg-4">
          <div class="custom-card bg-white mb-3">
            <h3 class="h6 fw-bold mb-3 text-dark d-flex align-items-center gap-2">
              <i class="bi bi-info-circle text-primary"></i>
              <span>Petunjuk Pengaduan</span>
            </h3>
            <ul class="list-unstyled small text-muted mb-0 d-flex flex-column gap-2">
              <li class="d-flex align-items-start gap-2">
                <i class="bi bi-check2 text-success mt-1"></i>
                <span>Gunakan bahasa yang santun, jelas, dan mudah dimengerti.</span>
              </li>
              <li class="d-flex align-items-start gap-2">
                <i class="bi bi-check2 text-success mt-1"></i>
                <span>Sebutkan nomor ruangan dan lantai dengan tepat untuk mempercepat penanganan.</span>
              </li>
              <li class="d-flex align-items-start gap-2">
                <i class="bi bi-check2 text-success mt-1"></i>
                <span>Melampirkan foto bukti kondisi terkini sangat dianjurkan.</span>
              </li>
              <li class="d-flex align-items-start gap-2">
                <i class="bi bi-check2 text-success mt-1"></i>
                <span>Setiap laporan akan memiliki <strong>Nomor Laporan Otomatis</strong> untuk kemudahan pemantauan.</span>
              </li>
            </ul>
          </div>

          <div class="custom-card" style="background-color: var(--accent-sage-light); border-color: rgba(163,181,160,0.4);">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-shield-check" style="color: var(--accent-sage-dark); font-size: 1.25rem;"></i>
              <h4 class="h6 fw-bold mb-0" style="color: var(--accent-sage-dark);">Komitmen Sarpras Kampus</h4>
            </div>
            <p class="small mb-0" style="color: #2D3A2C;">
              Laporan fasilitas mendesak (kebocoran, listrik padam, kerusakan alat ajar) akan diverifikasi maksimal 1x24 jam pada hari kerja kampus.
            </p>
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

