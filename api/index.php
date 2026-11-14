<?php
/**
 * CareCampus - Vercel Serverless Function Dispatcher
 * Router utama untuk deployment Vercel
 * Pemilik Proyek: Putri Alya
 */

if (!ob_get_level()) {
    ob_start();
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = urldecode($uri);
$path = ltrim($uri, '/');

$rootDir = dirname(__DIR__);

// Handle root URL
if (empty($path) || $path === 'index.php') {
    chdir($rootDir);
    require $rootDir . '/index.php';
    exit;
}

$target = $rootDir . '/' . $path;

// Jika direktori diminta, cari index.php di dalamnya
if (is_dir($target)) {
    $target = rtrim($target, '/') . '/index.php';
} elseif (!preg_match('/\.php$/', $target) && file_exists($target . '.php')) {
    $target .= '.php';
}

// Eksekusi berkas PHP yang cocok
if (file_exists($target) && is_file($target) && pathinfo($target, PATHINFO_EXTENSION) === 'php') {
    chdir(dirname($target));
    require $target;
    exit;
}

// Layani berkas statis jika lewat router
if (file_exists($target) && is_file($target)) {
    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    $mimes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'sql'  => 'text/plain'
    ];
    if (isset($mimes[$ext])) {
        header("Content-Type: {$mimes[$ext]}");
    }
    readfile($target);
    exit;
}

// 404
http_response_code(404);
echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;'><h2>404 - Halaman Tidak Ditemukan</h2><p><a href='/'>Kembali ke CareCampus</a></p></body></html>";
exit;
