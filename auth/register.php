<?php
/**
 * CareCampus - Registrasi Mahasiswa Baru
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';

if (is_logged_in()) {
    header('Location: ' . (is_admin() ? base_url('admin/index.php') : base_url('mahasiswa/index.php')));
    exit;
}

$errors = [];
$formData = [
    'nama' => '',
    'nim' => '',
    'prodi' => '',
    'email' => '',
    'no_telp' => ''
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $formData['nama'] = trim($_POST['nama'] ?? '');
    $formData['nim'] = trim($_POST['nim'] ?? '');
    $formData['prodi'] = trim($_POST['prodi'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['no_telp'] = trim($_POST['no_telp'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $errors[] = 'Token keamanan tidak valid. Silakan coba lagi.';
    }

    if (empty($formData['nama'])) {
        $errors[] = 'Nama lengkap wajib diisi.';
    }

    if (empty($formData['nim'])) {
        $errors[] = 'NIM (Nomor Induk Mahasiswa) wajib diisi.';
    }

    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid atau belum diisi.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Kata sandi minimal 6 karakter.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Konfirmasi kata sandi tidak cocok.';
    }

    if (empty($errors)) {
        $pdo = get_db();

        // Cek duplikasi email atau NIM
        $stmt = $pdo->prepare("SELECT id, email, nim FROM users WHERE email = ? OR nim = ? LIMIT 1");
        $stmt->execute([$formData['email'], $formData['nim']]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['email'] === $formData['email']) {
                $errors[] = 'Email ini sudah terdaftar. Silakan gunakan email lain atau masuk.';
            } else {
                $errors[] = 'NIM ini sudah terdaftar di sistem CareCampus.';
            }
        } else {
            // Simpan akun mahasiswa baru
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $insertStmt = $pdo->prepare("
                INSERT INTO users (nama, nim, email, password, role, prodi, no_telp, created_at)
                VALUES (?, ?, ?, ?, 'mahasiswa', ?, ?, NOW())
            ");
            
            $insertStmt->execute([
                $formData['nama'],
                $formData['nim'],
                $formData['email'],
                $hashedPassword,
                $formData['prodi'] ?: 'Mahasiswa Kampus',
                $formData['no_telp'] ?: null
            ]);

            set_flash('success', 'Pendaftaran akun mahasiswa berhasil! Silakan masuk menggunakan email/NIM dan kata sandi Anda.');
            header('Location: ' . base_url('auth/login.php'));
            exit;
        }
    }
}

$page_title = 'Daftar Akun Mahasiswa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?> - CareCampus</title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body style="background-color: var(--bg-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">

  <div class="container" style="max-width: 540px;">
    
    <!-- Brand Header -->
    <div class="text-center mb-3">
      <a href="<?= base_url('auth/login.php') ?>" class="text-decoration-none">
        <div class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle mb-1" style="background-color: var(--accent-sage-light);">
          <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo CareCampus" width="40" height="40">
        </div>
        <h1 class="h4 fw-bold mb-0" style="color: var(--primary-hover);">CareCampus</h1>
      </a>
      <p class="text-muted small">Pendaftaran Akun Pelapor Mahasiswa</p>
    </div>

    <!-- Register Card -->
    <div class="custom-card border shadow-sm p-4 bg-white mb-3">
      <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <h2 class="h6 fw-bold mb-0 text-dark">
          <i class="bi bi-person-plus me-1 text-primary"></i>Form Registrasi Mahasiswa
        </h2>
        <a href="<?= base_url('auth/login.php') ?>" class="small text-muted">Sudah punya akun? Masuk</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger border-danger-subtle d-flex align-items-start gap-2 py-2 px-3 mb-3" style="border-radius: 10px; font-size: 0.85rem;">
          <i class="bi bi-exclamation-triangle-fill fs-6 mt-1 text-danger"></i>
          <div>
            <?php foreach ($errors as $err): ?>
              <div><?= e($err) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <form action="<?= base_url('auth/register.php') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="mb-3">
          <label class="form-label-custom">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control-custom" value="<?= e($formData['nama']) ?>" placeholder="Contoh: Putri Alya" required>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label-custom">NIM Mahasiswa <span class="text-danger">*</span></label>
            <input type="text" name="nim" class="form-control-custom" value="<?= e($formData['nim']) ?>" placeholder="Contoh: 202401001" required>
          </div>
          <div class="col-md-6">
            <label class="form-label-custom">Program Studi</label>
            <input type="text" name="prodi" class="form-control-custom" value="<?= e($formData['prodi']) ?>" placeholder="Contoh: Teknik Informatika">
          </div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-7">
            <label class="form-label-custom">Alamat Email Kampus/Pribadi <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control-custom" value="<?= e($formData['email']) ?>" placeholder="contoh@mahasiswa.ac.id" required>
          </div>
          <div class="col-md-5">
            <label class="form-label-custom">No. Telepon / WA</label>
            <input type="text" name="no_telp" class="form-control-custom" value="<?= e($formData['no_telp']) ?>" placeholder="08xxxxxxxxxx">
          </div>
        </div>

        <div class="row g-2 mb-4">
          <div class="col-md-6">
            <label class="form-label-custom">Kata Sandi (min. 6 karakter) <span class="text-danger">*</span></label>
            <input type="password" name="password" id="reg_pass" class="form-control-custom" placeholder="Buat kata sandi" required>
          </div>
          <div class="col-md-6">
            <label class="form-label-custom">Ulangi Kata Sandi <span class="text-danger">*</span></label>
            <input type="password" name="password_confirm" id="reg_pass_conf" class="form-control-custom" placeholder="Ulangi kata sandi" required>
          </div>
        </div>

        <button type="submit" class="btn-custom btn-primary-custom w-100 py-2">
          <i class="bi bi-check-lg"></i>
          <span>Daftar Sekarang</span>
        </button>
      </form>
    </div>

    <div class="text-center text-muted small">
      <a href="<?= base_url('auth/login.php') ?>" class="text-secondary text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Halaman Masuk
      </a>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

