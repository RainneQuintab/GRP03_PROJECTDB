<?php
// backend/patient_session.php - is a patient signed in? (the PHP session is the proof, not the browser)
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();

if (empty($_SESSION['patient_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

require_once 'db.php';   // defines $pdo (exits with a JSON error if the database is unreachable)
try {
    $stmt = $pdo->prepare('SELECT patient_id, first_name, last_name, contact_number, address, email
                           FROM patients WHERE patient_id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['patient_id']]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) {                                   // the account was deleted while signed in
        unset($_SESSION['patient_id']);
        http_response_code(401);
        echo json_encode(['ok' => false]);
        exit;
    }
    echo json_encode(['ok' => true, 'patient' => [
        'id'    => (int) $p['patient_id'],
        'fn'    => $p['first_name'],
        'ln'    => $p['last_name'],
        'ph'    => $p['contact_number'],
        'ad'    => $p['address'],
        'email' => $p['email'],
    ]]);
} catch (PDOException $e) {
    error_log('patient_session.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error.']);
}
