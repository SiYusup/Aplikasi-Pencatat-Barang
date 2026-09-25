<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($model['title'] ?? 'Pencatat Barang') ?> — Pencatat Barang</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background: #f1f5f9; }
    .app-sidebar {
      width: 260px; min-height: 100vh; position: fixed; top: 0; left: 0; z-index: 100;
      background: #0f172a; color: #e2e8f0; display: flex; flex-direction: column;
    }
    .app-sidebar .brand { padding: 20px 20px 12px; font-weight: 700; font-size: 18px; color: #fff; }
    .app-sidebar .brand small { display: block; font-weight: 400; font-size: 12px; color: #94a3b8; }
    .app-sidebar .nav-link { color: #cbd5e1; border-radius: 8px; margin: 2px 12px; padding: 10px 14px; }
    .app-sidebar .nav-link:hover { background: #1e293b; color: #fff; }
    .app-sidebar .nav-link.active { background: #2563eb; color: #fff; }
    .app-sidebar .nav-section { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin: 14px 20px 4px; }
    .app-sidebar .sidebar-footer { margin-top: auto; padding: 14px 20px; border-top: 1px solid #1e293b; font-size: 13px; color: #94a3b8; }
    .app-main { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; }
    .app-topbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 28px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
    .app-content { padding: 28px; flex: 1; }
    .stat-card { border-left: 5px solid #0d6efd; }
    @media (max-width: 767px) {
      .app-sidebar { position: static; width: 100%; min-height: auto; }
      .app-main { margin-left: 0; }
    }
  </style>
</head>
<body>
<?php
$user = $model['user'] ?? null;
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
// $exact=true untuk '/barang' agar TIDAK ikut aktif saat di /barang/masuk & /barang/keluar.
$isActive = function (string $prefix, bool $exact = false) use ($uri): string {
  if ($prefix === '/') return $uri === '/' ? 'active' : '';
  if ($exact) return $uri === $prefix ? 'active' : '';
  return ($uri === $prefix || str_starts_with($uri, $prefix . '/')) ? 'active' : '';
};
?>
<?php if ($user): ?>
<aside class="app-sidebar">
  <div class="brand"><i class="bi bi-box-seam"></i> Pencatat Barang<small>Inventory Management</small></div>
  <div class="nav-section">Menu Utama</div>
  <nav class="nav flex-column">
    <a class="nav-link <?= $isActive('/') ?>" href="/"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a class="nav-link <?= $isActive('/barang', true) ?>" href="/barang"><i class="bi bi-boxes me-2"></i>Data Barang</a>
    <a class="nav-link <?= $isActive('/kategori') ?>" href="/kategori"><i class="bi bi-tags me-2"></i>Kategori</a>
    <a class="nav-link <?= $isActive('/barang/masuk') ?>" href="/barang/masuk"><i class="bi bi-box-arrow-in-down me-2"></i>Barang Masuk</a>
    <a class="nav-link <?= $isActive('/barang/keluar') ?>" href="/barang/keluar"><i class="bi bi-box-arrow-up me-2"></i>Barang Keluar</a>
    <a class="nav-link <?= $isActive('/laporan') ?>" href="/laporan"><i class="bi bi-file-earmark-text me-2"></i>Laporan</a>
  </nav>
  <div class="nav-section">Akun</div>
  <nav class="nav flex-column">
    <a class="nav-link <?= $isActive('/users/profile') ?>" href="/users/profile"><i class="bi bi-person-circle me-2"></i>Profile</a>
    <a class="nav-link <?= $isActive('/users/password') ?>" href="/users/password"><i class="bi bi-key me-2"></i>Ganti Password</a>
    <a class="nav-link js-logout" href="/users/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
  </nav>
  <div class="sidebar-footer"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($user->name) ?> (<?= htmlspecialchars($user->role) ?>)</div>
</aside>
<div class="app-main">
  <div class="app-topbar">
    <h5 class="mb-0"><?= htmlspecialchars($model['title'] ?? 'Dashboard') ?></h5>
    <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y') ?></span>
  </div>
  <div class="app-content">
<?php else: ?>
<div class="container py-5">
<?php endif; ?>
<?php if (!empty($model['error'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($model['error']) ?></div>
<?php endif; ?>
