<?php
/**
 * CareCampus - Manajemen Pengguna (Admin)
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

require_once dirname(__DIR__) . '/config/session.php';
require_admin();

$user = current_user();
$pdo = get_db();
$errors = [];

// Proses Tambah Pengguna Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token keamanan tidak valid.';
    }

    $nama = trim($_POST['nama'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'mahasiswa';
    $prodi = trim($_POST['prodi'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');

    if (empty($nama)) $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (strlen($password) < 6) $errors[] = 'Kata sandi minimal 6 karakter.';

    if (empty($errors)) {
        // Cek duplikasi email atau NIM
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR (nim IS NOT NULL AND nim = ? AND nim != '') LIMIT 1");
        $stmtCheck->execute([$email, $nim]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'Email atau NIM sudah terdaftar untuk pengguna lain.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("
                INSERT INTO users (nama, nim, email, password, role, prodi, no_telp, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtInsert->execute([$nama, $nim ?: null, $email, $hashedPassword, $role, $prodi ?: null, $no_telp ?: null]);

            set_flash('success', "Pengguna baru <strong>" . e($nama) . "</strong> berhasil ditambahkan!");
            header('Location: ' . base_url('admin/pengguna.php'));
            exit;
        }
    }
}

// Proses Reset Password Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token keamanan tidak valid.';
    }

    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $errors[] = 'Kata sandi baru minimal 6 karakter.';
    }

    if (empty($errors) && $targetUserId > 0) {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmtReset = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmtReset->execute([$hashed, $targetUserId]);

        set_flash('success', 'Kata sandi pengguna berhasil direset.');
        header('Location: ' . base_url('admin/pengguna.php'));
        exit;
    }
}

// Proses Hapus Pengguna
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId === (int)$user['id']) {
        set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk.');
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmtDel->execute([$delId]);
        set_flash('success', 'Pengguna berhasil dihapus dari sistem.');
    }
    header('Location: ' . base_url('admin/pengguna.php'));
    exit;
}

// Filter role
$roleFilter = trim($_GET['role'] ?? '');
$searchUser = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($roleFilter)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}

if (!empty($searchUser)) {
    $sql .= " AND (nama LIKE ? OR nim LIKE ? OR email LIKE ? OR prodi LIKE ?)";
    $sw = "%{$searchUser}%";
    $params[] = $sw;
    $params[] = $sw;
    $params[] = $sw;
    $params[] = $sw;
}

$sql .= " ORDER BY role ASC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$page_title = 'Manajemen Pengguna';
$active_menu = 'admin_pengguna';
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
          <h2 class="h4 fw-bold mb-1 text-dark">Manajemen Data Pengguna</h2>
          <p class="text-muted small mb-0">Kelola akun mahasiswa pelapor dan administrator sistem CareCampus</p>
        </div>
        <button type="button" class="btn-custom btn-primary-custom py-2 px-3" data-bs-toggle="modal" data-bs-target="#modalAddUser">
          <i class="bi bi-person-plus-fill me-1"></i>Tambah Pengguna Baru
        </button>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger border-danger-subtle d-flex align-items-start gap-2 py-2 px-3 mb-4" style="border-radius: 12px; font-size: 0.88rem;">
          <i class="bi bi-exclamation-triangle-fill fs-6 mt-1 text-danger"></i>
          <div>
            <strong>Perhatian:</strong>
            <ul class="mb-0 ps-3 mt-1">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <!-- Search & Filter Card -->
      <div class="custom-card p-3 mb-4 bg-white">
        <form action="<?= base_url('admin/pengguna.php') ?>" method="GET" class="row g-2 align-items-center">
          <div class="col-md-7">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 12px 0 0 12px;">
                <i class="bi bi-search"></i>
              </span>
              <input type="text" 
                     name="q" 
                     class="form-control-custom form-control border-start-0" 
                     placeholder="Cari nama, NIM, email, atau program studi..." 
                     value="<?= e($searchUser) ?>"
                     style="border-radius: 0 12px 12px 0;">
            </div>
          </div>

          <div class="col-md-3">
            <select name="role" class="form-select-custom">
              <option value="">Semua Peran (Role)</option>
              <option value="mahasiswa" <?= $roleFilter === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
              <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
          </div>

          <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn-custom btn-primary-custom w-100 py-2">
              Filter
            </button>
            <?php if (!empty($searchUser) || !empty($roleFilter)): ?>
              <a href="<?= base_url('admin/pengguna.php') ?>" class="btn btn-outline-custom py-2" title="Reset">
                <i class="bi bi-x-circle"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Users Table -->
      <div class="custom-card p-0 overflow-hidden mb-4">
        <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom bg-white">
          <div class="small text-muted">
            Total <strong><?= count($usersList) ?></strong> pengguna terdaftar
          </div>
        </div>

        <div class="table-responsive">
          <table class="custom-table mb-0">
            <thead>
              <tr>
                <th>Pengguna</th>
                <th>Role</th>
                <th>NIM / NIP</th>
                <th>Program Studi / Unit</th>
                <th>Email & Kontak</th>
                <th>Terdaftar</th>
                <th class="text-end" style="width: 120px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usersList as $u): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="user-avatar-circle <?= $u['role'] === 'admin' ? 'admin' : '' ?>" style="width: 34px; height: 34px; font-size: 0.85rem;">
                        <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                      </div>
                      <div>
                        <div class="fw-semibold text-dark small"><?= e($u['nama']) ?></div>
                        <?php if ($u['id'] == $user['id']): ?>
                          <span class="badge bg-light text-primary border" style="font-size: 0.65rem;">Akun Anda</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($u['role'] === 'admin'): ?>
                      <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.74rem;">Administrator</span>
                    <?php else: ?>
                      <span class="badge" style="background-color: var(--accent-sage-light); color: var(--accent-sage-dark); border: 1px solid rgba(163,181,160,0.5); font-size: 0.74rem;">Mahasiswa</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="font-monospace small text-muted"><?= e($u['nim'] ?: '-') ?></span>
                  </td>
                  <td>
                    <span class="small text-muted"><?= e($u['prodi'] ?: '-') ?></span>
                  </td>
                  <td>
                    <div class="small text-dark"><?= e($u['email']) ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= e($u['no_telp'] ?: '-') ?></div>
                  </td>
                  <td>
                    <span class="small text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></span>
                  </td>
                  <td class="text-end">
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-custom py-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm border" style="border-radius: 12px; font-size: 0.85rem;">
                        <li>
                          <button type="button" 
                                  class="dropdown-item py-2 d-flex align-items-center gap-2"
                                  data-bs-toggle="modal" 
                                  data-bs-target="#modalResetPass" 
                                  data-userid="<?= $u['id'] ?>" 
                                  data-username="<?= e($u['nama']) ?>">
                            <i class="bi bi-key text-muted"></i>
                            <span>Reset Password</span>
                          </button>
                        </li>
                        <?php if ($u['id'] != $user['id']): ?>
                          <li><hr class="dropdown-divider my-1"></li>
                          <li>
                            <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" 
                               href="<?= base_url('admin/pengguna.php?delete=' . $u['id']) ?>" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus akun pengguna <?= e($u['nama']) ?>? Seluruh data terkait akan dihapus.');">
                              <i class="bi bi-trash"></i>
                              <span>Hapus Akun</span>
                            </a>
                          </li>
                        <?php endif; ?>
                      </ul>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Modal Tambah Pengguna Baru -->
    <div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
          <div class="modal-header border-bottom px-4 pt-4 pb-2">
            <h5 class="modal-title h6 fw-bold text-dark">
              <i class="bi bi-person-plus text-primary me-2"></i>Tambah Pengguna Baru
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="<?= base_url('admin/pengguna.php') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="create_user">

            <div class="modal-body px-4 py-3">
              <div class="mb-3">
                <label class="form-label-custom">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control-custom" placeholder="Nama lengkap" required>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label-custom">Role / Peran <span class="text-danger">*</span></label>
                  <select name="role" class="form-select-custom" required>
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="admin">Administrator</option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label-custom">NIM / NIP</label>
                  <input type="text" name="nim" class="form-control-custom" placeholder="Nomor identitas">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label-custom">Program Studi / Unit Kerja</label>
                <input type="text" name="prodi" class="form-control-custom" placeholder="Contoh: Teknik Informatika / Bagian Sarpras">
              </div>

              <div class="row g-2 mb-3">
                <div class="col-7">
                  <label class="form-label-custom">Alamat Email <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control-custom" placeholder="email@kampus.ac.id" required>
                </div>
                <div class="col-5">
                  <label class="form-label-custom">No. Telepon / WA</label>
                  <input type="text" name="no_telp" class="form-control-custom" placeholder="08xxxxxxxx">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label-custom">Kata Sandi Awal (min. 6 karakter) <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control-custom" placeholder="Buat kata sandi akun" required>
              </div>
            </div>

            <div class="modal-footer border-top px-4 pb-4 pt-2">
              <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn-custom btn-primary-custom px-3">
                <i class="bi bi-save me-1"></i>Simpan Pengguna
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Reset Password -->
    <div class="modal fade" id="modalResetPass" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
          <div class="modal-header border-bottom px-4 pt-4 pb-2">
            <h5 class="modal-title h6 fw-bold text-dark">
              <i class="bi bi-key text-primary me-2"></i>Reset Kata Sandi Pengguna
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="<?= base_url('admin/pengguna.php') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="target_user_id" id="reset_user_id">

            <div class="modal-body px-4 py-3">
              <p class="small text-muted mb-3">
                Anda akan mereset kata sandi untuk: <strong id="reset_user_name" class="text-dark"></strong>
              </p>

              <div class="mb-3">
                <label class="form-label-custom">Kata Sandi Baru (min. 6 karakter) <span class="text-danger">*</span></label>
                <input type="password" name="new_password" class="form-control-custom" placeholder="Masukkan kata sandi baru" required>
              </div>
            </div>

            <div class="modal-footer border-top px-4 pb-4 pt-2">
              <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn-custom btn-primary-custom px-3">
                <i class="bi bi-check-lg me-1"></i>Simpan Kata Sandi
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const modalResetPass = document.getElementById('modalResetPass');
  if (modalResetPass) {
    modalResetPass.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const userId = button.getAttribute('data-userid');
      const userName = button.getAttribute('data-username');
      
      document.getElementById('reset_user_id').value = userId;
      document.getElementById('reset_user_name').textContent = userName;
    });
  }
});
</script>

</body>
</html>

