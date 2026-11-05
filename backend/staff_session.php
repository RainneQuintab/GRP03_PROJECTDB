<?php
// backend/staff_session.php - who is signed in as staff/admin?
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();

if (empty($_SESSION['staff'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}
$s = $_SESSION['staff'];
echo json_encode([
    'ok'       => true,
    'name'     => $s['name'],
    'role'     => $s['role'],            // admin | staff | doctor
    'doctor_id'=> $s['doctor_id'] ?? null,
    'redirect' => $s['role'] === 'admin' ? 'HealthBridge-admin.html' : 'HealthBridge-staff.html',
]);

