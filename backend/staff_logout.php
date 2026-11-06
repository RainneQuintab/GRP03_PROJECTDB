<?php
// backend/staff_logout.php - ends the staff/admin session
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/session.php';
unset($_SESSION['staff']);
echo json_encode(['ok' => true]);
