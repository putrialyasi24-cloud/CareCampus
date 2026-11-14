<?php
/**
 * CareCampus - Flash Alerts Component
 * Sistem Informasi Pengaduan & Pelaporan Fasilitas Kampus
 * Pemilik Proyek: Putri Alya
 */

$flash = get_flash();
if ($flash):
    $type = $flash['type'];
    $icon = 'bi-info-circle';
    $customClass = 'alert-secondary';

    if ($type === 'success') {
        $icon = 'bi-check-circle-fill';
        $customClass = 'alert-success border-success-subtle text-success-emphasis';
    } elseif ($type === 'danger') {
        $icon = 'bi-exclamation-triangle-fill';
        $customClass = 'alert-danger border-danger-subtle text-danger-emphasis';
    } elseif ($type === 'warning') {
        $icon = 'bi-exclamation-circle-fill';
        $customClass = 'alert-warning border-warning-subtle text-warning-emphasis';
    } elseif ($type === 'info') {
        $icon = 'bi-info-circle-fill';
        $customClass = 'alert-info border-info-subtle text-info-emphasis';
    }
?>
<div class="alert <?= $customClass ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-4 py-2 px-3 shadow-sm" role="alert" style="border-radius: 12px; font-size: 0.9rem;">
  <i class="bi <?= $icon ?> fs-5"></i>
  <div class="flex-grow-1">
    <?= $flash['message'] ?>
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 0.85rem;"></button>
</div>
<?php endif; ?>

