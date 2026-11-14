/**
 * CareCampus - JavaScript Client Helpers
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Sidebar Toggle
  const sidebar = document.querySelector('.sidebar-wrapper');
  const toggleBtn = document.querySelector('#sidebarToggleBtn');
  const closeSidebarBtn = document.querySelector('#sidebarCloseBtn');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
    });
  }

  if (closeSidebarBtn && sidebar) {
    closeSidebarBtn.addEventListener('click', function () {
      sidebar.classList.remove('show');
    });
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function (e) {
    if (window.innerWidth < 992 && sidebar && sidebar.classList.contains('show')) {
      if (!sidebar.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
        sidebar.classList.remove('show');
      }
    }
  });

  // 2. Image Upload Preview & Validation
  const fileInput = document.getElementById('foto_bukti');
  const previewBox = document.getElementById('preview_container');
  const previewImg = document.getElementById('preview_img');
  const removeBtn = document.getElementById('remove_img_btn');
  const dropzone = document.getElementById('upload_dropzone');

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      handleFiles(this.files);
    });

    if (dropzone) {
      ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.add('border-primary');
        }, false);
      });

      ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.remove('border-primary');
        }, false);
      });

      dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
          fileInput.files = files;
          handleFiles(files);
        }
      });
    }
  }

  function handleFiles(files) {
    if (!files || files.length === 0) return;
    const file = files[0];

    // Cek ukuran max 3MB
    const maxSize = 3 * 1024 * 1024;
    if (file.size > maxSize) {
      alert('Ukuran file foto maksimal adalah 3 MB. File yang Anda pilih: ' + (file.size / (1024 * 1024)).toFixed(2) + ' MB.');
      fileInput.value = '';
      if (previewBox) previewBox.classList.add('d-none');
      return;
    }

    // Cek tipe file gambar
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    if (!validTypes.includes(file.type)) {
      alert('Format file tidak didukung. Mohon upload foto dengan format JPG, PNG, atau WEBP.');
      fileInput.value = '';
      if (previewBox) previewBox.classList.add('d-none');
      return;
    }

    // Render Preview
    const reader = new FileReader();
    reader.onload = function (e) {
      if (previewImg && previewBox) {
        previewImg.src = e.target.result;
        previewBox.classList.remove('d-none');
      }
    };
    reader.readAsDataURL(file);
  }

  if (removeBtn && fileInput) {
    removeBtn.addEventListener('click', function () {
      fileInput.value = '';
      if (previewBox) previewBox.classList.add('d-none');
      if (previewImg) previewImg.src = '';
    });
  }

  // 3. Password Visibility Toggle
  const togglePasswordBtns = document.querySelectorAll('.toggle-password');
  togglePasswordBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        if (input.type === 'password') {
          input.type = 'text';
          this.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } else {
          input.type = 'password';
          this.innerHTML = '<i class="bi bi-eye"></i>';
        }
      }
    });
  });

  // 4. Quick Demo Fill on Login Page
  const demoAdminBtn = document.getElementById('btn_demo_admin');
  const demoMahasiswaBtn = document.getElementById('btn_demo_mahasiswa');
  const loginInput = document.getElementById('login_identifier');
  const passInput = document.getElementById('login_password');

  if (demoAdminBtn && loginInput && passInput) {
    demoAdminBtn.addEventListener('click', function (e) {
      e.preventDefault();
      loginInput.value = 'admin@carecampus.ac.id';
      passInput.value = 'admin123';
      highlightFields([loginInput, passInput]);
    });
  }

  if (demoMahasiswaBtn && loginInput && passInput) {
    demoMahasiswaBtn.addEventListener('click', function (e) {
      e.preventDefault();
      loginInput.value = 'putri@mahasiswa.ac.id';
      passInput.value = 'mahasiswa123';
      highlightFields([loginInput, passInput]);
    });
  }

  function highlightFields(elements) {
    elements.forEach(el => {
      el.classList.add('bg-light');
      setTimeout(() => el.classList.remove('bg-light'), 400);
    });
  }

  // 5. Auto hide alerts after 5 seconds
  const autoAlerts = document.querySelectorAll('.alert-dismissible');
  autoAlerts.forEach(alertEl => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
      if (bsAlert) {
        bsAlert.close();
      }
    }, 5000);
  });
});

