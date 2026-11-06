<?php
// backend/visit_notes.php - diagnosis and prescriptions for a visit (PostgreSQL)
//   POST action=save  appointment_id, diagnosis, observations, rx (JSON list of {med,dose,ins})
//        doctor only, their own Scheduled appointment: saves the note + prescriptions and
//        marks the appointment Completed, all in one transaction
//   GET  -> notes and prescriptions the signed-in account may see
//        staff/admin: all   doctor: their own visits   patient: their own visits
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();
require_once 'db.php';

function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }

$staff = $_SESSION['staff'] ?? null;
$pid   = $_SESSION['patient_id'] ?? null;
if (!$staff && !$pid) { fail('Please sign in.', 401); }

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $where = ''; $args = [];
        if ($staff) {
            if ($staff['role'] === 'doctor') { $where = 'WHERE a.doctor_id = :x'; $args['x'] = (int) ($staff['doctor_id'] ?? 0); }
        } else {
            $where = 'WHERE a.patient_id = :x'; $args['x'] = (int) $pid;
        }
        $n = $pdo->prepare("SELECT n.note_id, n.appointment_id, n.diagnosis, n.clinical_observations, n.recorded_at
                            FROM medical_notes n JOIN appointments a ON a.appointment_id = n.appointment_id $where
                            ORDER BY n.note_id");
        $n->execute($args);
        $r = $pdo->prepare("SELECT r.prescription_id, r.appointment_id, r.medication_name, r.dosage, r.instructions
                            FROM prescriptions r JOIN appointments a ON a.appointment_id = r.appointment_id $where
                            ORDER BY r.prescription_id");
        $r->execute($args);
        out(['ok' => true, 'notes' => $n->fetchAll(PDO::FETCH_ASSOC), 'prescriptions' => $r->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'save') { fail('Unknown action.', 405); }
    if (!$staff || $staff['role'] !== 'doctor') { fail('Only the doctor can complete a visit.', 403); }

    $id = (int) ($_POST['appointment_id'] ?? 0);
    $dx = trim($_POST['diagnosis'] ?? '');
    $ob = trim($_POST['observations'] ?? '');
    if ($dx === '') { fail('Diagnosis is required.'); }
    $rx = json_decode($_POST['rx'] ?? '[]', true);
    if (!is_array($rx)) { $rx = []; }

    $pdo->beginTransaction();
    $cur = $pdo->prepare('SELECT doctor_id, status FROM appointments WHERE appointment_id = :a FOR UPDATE');
    $cur->execute(['a' => $id]);
    $row = $cur->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $pdo->rollBack(); fail('Appointment not found.', 404); }
    if ((int) $row['doctor_id'] !== (int) ($staff['doctor_id'] ?? 0)) { $pdo->rollBack(); fail('That appointment belongs to another doctor.', 403); }
    if ($row['status'] !== 'Scheduled') { $pdo->rollBack(); fail('Only a Scheduled appointment can be completed.', 409); }

    $pdo->prepare('INSERT INTO medical_notes (appointment_id, diagnosis, clinical_observations) VALUES (:a, :d, :o)')
        ->execute(['a' => $id, 'd' => $dx, 'o' => $ob]);
    $ins = $pdo->prepare('INSERT INTO prescriptions (appointment_id, medication_name, dosage, instructions) VALUES (:a, :m, :d, :i)');
    foreach ($rx as $item) {
        if (!is_array($item)) { continue; }
        $m = mb_substr(trim($item['med'] ?? ''), 0, 100);
        if ($m === '') { continue; }
        $ins->execute(['a' => $id, 'm' => $m, 'd' => mb_substr(trim($item['dose'] ?? ''), 0, 50), 'i' => trim($item['ins'] ?? '')]);
    }
    $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = :a")->execute(['a' => $id]);
    $pdo->commit();
    out(['ok' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('visit_notes.php: ' . $e->getMessage());
    fail('Server error while saving the visit.', 500);
}