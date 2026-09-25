<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Config;

use Dotenv\Dotenv;

class Database
{
    private static ?\PDO $pdo = null;
    private static ?string $activeEnv = null;
    private static bool $dotenvLoaded = false;

    public static function loadEnv(): void
    {
        if (self::$dotenvLoaded) {
            return;
        }
        self::$dotenvLoaded = true;
        $root = dirname(__DIR__, 2);
        if (file_exists($root . '/.env')) {
            Dotenv::createImmutable($root)->safeLoad();
        }
    }

    /**
     * Menggantikan config array (referensi php-login-management/config/database.php)
     * dengan vlucas/phpdotenv (.env). $env: "prod" | "test".
     */
    public static function getConnection(string $env = 'prod'): \PDO
    {
        self::loadEnv();

        // APP_ENV=test (phpunit) otomatis pakai database testing
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? ($_SERVER['APP_ENV'] ?? 'prod'));
        if ($appEnv === 'test') {
            $env = 'test';
        }

        if (self::$pdo !== null && self::$activeEnv === $env) {
            return self::$pdo;
        }

        $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
        $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432');
        $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'postgres');
        $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'ucup');
        $name = $env === 'test'
            ? (getenv('DB_NAME_TEST') ?: ($_ENV['DB_NAME_TEST'] ?? 'pencatat_barang_test'))
            : (getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'pencatat_barang'));

        self::$pdo = new \PDO(
            "pgsql:host=$host;port=$port;dbname=$name",
            $user,
            $pass,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        self::$activeEnv = $env;

        return self::$pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
        self::$activeEnv = null;
    }

    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }

    public static function commitTransaction(): void
    {
        self::getConnection()->commit();
    }

    public static function rollbackTransaction(): void
    {
        if (self::$pdo !== null && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }
}
