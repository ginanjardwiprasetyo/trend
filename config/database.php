<?php
/**
 * Konfigurasi Koneksi Database PostgreSQL
 * TrendHidro - Aplikasi Pemetaan Tren Hidrologi
 */

// Fungsi sederhana untuk membaca file .env
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Muat .env jika ada
loadEnv(__DIR__ . '/../.env');

define('DB_HOST', 'aws-1-ap-southeast-1.pooler.supabase.com');
define('DB_PORT', '6543');
define('DB_USER', 'postgres.watukhizpufbukhfhpcl');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', 'postgres');


/**
 * Mendapatkan koneksi database PostgreSQL (Supabase) menggunakan PDO
 * @return PDO
 */
function getConnection()
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        // Coba gunakan port 6543 (Pooler) untuk bypass blokade port 5432
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=prefer;";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::ATTR_TIMEOUT            => 10, // 10 seconds timeout
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => 'Koneksi database gagal: ' . $e->getMessage(),
            'details' => 'Pastikan ekstensi pdo_pgsql aktif di php.ini'
        ]);
        exit;
    }
}