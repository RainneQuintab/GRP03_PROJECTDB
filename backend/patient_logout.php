<?php
// backend/patient_logout.php - ends the patient session
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
session_start();
unset($_SESSION['patient_id']);
echo json_encode(['ok' => true]);
