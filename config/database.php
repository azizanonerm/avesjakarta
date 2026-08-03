<?php
/**
 * Database Configuration & Connection
 * AVES Jakarta Expert System
 * Using PDO for secure database operations
 */

require_once __DIR__ . '/security.php';

/**
 * Prioritas konfigurasi:
 * 1. Environment variable (server yang mendukungnya).
 * 2. config/database.credentials.php (shared hosting seperti InfinityFree).
 * 3. Nilai lokal XAMPP.
 *
 * File database.credentials.php sengaja tidak disimpan di repository karena
 * berisi password hosting.
 */
$credentialsFile = __DIR__ . '/database.credentials.php';
$fileCredentials = [];

if (is_file($credentialsFile)) {
    $loadedCredentials = require $credentialsFile;
    if (!is_array($loadedCredentials)) {
        error_log('database.credentials.php must return an array.');
        http_response_code(500);
        exit('Format konfigurasi database tidak valid.');
    }
    $fileCredentials = $loadedCredentials;
}

$environmentValue = static function (string $name): ?string {
    $value = getenv($name);
    return $value === false || $value === '' ? null : (string) $value;
};

$serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
$isLocalServer = PHP_SAPI === 'cli'
    || $serverName === ''
    || in_array($serverName, ['localhost', '127.0.0.1', '::1'], true)
    || str_ends_with($serverName, '.test')
    || str_ends_with($serverName, '.local');

$dbHost = $environmentValue('AVES_DB_HOST')
    ?? (string) ($fileCredentials['host'] ?? ($isLocalServer ? 'localhost' : ''));
$dbUser = $environmentValue('AVES_DB_USER')
    ?? (string) ($fileCredentials['user'] ?? ($isLocalServer ? 'root' : ''));
$dbPass = $environmentValue('AVES_DB_PASS')
    ?? (string) ($fileCredentials['pass'] ?? '');
$dbName = $environmentValue('AVES_DB_NAME')
    ?? (string) ($fileCredentials['name'] ?? ($isLocalServer ? 'aves_jakarta' : ''));
$dbPort = $environmentValue('AVES_DB_PORT')
    ?? (string) ($fileCredentials['port'] ?? '3306');

if ($dbHost === '' || $dbUser === '' || $dbName === '') {
    error_log('Hosting database credentials are incomplete.');
    http_response_code(500);
    exit('Konfigurasi database hosting belum diisi.');
}

define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);
define('DB_PORT', $dbPort);

try {
    // Create PDO connection
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database sedang bermasalah. Silakan coba kembali nanti.');
}

// Helper function to execute queries safely
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Database query failed: ' . $e->getMessage());
        throw $e;
    }
}

// Helper function to fetch single row
function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

// Helper function to fetch all rows
function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

// Helper function to insert/update/delete
function execute($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->rowCount();
}
?>
