<?php
// backend/appointments.php - a signed-in patient's own appointments (PostgreSQL)
//   GET  ?action=mine                                       -> my appointments, newest first
//   POST action=book    doctor_id, date ("YYYY-MM-DD HH:MM") -> request an appointment (status Pending)
//   POST action=cancel  appointment_id                       -> cancel one of mine (Pending/Scheduled only)
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();
require_once 'db.php';

function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }

if (empty($_SESSION['patient_id'])) { fail('Please sign in.', 401); }
$pid    = (int) $_SESSION['patient_id'];
$verb   = $_SERVER['REQUEST_METHOD'];
$action = $verb === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? 'mine');

try {
    if ($verb === 'GET' && $action === 'mine') {
        $s = $pdo->prepare('SELECT a.appointment_id, a.appointment_date, a.status,
                                   d.doctor_id, d.first_name AS doctor_first, d.last_name AS doctor_last, sp.specialty_name
                            FROM appointments a
                            JOIN doctors d ON d.doctor_id = a.doctor_id
                            LEFT JOIN specialties sp ON sp.specialty_id = d.specialty_id
                            WHERE a.patient_id = :p
                            ORDER BY a.appointment_date DESC');
        $s->execute(['p' => $pid]);
        out(['ok' => true, 'appointments' => $s->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($verb !== 'POST') { fail('Unknown action.', 405); }

    if ($action === 'book') {
        $doctorId = (int) ($_POST['doctor_id'] ?? 0);
        $norm     = str_replace('T', ' ', trim($_POST['date'] ?? ''));
        $tz       = new DateTimeZone('Asia/Manila');
        $when     = DateTime::createFromFormat('Y-m-d H:i', $norm, $tz);
        if (!$when || $when->format('Y-m-d H:i') !== $norm) { fail('Pick a valid date and time.'); }
        $now = new DateTime('now', $tz);
        if ($when <= $now)                                  { fail('Pick a date and time in the future.'); }
        if ($when > (clone $now)->modify('+1 year'))        { fail('Appointments can be booked up to one year ahead.'); }

        $d = $pdo->prepare('SELECT 1 FROM doctors WHERE doctor_id = :d');
        $d->execute(['d' => $doctorId]);
        if (!$d->fetchColumn()) { fail('Choose a doctor.'); }

        // appointments_doctor_slot (unique index) stops two bookings for the same doctor and time
        $ins = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status)
                              VALUES (:p, :d, :t, 'Pending') RETURNING appointment_id");
        $ins->execute(['p' => $pid, 'd' => $doctorId, 't' => $when->format('Y-m-d H:i:00')]);
        out(['ok' => true, 'appointment_id' => (int) $ins->fetchColumn(), 'status' => 'Pending'], 201);
    }

    if ($action === 'cancel') {
        $u = $pdo->prepare("UPDATE appointments SET status = 'Cancelled'
                            WHERE appointment_id = :a AND patient_id = :p AND status IN ('Pending', 'Scheduled')");
        $u->execute(['a' => (int) ($_POST['appointment_id'] ?? 0), 'p' => $pid]);
        if ($u->rowCount() === 0) { fail('That appointment can’t be cancelled.', 404); }
        out(['ok' => true]);
    }

    fail('Unknown action.');
} catch (PDOException $e) {
    if ($e->getCode() === '23505') { fail('That time is already taken for this doctor. Please choose another time.', 409); }
    error_log('appointments.php: ' . $e->getMessage());
    fail('Server error. Check that database/update_appointments.sql was applied.', 500);
}
