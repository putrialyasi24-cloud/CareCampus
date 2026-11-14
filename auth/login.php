<?php
/**
 * CareCampus - Halaman Login
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';

// Jika sudah login, redirect sesuai peran
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . base_url('admin/index.php'));
    } else {
        header('Location: ' . base_url('mahasiswa/index.php'));
    }
    exit;
}

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    }

    if (empty($identifier)) {
        $errors[] = 'Email atau NIM / Username wajib diisi.';
    }

    if (empty($password)) {
        $errors[] = 'Kata sandi wajib diisi.';
    }

    if (empty($errors)) {
        $pdo = get_db();
        // Mahasiswa bisa login pakai Email atau NIM, Admin bisa pakai Email atau NIM/ID
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR nim = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login Berhasil
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nama'] = $user['nama'];

            set_flash('success', 'Selamat datang kembali, <strong>' . e($user['nama']) . '</strong>!');

            if ($user['role'] === 'admin') {
                header('Location: ' . base_url('admin/index.php'));
            } else {
                header('Location: ' . base_url('mahasiswa/index.php'));
            }
            exit;
        } else {
            $errors[] = 'Email/NIM atau kata sandi yang Anda masukkan salah. Silakan coba kembali.';
        }
    }
}

$page_title = 'Masuk ke Akun Anda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?> - CareCampus</title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  
  <!-- Google Fonts: Poppins & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body style="background-color: var(--bg-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem 1rem;">

  <div class="container" style="max-width: 480px;">
    
    <!-- Brand Header -->
    <div class="text-center mb-4">
      <div class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle mb-2" style="background-color: var(--accent-sage-light);">
        <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo CareCampus" width="46" height="46">
      </div>
      <h1 class="h3 fw-bold mb-1" style="color: var(--primary-hover);">CareCampus</h1>
      <p class="text-muted small mb-1">Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus</p>
      <span class="badge rounded-pill" style="background-color: var(--accent-sage-light); color: var(--accent-sage-dark); border: 1px solid rgba(163,181,160,0.4); font-size: 0.72rem;">
        Karya: Putri Alya
      </span>
    </div>

    <!-- Main Card -->
    <div class="custom-card border shadow-sm p-4 p-sm-4 bg-white mb-3">
      <div class="mb-3 text-center">
        <h2 class="h5 fw-bold mb-1 text-dark">Selamat Datang</h2>
        <p class="text-muted small mb-0">Masuk dengan akun Mahasiswa atau Admin untuk melanjutkan</p>
      </div>

      <!-- Flash alerts -->
      <?php require dirname(__DIR__) . '/includes/alerts.php'; ?>

      <!-- Error box -->
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

      <form action="<?= base_url('auth/login.php') ?>" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="mb-3">
          <label for="login_identifier" class="form-label-custom">Email atau NIM / Username</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 12px 0 0 12px;">
              <i class="bi bi-person"></i>
            </span>
            <input type="text" 
                   class="form-control-custom form-control border-start-0" 
                   id="login_identifier" 
                   name="identifier" 
                   value="<?= e($identifier) ?>" 
                   placeholder="Contoh: putri@mahasiswa.ac.id atau 202401001" 
                   required 
                   autofocus
                   style="border-radius: 0 12px 12px 0;">
          </div>
        </div>

        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="login_password" class="form-label-custom mb-0">Kata Sandi</label>
          </div>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 12px 0 0 12px;">
              <i class="bi bi-lock"></i>
            </span>
            <input type="password" 
                   class="form-control-custom form-control border-start-0 border-end-0" 
                   id="login_password" 
                   name="password" 
                   placeholder="Masukkan kata sandi Anda" 
                   required
                   style="border-radius: 0;">
            <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password" data-target="login_password" style="border-radius: 0 12px 12px 0; cursor: pointer;">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-custom btn-primary-custom w-100 py-2 mt-2">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Masuk ke CareCampus</span>
        </button>
      </form>

      <!-- Fast Demo Credentials Helper -->
      <div class="mt-4 pt-3 border-top">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted" style="font-size: 0.76rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
            <i class="bi bi-lightning-charge me-1 text-warning"></i>Login Cepat Akun Demo:
          </span>
        </div>
        <div class="row g-2">
          <div class="col-6">
            <button type="button" class="btn btn-sm btn-outline-custom w-100 py-1" id="btn_demo_admin" style="font-size: 0.78rem;">
              <i class="bi bi-shield-lock me-1 text-primary"></i>Demo Admin
            </button>
          </div>
          <div class="col-6">
            <button type="button" class="btn btn-sm btn-outline-custom w-100 py-1" id="btn_demo_mahasiswa" style="font-size: 0.78rem;">
              <i class="bi bi-mortarboard me-1" style="color: var(--accent-sage-dark);"></i>Demo Mahasiswa
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Registration Link & Footer -->
    <div class="text-center">
      <p class="text-muted small mb-2">
        Belum memiliki akun mahasiswa? 
        <a href="<?= base_url('auth/register.php') ?>" class="fw-semibold" style="color: var(--primary);">Daftar Mahasiswa di sini</a>
      </p>
      <div class="text-muted" style="font-size: 0.75rem;">
        &copy; <?= date('Y') ?> CareCampus &bull; Putri Alya
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= base_url('assets/js/main.js') ?>"></script>
</body>
</html>

