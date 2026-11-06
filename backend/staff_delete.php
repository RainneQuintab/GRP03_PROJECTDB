<?php
// backend/staff_delete.php - delete an appointment or a patient from the active data (PostgreSQL)
//   POST type=appointment id=<appointment_id>   only Completed / Cancelled ones
//   POST type=patient     id=<patient_id>       the patient and all their appointments
// Staff and admin only. A full copy goes to deleted_records first, all in one transaction.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/session.php';
require_once 'db.php';

function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }

if (empty($_SESSION['staff'])) { fail('Please sign in.', 401); }
$role = $_SESSION['staff']['role'];
if (!in_array($role, ['staff', 'admin'], true)) { fail('Only staff or admin can delete records.', 403); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { fail('Unknown action.', 405); }

$type = $_POST['type'] ?? '';
$id   = (int) ($_POST['id'] ?? 0);
if (!in_array($type, ['appointment', 'patient'], true) || $id <= 0) { fail('Bad request.'); }

function rows($pdo, $sql, $args) { $s = $pdo->prepare($sql); $s->execute($args); return $s->fetchAll(PDO::FETCH_ASSOC); }

try {
    $pdo->beginTransaction();

    if ($type === 'appointment') {
        $ap = rows($pdo, 'SELECT * FROM appointments WHERE appointment_id = :a FOR UPDATE', ['a' => $id]);
        if (!$ap) { $pdo->rollBack(); fail('Appointment not found.', 404); }
        if (!in_array($ap[0]['status'], ['Completed', 'Cancelled'], true)) {
            $pdo->rollBack(); fail('Only Completed or Cancelled appointments can be deleted.', 409);
        }
        $pid  = (int) $ap[0]['patient_id'];
        $pt   = rows($pdo, 'SELECT first_name, last_name FROM patients WHERE patient_id = :p', ['p' => $pid]);
        $name = $pt ? $pt[0]['first_name'] . ' ' . $pt[0]['last_name'] : 'Patient ' . $pid;
        $nt   = rows($pdo, 'SELECT * FROM medical_notes WHERE appointment_id = :a', ['a' => $id]);
        $rx   = rows($pdo, 'SELECT * FROM prescriptions WHERE appointment_id = :a', ['a' => $id]);
        $bl   = rows($pdo, 'SELECT * FROM billing WHERE appointment_id = :a', ['a' => $id]);

        $pdo->prepare('INSERT INTO deleted_records (rec_type, ref_id, label, payload, deleted_by) VALUES (:t, :r, :l, :p, :b)')
            ->execute(['t' => 'appointment', 'r' => $pid, 'l' => $name,
                       'p' => json_encode(['appointments' => $ap, 'notes' => $nt, 'prescriptions' => $rx, 'billing' => $bl]), 'b' => $role]);
        $pdo->prepare('DELETE FROM billing WHERE appointment_id = :a')->execute(['a' => $id]);
        $pdo->prepare('DELETE FROM prescriptions WHERE appointment_id = :a')->execute(['a' => $id]);
        $pdo->prepare('DELETE FROM medical_notes WHERE appointment_id = :a')->execute(['a' => $id]);
        $pdo->prepare('DELETE FROM appointments  WHERE appointment_id = :a')->execute(['a' => $id]);
    } else {
        $pt = rows($pdo, 'SELECT * FROM patients WHERE patient_id = :p FOR UPDATE', ['p' => $id]);
        if (!$pt) { $pdo->rollBack(); fail('Patient not found.', 404); }
        $safe = $pt[0]; unset($safe['password_hash']);
        $name = $pt[0]['first_name'] . ' ' . $pt[0]['last_name'];
        $ap   = rows($pdo, 'SELECT * FROM appointments WHERE patient_id = :p', ['p' => $id]);
        $nt   = rows($pdo, 'SELECT n.* FROM medical_notes n JOIN appointments a ON a.appointment_id = n.appointment_id WHERE a.patient_id = :p', ['p' => $id]);
        $rx   = rows($pdo, 'SELECT r.* FROM prescriptions r JOIN appointments a ON a.appointment_id = r.appointment_id WHERE a.patient_id = :p', ['p' => $id]);
        $bl   = rows($pdo, 'SELECT b.* FROM billing b JOIN appointments a ON a.appointment_id = b.appointment_id WHERE a.patient_id = :p', ['p' => $id]);

        $pdo->prepare('INSERT INTO deleted_records (rec_type, ref_id, label, payload, deleted_by) VALUES (:t, :r, :l, :p, :b)')
            ->execute(['t' => 'patient', 'r' => $id, 'l' => $name,
            'p' => json_encode(['patient' => $safe, 'appointments' => $ap, 'notes' => $nt, 'prescriptions' => $rx, 'billing' => $bl]), 'b' => $role]);
        $pdo->prepare('DELETE FROM billing WHERE appointment_id IN (SELECT appointment_id FROM appointments WHERE patient_id = :p)')->execute(['p' => $id]);
        $pdo->prepare('DELETE FROM prescriptions WHERE appointment_id IN (SELECT appointment_id FROM appointments WHERE patient_id = :p)')->execute(['p' => $id]);
        $pdo->prepare('DELETE FROM medical_notes WHERE appointment_id IN (SELECT appointment_id FROM appointments WHERE patient_id = :p)')->execute(['p' => $id]);
        $pdo->prepare('DELETE FROM appointments WHERE patient_id = :p')->execute(['p' => $id]);
        $pdo->prepare('DELETE FROM patients WHERE patient_id = :p')->execute(['p' => $id]);
    }

    $pdo->commit();
    out(['ok' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('staff_delete.php: ' . $e->getMessage());
    fail('Server error while deleting.', 500);
}