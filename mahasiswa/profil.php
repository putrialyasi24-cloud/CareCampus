<?php
/**
 * CareCampus - Profil Mahasiswa
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_mahasiswa();

$user = current_user();
$pdo = get_db();
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $errors[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    }

    if ($action === 'update_profile' && empty($errors)) {
        $nama = trim($_POST['nama'] ?? '');
        $prodi = trim($_POST['prodi'] ?? '');
        $no_telp = trim($_POST['no_telp'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nama)) {
            $errors[] = 'Nama lengkap wajib diisi.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Alamat email tidak valid.';
        }

        // Cek jika email dipakai akun lain
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email tersebut sudah digunakan oleh akun lain.';
            } else {
                $stmtUpdate = $pdo->prepare("
                    UPDATE users 
                    SET nama = ?, prodi = ?, no_telp = ?, email = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$nama, $prodi, $no_telp, $email, $user['id']]);
                set_flash('success', 'Profil Anda berhasil diperbarui!');
                header('Location: ' . base_url('mahasiswa/profil.php'));
                exit;
            }
        }
    } elseif ($action === 'change_password' && empty($errors)) {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($old_password)) {
            $errors[] = 'Kata sandi saat ini wajib diisi.';
        }

        if (strlen($new_password) < 6) {
            $errors[] = 'Kata sandi baru minimal 6 karakter.';
        }

        if ($new_password !== $confirm_password) {
            $errors[] = 'Konfirmasi kata sandi baru tidak sesuai.';
        }

        if (empty($errors)) {
            // Cek password lama
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();

            if (!password_verify($old_password, $userData['password'])) {
                $errors[] = 'Kata sandi saat ini yang Anda masukkan salah.';
            } else {
                $newHash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmtPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmtPass->execute([$newHash, $user['id']]);
                set_flash('success', 'Kata sandi berhasil diubah! Gunakan kata sandi baru untuk login berikutnya.');
                header('Location: ' . base_url('mahasiswa/profil.php'));
                exit;
            }
        }
    }
}

// Refresh user data
$user = current_user();

$page_title = 'Profil Saya';
$active_menu = 'profil';
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
          <li class="breadcrumb-item active" aria-current="page">Profil Mahasiswa</li>
        </ol>
      </nav>

      <div class="mb-4">
        <h2 class="h4 fw-bold mb-1 text-dark">Profil Mahasiswa</h2>
        <p class="text-muted small mb-0">Kelola informasi data diri dan keamanan akun CareCampus Anda</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger border-danger-subtle d-flex align-items-start gap-2 py-2 px-3 mb-4" style="border-radius: 12px; font-size: 0.88rem;">
          <i class="bi bi-exclamation-triangle-fill fs-6 mt-1 text-danger"></i>
          <div>
            <strong>Terjadi kendala:</strong>
            <ul class="mb-0 ps-3 mt-1">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <div class="row g-4">
        <!-- Edit Profile Form -->
        <div class="col-lg-7">
          <div class="custom-card bg-white">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-person-gear text-primary"></i>
              <span>Informasi Pribadi</span>
            </h3>

            <form action="<?= base_url('mahasiswa/profil.php') ?>" method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="update_profile">

              <div class="mb-3">
                <label class="form-label-custom">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control-custom" value="<?= e($user['nama']) ?>" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label-custom">NIM (Nomor Induk Mahasiswa)</label>
                  <input type="text" class="form-control-custom bg-light" value="<?= e($user['nim'] ?? '-') ?>" readonly title="NIM tidak dapat diubah">
                  <div class="form-text-custom">Hubungi sarpras/admin jika terdapat kesalahan NIM.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label-custom">Program Studi</label>
                  <input type="text" name="prodi" class="form-control-custom" value="<?= e($user['prodi'] ?? '') ?>" placeholder="Contoh: Teknik Informatika">
                </div>
              </div>

              <div class="row g-3 mb-4">
                <div class="col-md-7">
                  <label class="form-label-custom">Alamat Email <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control-custom" value="<?= e($user['email']) ?>" required>
                </div>
                <div class="col-md-5">
                  <label class="form-label-custom">No. Telepon / WhatsApp</label>
                  <input type="text" name="no_telp" class="form-control-custom" value="<?= e($user['no_telp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                </div>
              </div>

              <div class="text-end pt-2 border-top">
                <button type="submit" class="btn-custom btn-primary-custom px-4">
                  <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Change Password Form -->
        <div class="col-lg-5">
          <div class="custom-card bg-white">
            <h3 class="h6 fw-bold mb-3 text-dark pb-2 border-bottom d-flex align-items-center gap-2">
              <i class="bi bi-shield-lock text-primary"></i>
              <span>Keamanan & Sandi</span>
            </h3>

            <form action="<?= base_url('mahasiswa/profil.php') ?>" method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="change_password">

              <div class="mb-3">
                <label class="form-label-custom">Kata Sandi Saat Ini <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" name="old_password" id="old_pass" class="form-control-custom form-control border-end-0" placeholder="Masukkan kata sandi lama" required style="border-radius: 12px 0 0 12px;">
                  <button type="button" class="input-group-text bg-white border-start-0 text-muted toggle-password" data-target="old_pass" style="border-radius: 0 12px 12px 0; cursor: pointer;">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label-custom">Kata Sandi Baru (min. 6 karakter) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" name="new_password" id="new_pass" class="form-control-custom form-control border-end-0" placeholder="Kata sandi baru" required style="border-radius: 12px 0 0 12px;">
                  <button type="button" class="input-group-text bg-white border-start-0 text-muted toggle-password" data-target="new_pass" style="border-radius: 0 12px 12px 0; cursor: pointer;">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label-custom">Ulangi Kata Sandi Baru <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" name="confirm_password" id="conf_pass" class="form-control-custom form-control border-end-0" placeholder="Ulangi kata sandi baru" required style="border-radius: 12px 0 0 12px;">
                  <button type="button" class="input-group-text bg-white border-start-0 text-muted toggle-password" data-target="conf_pass" style="border-radius: 0 12px 12px 0; cursor: pointer;">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <button type="submit" class="btn-custom btn-outline-custom w-100 py-2">
                <i class="bi bi-key me-1"></i>Perbarui Kata Sandi
              </button>
            </form>
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

