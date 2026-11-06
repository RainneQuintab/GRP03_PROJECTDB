<?php
// backend/staff_appointments.php - appointment queue for staff, admin and doctors (PostgreSQL)
//   GET  ?status=Pending&date=2026-11-10    -> list (both filters optional). Doctors only ever see their own.
//   POST action=update appointment_id, [status], [doctor_id], [date "YYYY-MM-DD HH:MM"]
//        staff/admin: change status, assign a doctor, reschedule.  doctor: mark their own appointment Completed.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();
require_once 'db.php';

const STATUSES = ['Pending', 'Scheduled', 'Completed', 'Cancelled'];

function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }

if (empty($_SESSION['staff'])) { fail('Please sign in.', 401); }
$me   = $_SESSION['staff'];
$role = $me['role'];
$myDoctor = $me['doctor_id'] ?? null;
if ($role === 'doctor' && $myDoctor === null) { fail('This doctor login is not linked to a doctor record.', 403); }

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $where = []; $args = [];
        if ($role === 'doctor') { $where[] = 'a.doctor_id = :me'; $args['me'] = (int) $myDoctor; }
        $status = $_GET['status'] ?? '';
        if ($status !== '') {
            if (!in_array($status, STATUSES, true)) { fail('Unknown status.'); }
            $where[] = 'a.status = :st'; $args['st'] = $status;
        }
        $date = $_GET['date'] ?? '';
        if ($date !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $date);
            if (!$dt || $dt->format('Y-m-d') !== $date) { fail('Date must look like 2026-11-10.'); }
            $where[] = 'a.appointment_date >= CAST(:d0 AS date) AND a.appointment_date < CAST(:d1 AS date) + 1';
            $args['d0'] = $date; $args['d1'] = $date;
        }
            $sql = "SELECT a.appointment_id, a.appointment_date, a.status, a.reason,
                       p.patient_id, p.first_name AS patient_first, p.last_name AS patient_last, p.contact_number,
                       d.doctor_id, d.first_name AS doctor_first, d.last_name AS doctor_last, sp.specialty_name
                FROM appointments a
                JOIN patients p ON p.patient_id = a.patient_id
                JOIN doctors  d ON d.doctor_id  = a.doctor_id
                LEFT JOIN specialties sp ON sp.specialty_id = d.specialty_id"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY a.appointment_date';
        $s = $pdo->prepare($sql);
        $s->execute($args);
        out(['ok' => true, 'appointments' => $s->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'update') { fail('Unknown action.', 405); }

    $id = (int) ($_POST['appointment_id'] ?? 0);
    $cur = $pdo->prepare('SELECT doctor_id, status FROM appointments WHERE appointment_id = :a');
    $cur->execute(['a' => $id]);
    $row = $cur->fetch(PDO::FETCH_ASSOC);
    if (!$row) { fail('Appointment not found.', 404); }

    $sets = []; $args = ['a' => $id];

    if ($role === 'doctor') {
        if ((int) $row['doctor_id'] !== (int) $myDoctor) { fail('That appointment belongs to another doctor.', 403); }
        if (($_POST['status'] ?? '') !== 'Completed' || isset($_POST['doctor_id']) || isset($_POST['date'])) {
            fail('Doctors can only mark their own appointments Completed.', 403);
        }
        if ($row['status'] !== 'Scheduled') { fail('Only a Scheduled appointment can be completed.', 409); }
        $sets[] = "status = 'Completed'";
    } else {
        if (isset($_POST['status'])) {
            if (!in_array($_POST['status'], STATUSES, true)) { fail('Unknown status.'); }
            $sets[] = 'status = :st'; $args['st'] = $_POST['status'];
        }
        if (isset($_POST['doctor_id'])) {
            $chk = $pdo->prepare('SELECT 1 FROM doctors WHERE doctor_id = :d');
            $chk->execute(['d' => (int) $_POST['doctor_id']]);
            if (!$chk->fetchColumn()) { fail('Choose a doctor.'); }
            $sets[] = 'doctor_id = :doc'; $args['doc'] = (int) $_POST['doctor_id'];
        }
        if (isset($_POST['date'])) {
            $norm = str_replace('T', ' ', trim($_POST['date']));
            $when = DateTime::createFromFormat('Y-m-d H:i', $norm);
            if (!$when || $when->format('Y-m-d H:i') !== $norm) { fail('Pick a valid date and time.'); }
            $sets[] = 'appointment_date = :t'; $args['t'] = $when->format('Y-m-d H:i:00');
        }
        if (!$sets) { fail('Nothing to change.'); }
    }

    $pdo->prepare('UPDATE appointments SET ' . implode(', ', $sets) . ' WHERE appointment_id = :a')->execute($args);
    out(['ok' => true]);
} catch (PDOException $e) {
    if ($e->getCode() === '23505') { fail('That doctor already has an appointment at that time.', 409); }
    error_log('staff_appointments.php: ' . $e->getMessage());
    fail('Server error. Check that database/update_appointments.sql was applied.', 500);
}
