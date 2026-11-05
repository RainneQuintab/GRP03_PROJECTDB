<?php
// backend/staff_login.php - staff / admin sign-in (PostgreSQL)
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

function fail($msg, $code = 401) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { fail('POST only.', 405); }

$email = trim($_POST['email'] ?? '');
$pw    = $_POST['password'] ?? '';
if ($email === '' || $pw === '') { fail('Enter your work email and password.', 400); }

try {
    $stmt = $pdo->prepare('SELECT user_id, first_name, last_name, email, password_hash, role, doctor_id
                           FROM staff_users
                           WHERE LOWER(email) = LOWER(:e) AND is_active = TRUE LIMIT 1');
    $stmt->execute(['e' => $email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u || !password_verify($pw, $u['password_hash'])) {
        usleep(400000);                       // slow down guessing
        fail('Incorrect email or password.');
    }

    session_regenerate_id(true);
    $_SESSION['staff'] = [
        'id'   => (int) $u['user_id'],
        'role' => $u['role'],
        'name' => $u['first_name'] . ' ' . $u['last_name'],
        'doctor_id' => $u['doctor_id'] !== null ? (int) $u['doctor_id'] : null,   // set for role 'doctor'
    ];
    echo json_encode([
        'ok'       => true,
        'user'     => ['name' => $_SESSION['staff']['name'], 'role' => $u['role']],   // admin | staff | doctor
        'redirect' => $u['role'] === 'admin' ? 'HealthBridge-admin.html' : 'HealthBridge-staff.html',
    ]);
} catch (PDOException $e) {
    error_log('staff_login.php: ' . $e->getMessage());
    fail('Server error. Please try again.', 500);
}
