<?php
// backend/signup.php - creates a patient in PostgreSQL and signs them in
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { fail('POST only.', 405); }

$fn  = trim($_POST['fn'] ?? '');
$ln  = trim($_POST['ln'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$ph  = preg_replace('/\D/', '', $_POST['ph'] ?? '');
$ad  = trim($_POST['ad'] ?? '');
$em  = trim($_POST['em'] ?? '');
$pw  = $_POST['password'] ?? '';

if ($fn === '' || $ln === '')                         fail('Please enter your first and last name.');
$d = DateTime::createFromFormat('Y-m-d', $dob);
if (!$d || $d->format('Y-m-d') !== $dob || $d > new DateTime('today') || $d < new DateTime('1900-01-01'))
                                                      fail('Please enter a valid date of birth.');
if (!preg_match('/^9\d{9}$/', $ph))                   fail('Enter a 10-digit mobile number starting with 9.');
if ($ad === '')                                       fail('Please enter your address.');
if (!filter_var($em, FILTER_VALIDATE_EMAIL))          fail('That email address doesn\'t look right.');
if (strlen($pw) < 8)                                  fail('Use at least 8 characters for your password.');

try {
    // which password column does this table use?
    $col = $pdo->query("SELECT column_name FROM information_schema.columns
                        WHERE table_name = 'patients' AND column_name IN ('password_hash','password','pass')
                        ORDER BY column_name = 'password_hash' DESC LIMIT 1")->fetchColumn();
    if (!$col) { fail('Server error: patients table has no password column.', 500); }

    $dup = $pdo->prepare('SELECT 1 FROM patients WHERE LOWER(email) = LOWER(:e) LIMIT 1');
    $dup->execute(['e' => $em]);
    if ($dup->fetchColumn()) { fail('That email already has an account. Try signing in instead.', 409); }

    $ins = $pdo->prepare(
        "INSERT INTO patients (first_name, last_name, date_of_birth, contact_number, address, email, \"$col\")
         VALUES (:fn, :ln, :dob, :ph, :ad, :em, :pw) RETURNING *");
    $ins->execute([
        'fn' => $fn, 'ln' => $ln, 'dob' => $dob,
        'ph' => '0' . $ph, 'ad' => $ad, 'em' => $em,
        'pw' => password_hash($pw, PASSWORD_DEFAULT),
    ]);
    $row = $ins->fetch(PDO::FETCH_ASSOC);

    session_regenerate_id(true);
    $_SESSION['patient_id'] = $row['patient_id'];

    unset($row['password'], $row['password_hash'], $row['pass']);
    $row['id'] = $row['patient_id'];
    $row['fn'] = $row['first_name'];
    echo json_encode(['ok' => true, 'patient' => $row]);
} catch (PDOException $e) {
    error_log('signup.php: ' . $e->getMessage());
    fail('Server error: ' . $e->getMessage(), 500);
}
