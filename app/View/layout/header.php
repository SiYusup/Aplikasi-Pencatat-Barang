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
  <!-- Library + helper WAJIB di <head> (tanpa defer) agar sudah tersedia saat
       script inline tiap halaman dieksekusi. Sebelumnya di footer sehingga
       script halaman jalan lebih dulu -> ReferenceError diam-diam -> tabel kosong. -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
  function escHtml(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

  async function api(path, options = {}) {
    // cache:no-store agar browser TIDAK menyajikan respons GET basi dari cache:
    // navigasi sidebar harus selalu menampilkan data terbaru dari server.
    const res = await fetch(path, { credentials: 'same-origin', cache: 'no-store', headers: { 'Content-Type': 'application/json' }, ...options });
    const ct = res.headers.get('content-type') || '';
    const data = ct.includes('json') ? await res.json() : await res.text();
    if (!res.ok) {
      const msg = (data && data.message) ? data.message : ('HTTP ' + res.status);
      throw new Error(msg);
    }
    return data;
  }

  /* Alert Bootstrap (fallback/damping SweetAlert) */
  function bootstrapAlert(msg, type) {
    type = type || 'danger';
    const host = document.querySelector('.app-content') || document.querySelector('.container');
    if (!host) { console.error(msg); return; }
    const div = document.createElement('div');
    div.className = 'alert alert-' + type + ' alert-dismissible fade show';
    div.setAttribute('role', 'alert');
    div.innerHTML = escHtml(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>';
    host.prepend(div);
    setTimeout(() => { try { div.remove(); } catch (e) {} }, 8000);
  }

  /* SweetAlert dengan fallback Bootstrap bila CDN gagal */
  function swalError(e) {
    const msg = (e && e.message) ? e.message : String(e);
    if (window.Swal) Swal.fire('Gagal', msg, 'error');
    else bootstrapAlert(msg, 'danger');
  }
  function swalOk(title, msg) {
    if (window.Swal) Swal.fire(title, msg, 'success');
    else bootstrapAlert(msg, 'success');
  }
  async function swalConfirm(opts) {
    if (window.Swal) return Swal.fire(Object.assign({ icon: 'warning', showCancelButton: true, cancelButtonText: 'Batal' }, opts));
    return { isConfirmed: window.confirm(opts.title + (opts.text ? '\n' + opts.text : '')) };
  }

  /* Inisialisasi aman (tahan bila CDN gagal dimuat) */
  function initFlatpickr(sel) {
    if (typeof flatpickr === 'undefined') return;
    document.querySelectorAll(sel).forEach(elm => flatpickr(elm, { dateFormat: 'Y-m-d' }));
  }
  function safeDataTable(sel, prev) {
    if (prev) { try { prev.destroy(); } catch (e) {} }
    try {
      if (typeof DataTable !== 'undefined') return new DataTable(sel);
      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) return window.jQuery(sel).DataTable();
    } catch (e) { console.error('DataTable:', e); }
    return null;
  }
  /* Refresh tabel DataTables dengan URUTAN AMAN: hancurkan instance lama DULU,
     baru ganti isi tbody, lalu inisialisasi ulang. Jangan dibalik: destroy()
     mengembalikan DOM ke kondisi awal sehingga baris baru ikut terhapus. */
  function refreshTable(sel, prev, html, label) {
    const table = document.querySelector(sel);
    if (!table) { console.error('refreshTable: tabel tidak ditemukan', sel); return null; }
    if (prev) { try { prev.destroy(); } catch (e) {} }
    const tb = table.querySelector('tbody');
    if (tb) tb.innerHTML = html;
    const rows = tb ? tb.querySelectorAll('tr').length : 0;
    console.info(`[${label || sel}] baris ter-render:`, rows);
    try {
      if (typeof DataTable !== 'undefined') return new DataTable(sel);
      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) return window.jQuery(sel).DataTable();
    } catch (e) { console.error('DataTable:', e); }
    return null;
  }
  function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    if (window.bootstrap && window.bootstrap.Modal) new window.bootstrap.Modal(m).show();
    else m.style.display = 'block';
  }
  function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    if (window.bootstrap && window.bootstrap.Modal) {
      const inst = window.bootstrap.Modal.getInstance(m);
      if (inst) inst.hide();
      else m.style.display = 'none';
    } else m.style.display = 'none';
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Alert dari redirect: ?ok=... (sukses) & ?logout=1 (baru logout)
    const q = new URLSearchParams(location.search);
    const ok = q.get('ok');
    if (ok) swalOk('Berhasil', ok);
    if (q.get('logout') === '1') {
      bootstrapAlert('Anda telah logout. Sampai jumpa!', 'info');
      if (window.Swal) Swal.fire('Logout', 'Anda telah logout.', 'info');
    }
    // Konfirmasi logout di sidebar
    document.querySelectorAll('a.js-logout').forEach(a => a.addEventListener('click', async (ev) => {
      ev.preventDefault();
      const c = await swalConfirm({ title: 'Yakin ingin logout?', text: 'Sesi Anda akan diakhiri dan harus login kembali.', confirmButtonText: 'Ya, logout' });
      if (c.isConfirmed) window.location.href = a.getAttribute('href');
    }));
  });
  </script>
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
    <a class="nav-link <?= $isActive('/supplier') ?>" href="/supplier"><i class="bi bi-truck me-2"></i>Supplier</a>
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
