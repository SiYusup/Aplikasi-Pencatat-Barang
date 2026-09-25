<?php
// php scripts/migrate.php [--test] — buat database + jalankan database.sql
require __DIR__ . '/../vendor/autoload.php';

use UCrazy\AplikasiPencatatBarangCrud\Config\Database;

Database::loadEnv();
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '5432';
$user = $_ENV['DB_USER'] ?? 'postgres';
$pass = $_ENV['DB_PASS'] ?? 'ucup';
$names = [$_ENV['DB_NAME'] ?? 'pencatat_barang'];
if (in_array('--test', $argv ?? [])) {
    $names[] = $_ENV['DB_NAME_TEST'] ?? 'pencatat_barang_test';
}

$admin = new PDO("pgsql:host=$host;port=$port;dbname=postgres", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach ($names as $name) {
    $exists = $admin->query("SELECT 1 FROM pg_database WHERE datname = " . $admin->quote($name))->fetchColumn();
    if (!$exists) {
        $admin->exec('CREATE DATABASE "' . str_replace('"', '""', $name) . '"');
        echo "Database $name dibuat\n";
    } else {
        echo "Database $name sudah ada\n";
    }
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec(file_get_contents(__DIR__ . '/../database.sql'));
    echo "Migrasi $name OK\n";
}
