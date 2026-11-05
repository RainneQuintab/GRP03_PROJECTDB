<?php
// backend/login.php - JSON login endpoint (PostgreSQL)
ini_set('display_errors', '0');          // keep the response pure JSON
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';                   // defines $pdo (pgsql)

function fail($msg, $code = 401) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('POST only.', 405);
}

$login    = trim($_POST['login'] ?? $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
if ($login === '' || $password === '') {
    fail('Enter your ID/email and password.', 400);
}

try {
    if (strpos($login, '@') !== false) {
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE LOWER(email) = LOWER(:v) LIMIT 1');
        $stmt->execute(['v' => $login]);
    } else {
        // "HB-00004" -> 4
        $id = (int) preg_replace('/\D/', '', $login);
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = :v LIMIT 1');
        $stmt->execute(['v' => $id]);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $col = null;
    if ($row) {
        foreach (['password_hash', 'password', 'pass'] as $c) {
            if (array_key_exists($c, $row)) { $col = $c; break; }
        }
    }
    if (!$row || !$col || $row[$col] === null || $row[$col] === '') {
        fail('Incorrect ID/email or password.');
    }

    $stored = (string) $row[$col];
    $ok = password_verify($password, $stored);
    // Temporary: passwords are currently plain text in the table. Remove once hashed.
    if (!$ok && !preg_match('/^\$(2y|argon2)/', $stored)) {
        $ok = hash_equals($stored, $password);
    }
    if (!$ok) {
        fail('Incorrect ID/email or password.');
    }

    session_regenerate_id(true);
    $_SESSION['patient_id'] = $row['patient_id'];

    unset($row['password'], $row['password_hash'], $row['pass']);   // never send it back
    $row['id'] = $row['patient_id'];
    $row['fn'] = $row['first_name'] ?? '';
    echo json_encode(['ok' => true, 'patient' => $row]);
} catch (PDOException $e) {
    error_log('login.php: ' . $e->getMessage());
    fail('Server error: ' . $e->getMessage(), 500);
}

