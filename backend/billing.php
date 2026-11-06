<?php
// backend/billing.php - bills and payments (PostgreSQL, table: billing)
//   GET                                   -> bills the signed-in account may see
//        staff/admin: all   doctor: bills of their own visits   patient: their own bills
//   POST action=save  appointment_id amount            staff/admin: create or change a bill
//        (only for a Completed appointment, and only while the bill is still Unpaid)
//   POST action=paid  appointment_id payment_method    staff/admin: record a payment (cash at the desk, etc.)
//   POST action=pay   appointment_id payment_method    patient: pay their own bill online (demo checkout)
// The amount always comes from the database, never from the browser, when a payment is recorded.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();
require_once 'db.php';

function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }

$STAFF_METHODS   = ['Cash', 'GCash', 'Maya', 'Credit / debit card'];
$PATIENT_METHODS = ['GCash', 'Maya', 'Credit / debit card'];

$staff = $_SESSION['staff'] ?? null;
$pid   = $_SESSION['patient_id'] ?? null;
if (!$staff && !$pid) { fail('Please sign in.', 401); }
$isDesk = $staff && in_array($staff['role'], ['staff', 'admin'], true);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $where = ''; $args = [];
        if ($staff) {
            if ($staff['role'] === 'doctor') { $where = 'WHERE a.doctor_id = :x'; $args['x'] = (int) ($staff['doctor_id'] ?? 0); }
        } else {
            $where = 'WHERE a.patient_id = :x'; $args['x'] = (int) $pid;
        }
        $s = $pdo->prepare("SELECT b.bill_id, b.appointment_id, b.amount, b.status, b.payment_method, b.billed_at, b.paid_at
                            FROM billing b JOIN appointments a ON a.appointment_id = b.appointment_id $where
                            ORDER BY b.bill_id");
        $s->execute($args);
        out(['ok' => true, 'bills' => $s->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { fail('Unknown action.', 405); }
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['appointment_id'] ?? 0);
    if ($id <= 0 || !in_array($action, ['save', 'paid', 'pay'], true)) { fail('Bad request.'); }

    // ---------- staff / admin: create or change a bill ----------
    if ($action === 'save') {
        if (!$isDesk) { fail('Only staff or admin can create bills.', 403); }
        $amount = trim($_POST['amount'] ?? '');
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount)) { fail('Enter a fee between 0 and 99,999,999.99.'); }

        $pdo->beginTransaction();
        $q = $pdo->prepare('SELECT status FROM appointments WHERE appointment_id = :a FOR UPDATE');
        $q->execute(['a' => $id]);
        $ap = $q->fetch(PDO::FETCH_ASSOC);
        if (!$ap) { $pdo->rollBack(); fail('Appointment not found.', 404); }
        if ($ap['status'] !== 'Completed') { $pdo->rollBack(); fail('Only a Completed visit can be billed.', 409); }

        $u = $pdo->prepare("INSERT INTO billing (appointment_id, amount) VALUES (:a, :m)
                            ON CONFLICT (appointment_id) DO UPDATE SET amount = EXCLUDED.amount
                            WHERE billing.status = 'Unpaid'
                            RETURNING bill_id");
        $u->execute(['a' => $id, 'm' => $amount]);
        if (!$u->fetchColumn()) { $pdo->rollBack(); fail('This bill is already paid and cannot be changed.', 409); }
        $pdo->commit();
        out(['ok' => true]);
    }

    // ---------- record a payment (staff at the desk, or the patient online) ----------
    $method = trim($_POST['payment_method'] ?? '');
    if ($action === 'paid') {
        if (!$isDesk) { fail('Only staff or admin can record a payment.', 403); }
        $allowed = $STAFF_METHODS;
    } else {
        if (!$pid) { fail('Only a signed-in patient can pay online.', 403); }
        $allowed = $PATIENT_METHODS;
    }
    if (!in_array($method, $allowed, true)) { fail('Choose a payment method.'); }

    $sql = "UPDATE billing b SET status = 'Paid', payment_method = :m, paid_at = CURRENT_TIMESTAMP
            FROM appointments a
            WHERE b.appointment_id = :a AND a.appointment_id = b.appointment_id AND b.status = 'Unpaid'";
    $args = ['m' => $method, 'a' => $id];
    if ($action === 'pay') { $sql .= ' AND a.patient_id = :p'; $args['p'] = (int) $pid; }
    $u = $pdo->prepare($sql);
    $u->execute($args);
    if ($u->rowCount() !== 1) { fail('There is no unpaid bill for that visit.', 409); }
    out(['ok' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('billing.php: ' . $e->getMessage());
    fail('Server error in Billing.', 500);
}
