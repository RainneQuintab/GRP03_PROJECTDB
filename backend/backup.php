<?php
// backend/backup.php - list / restore / purge deleted records (admin only)
//   GET                      -> all backup records
//   POST action=restore id=  -> put the saved rows back into the live tables
//   POST action=purge   id=  -> delete the backup copy forever
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/session.php';
require_once 'db.php';

function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }

if (empty($_SESSION['staff'])) { fail('Please sign in.', 401); }
if ($_SESSION['staff']['role'] !== 'admin') { fail('Only an admin can use Backup.', 403); }

function insertRow($pdo, $table, $row) {
    $cols = []; $ph = []; $args = [];
    foreach ($row as $k => $v) {
        if (!preg_match('/^[a-z_]+$/', $k)) { continue; }
        $cols[] = $k; $ph[] = ':' . $k; $args[$k] = $v;
    }
    $pdo->prepare("INSERT INTO $table (" . implode(',', $cols) . ') VALUES (' . implode(',', $ph) . ')')->execute($args);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $s = $pdo->query('SELECT record_id, rec_type, ref_id, label, payload, deleted_by, deleted_at FROM deleted_records ORDER BY record_id DESC');
        $list = [];
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $r['payload'] = json_decode($r['payload'], true);
            $list[] = $r;
        }
        out(['ok' => true, 'records' => $list]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { fail('Unknown action.', 405); }
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0 || !in_array($action, ['restore', 'purge'], true)) { fail('Bad request.'); }

    if ($action === 'purge') {
        $pdo->prepare('DELETE FROM deleted_records WHERE record_id = :i')->execute(['i' => $id]);
        out(['ok' => true]);
    }

    $pdo->beginTransaction();
    $q = $pdo->prepare('SELECT * FROM deleted_records WHERE record_id = :i FOR UPDATE');
    $q->execute(['i' => $id]);
    $rec = $q->fetch(PDO::FETCH_ASSOC);
    if (!$rec) { $pdo->rollBack(); fail('That backup record no longer exists.', 404); }
    $p = json_decode($rec['payload'], true) ?: [];

    if ($rec['rec_type'] === 'patient') {
        insertRow($pdo, 'patients', $p['patient']);     // password is not backed up: the patient uses "forgot password"
    } else {
        $c = $pdo->prepare('SELECT 1 FROM patients WHERE patient_id = :p');
        $c->execute(['p' => (int) $rec['ref_id']]);
        if (!$c->fetchColumn()) { $pdo->rollBack(); fail('This patient was deleted too. Restore the patient first.', 409); }
    }
    foreach (($p['appointments'] ?? []) as $r)  { insertRow($pdo, 'appointments', $r); }
    foreach (($p['notes'] ?? []) as $r)         { insertRow($pdo, 'medical_notes', $r); }
    foreach (($p['prescriptions'] ?? []) as $r) { insertRow($pdo, 'prescriptions', $r); }
    foreach (($p['billing'] ?? []) as $r)       { insertRow($pdo, 'billing', $r); }

    $pdo->prepare('DELETE FROM deleted_records WHERE record_id = :i')->execute(['i' => $id]);
    $pdo->commit();
    out(['ok' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('backup.php: ' . $e->getMessage());
    if ($e->getCode() === '23505') { fail('Cannot restore: that record, or that doctor time slot, already exists.', 409); }
    fail('Server error in Backup.', 500);
}