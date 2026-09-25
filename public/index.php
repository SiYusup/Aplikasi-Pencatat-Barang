<?php

require_once __DIR__ . '/../vendor/autoload.php';

use UCrazy\AplikasiPencatatBarangCrud\App\Router;
use UCrazy\AplikasiPencatatBarangCrud\Config\Database;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\AuthApiController;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\BarangApiController;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\KategoriApiController;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\LaporanApiController;
use UCrazy\AplikasiPencatatBarangCrud\Controller\Api\SupplierApiController;
use UCrazy\AplikasiPencatatBarangCrud\Controller\HomeController;
use UCrazy\AplikasiPencatatBarangCrud\Controller\UserController;
use UCrazy\AplikasiPencatatBarangCrud\Middleware\ApiAuthMiddleware;
use UCrazy\AplikasiPencatatBarangCrud\Middleware\MustLoginMiddleware;
use UCrazy\AplikasiPencatatBarangCrud\Middleware\MustNotLoginMiddleware;

Database::getConnection('prod');

// ---- Web (HTML + Bootstrap, data via REST API di bawah) ----
Router::add('GET', '/', HomeController::class, 'index', [MustLoginMiddleware::class]);
Router::add('GET', '/barang', HomeController::class, 'barang', [MustLoginMiddleware::class]);
Router::add('GET', '/kategori', HomeController::class, 'kategori', [MustLoginMiddleware::class]);
Router::add('GET', '/supplier', HomeController::class, 'supplier', [MustLoginMiddleware::class]);
Router::add('GET', '/barang/masuk', HomeController::class, 'barangMasuk', [MustLoginMiddleware::class]);
Router::add('GET', '/barang/keluar', HomeController::class, 'barangKeluar', [MustLoginMiddleware::class]);
Router::add('GET', '/laporan', HomeController::class, 'laporan', [MustLoginMiddleware::class]);

Router::add('GET', '/users/register', UserController::class, 'register', [MustNotLoginMiddleware::class]);
Router::add('POST', '/users/register', UserController::class, 'postRegister', [MustNotLoginMiddleware::class]);
Router::add('GET', '/users/login', UserController::class, 'login', [MustNotLoginMiddleware::class]);
Router::add('POST', '/users/login', UserController::class, 'postLogin', [MustNotLoginMiddleware::class]);
Router::add('GET', '/users/logout', UserController::class, 'logout', [MustLoginMiddleware::class]);
Router::add('GET', '/users/profile', UserController::class, 'profile', [MustLoginMiddleware::class]);
Router::add('POST', '/users/profile', UserController::class, 'postUpdateProfile', [MustLoginMiddleware::class]);
Router::add('GET', '/users/password', UserController::class, 'password', [MustLoginMiddleware::class]);
Router::add('POST', '/users/password', UserController::class, 'postUpdatePassword', [MustLoginMiddleware::class]);

// ---- REST API (JSON, transfer data utama) ----
Router::add('POST', '/api/v1/auth/register', AuthApiController::class, 'register', []);
Router::add('POST', '/api/v1/auth/login', AuthApiController::class, 'login', []);
Router::add('POST', '/api/v1/auth/logout', AuthApiController::class, 'logout', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/profile', AuthApiController::class, 'profile', [ApiAuthMiddleware::class]);
Router::add('PUT', '/api/v1/profile', AuthApiController::class, 'updateProfile', [ApiAuthMiddleware::class]);
Router::add('PUT', '/api/v1/profile/password', AuthApiController::class, 'updatePassword', [ApiAuthMiddleware::class]);

Router::add('GET', '/api/v1/dashboard/summary', LaporanApiController::class, 'summary', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/dashboard/chart', LaporanApiController::class, 'chart', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/dashboard/low-stock', LaporanApiController::class, 'lowStock', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/notifikasi', LaporanApiController::class, 'notifikasi', [ApiAuthMiddleware::class]);

Router::add('GET', '/api/v1/barang', BarangApiController::class, 'list', [ApiAuthMiddleware::class]);
Router::add('POST', '/api/v1/barang', BarangApiController::class, 'create', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/barang/([0-9]+)', BarangApiController::class, 'show', [ApiAuthMiddleware::class]);
Router::add('PUT', '/api/v1/barang/([0-9]+)', BarangApiController::class, 'update', [ApiAuthMiddleware::class]);
Router::add('DELETE', '/api/v1/barang/([0-9]+)', BarangApiController::class, 'delete', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/barang/([0-9]+)/history', BarangApiController::class, 'history', [ApiAuthMiddleware::class]);
Router::add('POST', '/api/v1/barang/([0-9]+)/opname', BarangApiController::class, 'opname', [ApiAuthMiddleware::class]);

Router::add('GET', '/api/v1/barang-masuk', BarangApiController::class, 'listMasuk', [ApiAuthMiddleware::class]);
Router::add('POST', '/api/v1/barang-masuk', BarangApiController::class, 'createMasuk', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/barang-masuk/([0-9]+)', BarangApiController::class, 'showMasuk', [ApiAuthMiddleware::class]);
Router::add('DELETE', '/api/v1/barang-masuk/([0-9]+)', BarangApiController::class, 'deleteMasuk', [ApiAuthMiddleware::class]);

Router::add('GET', '/api/v1/barang-keluar', BarangApiController::class, 'listKeluar', [ApiAuthMiddleware::class]);
Router::add('POST', '/api/v1/barang-keluar', BarangApiController::class, 'createKeluar', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/barang-keluar/([0-9]+)', BarangApiController::class, 'showKeluar', [ApiAuthMiddleware::class]);
Router::add('DELETE', '/api/v1/barang-keluar/([0-9]+)', BarangApiController::class, 'deleteKeluar', [ApiAuthMiddleware::class]);

Router::add('GET', '/api/v1/kategori', KategoriApiController::class, 'list', [ApiAuthMiddleware::class]);
Router::add('POST', '/api/v1/kategori', KategoriApiController::class, 'create', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/kategori/([0-9]+)', KategoriApiController::class, 'show', [ApiAuthMiddleware::class]);
Router::add('PUT', '/api/v1/kategori/([0-9]+)', KategoriApiController::class, 'update', [ApiAuthMiddleware::class]);
Router::add('DELETE', '/api/v1/kategori/([0-9]+)', KategoriApiController::class, 'delete', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/supplier', SupplierApiController::class, 'list', [ApiAuthMiddleware::class]);
Router::add('POST', '/api/v1/supplier', SupplierApiController::class, 'create', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/supplier/([0-9]+)', SupplierApiController::class, 'show', [ApiAuthMiddleware::class]);
Router::add('PUT', '/api/v1/supplier/([0-9]+)', SupplierApiController::class, 'update', [ApiAuthMiddleware::class]);
Router::add('DELETE', '/api/v1/supplier/([0-9]+)', SupplierApiController::class, 'delete', [ApiAuthMiddleware::class]);

Router::add('GET', '/api/v1/laporan/stok', LaporanApiController::class, 'laporanStok', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/laporan/masuk', LaporanApiController::class, 'laporanMasuk', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/laporan/keluar', LaporanApiController::class, 'laporanKeluar', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/laporan/mutasi', LaporanApiController::class, 'laporanMutasi', [ApiAuthMiddleware::class]);
Router::add('GET', '/api/v1/audit-logs', LaporanApiController::class, 'auditLogs', [ApiAuthMiddleware::class]);

Router::add('GET', '/api/v1/export/(barang|masuk|keluar|laporan)', LaporanApiController::class, 'export', [ApiAuthMiddleware::class]);

Router::run();
