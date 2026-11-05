<?php
// backend/get_doctors.php
header('Content-Type: application/json');
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT d.doctor_id, d.first_name, d.last_name, d.email, s.specialty_name 
                         FROM doctors d 
                         LEFT JOIN specialties s ON d.specialty_id = s.specialty_id");
    $doctors = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('get_doctors.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
?>
