<?php
// backend/db.php - PostgreSQL connection via PDO. Settings come from backend/config.php (see config.sample.php).
$cfgFile = __DIR__ . '/config.php';
if (!is_file($cfgFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'success' => false, 'error' => 'Server is not configured: copy backend/config.sample.php to backend/config.php.']);
    exit();
}
$cfg = require $cfgFile;

try {
    $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']}";
    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log('db.php: ' . $e->getMessage());       // details go to the server log only
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'success' => false, 'error' => 'Database connection failed.']);
    exit();
}
