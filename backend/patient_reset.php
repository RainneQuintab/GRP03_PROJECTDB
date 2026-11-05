<?php
// backend/patient_reset.php - forgot ID / password for patients (code by email or phone)
//   action=request : method=email|phone, contact=...                 -> sends a 6-digit code
//   action=reset   : method, contact, code, password                 -> checks the code, saves the new password
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once 'db.php';       // defines $pdo and $cfg
require_once 'notify.php';

const CODE_MINUTES = 10;     // how long a code works
const MAX_ATTEMPTS = 5;      // wrong guesses allowed per code
const MAX_REQUESTS = 3;      // codes per account per 15 minutes
$demo = !empty($cfg['demo_mode']);   // true = nothing is sent; the code is returned to the page (testing only)

function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { fail('POST only.', 405); }

$action = $_POST['action'] ?? '';
$method = $_POST['method'] ?? '';
$raw    = trim($_POST['contact'] ?? '');
if (!in_array($method, ['email', 'phone'], true)) { fail('Choose email or phone.'); }

if ($method === 'email') {
    if (!filter_var($raw, FILTER_VALIDATE_EMAIL)) { fail('That email address doesn’t look right.'); }
    $contact = strtolower($raw);
} else {
    $d = preg_replace('/\D/', '', $raw);
    if (strpos($d, '63') === 0 && strlen($d) > 10) { $d = substr($d, 2); }
    if (strpos($d, '0') === 0) { $d = substr($d, 1); }
    if (!preg_match('/^9\d{9}$/', $d)) { fail('Enter a valid mobile number, e.g. 9171234567.'); }
    $contact = $d;                         // 10 digits
}

function maskContact($method, $c) {
    if ($method === 'email') {
        [$u, $dom] = explode('@', $c, 2);
        return substr($u, 0, 1) . str_repeat('*', max(2, strlen($u) - 1)) . '@' . $dom;
    }
    return '+63 ' . substr($c, 0, 3) . ' *** ' . substr($c, -2);
}

// Returns the single matching patient, or null. A mobile number shared by several patients is treated as "no match"
// so a code is never sent for the wrong person; those patients use their email instead.
function findPatient(PDO $pdo, $method, $c) {
    if ($method === 'email') {
        $s = $pdo->prepare('SELECT patient_id, first_name, email FROM patients WHERE LOWER(email) = :c LIMIT 2');
    } else {
        $s = $pdo->prepare("SELECT patient_id, first_name, email FROM patients
                            WHERE RIGHT(REGEXP_REPLACE(COALESCE(contact_number, ''), '\D', '', 'g'), 10) = :c LIMIT 2");
    }
    $s->execute(['c' => $c]);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    return count($rows) === 1 ? $rows[0] : null;
}

try {
    if ($action === 'request') {
        $p = findPatient($pdo, $method, $contact);
        $res = ['ok' => true, 'masked' => maskContact($method, $contact)];   // same reply whether or not the account exists

        if ($p) {
            $recent = $pdo->prepare("SELECT COUNT(*) FROM patient_password_resets WHERE patient_id = :u AND created_at > NOW() - INTERVAL '15 minutes'");
            $recent->execute(['u' => $p['patient_id']]);
            if ((int) $recent->fetchColumn() >= MAX_REQUESTS) {
                fail('Too many requests. Please wait a few minutes and try again.', 429);
            }
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $pdo->prepare('UPDATE patient_password_resets SET used = TRUE WHERE patient_id = :u AND used = FALSE')->execute(['u' => $p['patient_id']]);
            $pdo->prepare("INSERT INTO patient_password_resets (patient_id, code_hash, method, expires_at)
                           VALUES (:u, :h, :m, NOW() + INTERVAL '" . (int) CODE_MINUTES . " minutes')")
                ->execute(['u' => $p['patient_id'], 'h' => password_hash($code, PASSWORD_DEFAULT), 'm' => $method]);

            if ($demo) { $res['demo_code'] = $code; }
            else {
                $msg = "Your HealthBridge password reset code is $code. It expires in " . CODE_MINUTES . " minutes. If you didn't ask for it, ignore this message.";
                $to = $method === 'email' ? $p['email'] : $contact;
                send_code($cfg, $method, $to, 'HealthBridge password reset code', $msg);
            }
        } elseif ($demo) {
            fail('No patient account uses that ' . ($method === 'email' ? 'email' : 'mobile number') . '.', 404);
        }
        out($res);
    }

    if ($action === 'reset') {
        $code = trim($_POST['code'] ?? '');
        $pw   = $_POST['password'] ?? '';
        if (!preg_match('/^\d{6}$/', $code)) { fail('Enter the 6-digit code.'); }
        if (strlen($pw) < 8) { fail('Your new password needs at least 8 characters.'); }

        $bad = 'That code is incorrect or has expired.';
        $p = findPatient($pdo, $method, $contact);
        if (!$p) { fail($bad); }

        $s = $pdo->prepare('SELECT reset_id, code_hash, attempts FROM patient_password_resets
                            WHERE patient_id = :u AND used = FALSE AND expires_at > NOW()
                            ORDER BY created_at DESC LIMIT 1');
        $s->execute(['u' => $p['patient_id']]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        if (!$r || $r['attempts'] >= MAX_ATTEMPTS) { fail($bad); }

        if (!password_verify($code, $r['code_hash'])) {
            $pdo->prepare('UPDATE patient_password_resets SET attempts = attempts + 1 WHERE reset_id = :r')->execute(['r' => $r['reset_id']]);
            usleep(400000);
            fail($bad);
        }

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE patients SET password_hash = :h WHERE patient_id = :u')
            ->execute(['h' => password_hash($pw, PASSWORD_DEFAULT), 'u' => $p['patient_id']]);
        $pdo->prepare('UPDATE patient_password_resets SET used = TRUE WHERE patient_id = :u')->execute(['u' => $p['patient_id']]);
        $pdo->commit();
        out(['ok' => true, 'patient_code' => 'HB-' . str_pad((string) $p['patient_id'], 5, '0', STR_PAD_LEFT), 'email' => $p['email']]);
    }

    fail('Unknown action.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('patient_reset.php: ' . $e->getMessage());
    fail('Server error. Check that database/update_patient_reset.sql was applied.', 500);
}
