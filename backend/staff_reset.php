<?php
// backend/staff_reset.php - forgot password for staff/admin (code by email or phone)
//   action=request : method=email|phone, contact=...            -> sends a 6-digit code
//   action=reset   : method, contact, code, password            -> checks the code, saves the new password
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once 'db.php';       // defines $pdo and $cfg
require_once 'notify.php';

// ===== Settings =====
define('DEMO_MODE', !empty($cfg['demo_mode']));   // true = no email/SMS is sent; the code is returned to the page. Set in config.php; keep false for real use.
const CODE_MINUTES   = 10;     // how long a code works
const MAX_ATTEMPTS   = 5;      // wrong guesses allowed per code
const MAX_REQUESTS   = 3;      // codes per account per 15 minutes

function out($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { fail('POST only.', 405); }

$action = $_POST['action'] ?? '';
$method = $_POST['method'] ?? '';
$raw    = trim($_POST['contact'] ?? '');
if (!in_array($method, ['email', 'phone'], true)) { fail('Choose email or phone.'); }

// ---- normalise the contact ----
if ($method === 'email') {
    if (!filter_var($raw, FILTER_VALIDATE_EMAIL)) { fail('That email address doesn’t look right.'); }
    $contact = strtolower($raw);
} else {
    $d = preg_replace('/\D/', '', $raw);
    if (strpos($d, '63') === 0) { $d = substr($d, 2); }
    if (strpos($d, '0') === 0)  { $d = substr($d, 1); }
    if (!preg_match('/^9\d{9}$/', $d)) { fail('Enter a valid mobile number, e.g. 9171234567.'); }
    $contact = $d;                         // 10 digits, e.g. 9171234567
}

function maskContact($method, $c) {
    if ($method === 'email') {
        [$u, $dom] = explode('@', $c, 2);
        return substr($u, 0, 1) . str_repeat('*', max(2, strlen($u) - 1)) . '@' . $dom;
    }
    return '+63 ' . substr($c, 0, 3) . ' *** ' . substr($c, -2);
}

function findUser(PDO $pdo, $method, $c) {
    if ($method === 'email') {
        $s = $pdo->prepare('SELECT user_id, first_name, email, phone FROM staff_users WHERE LOWER(email) = :c AND is_active = TRUE LIMIT 1');
    } else {
        $s = $pdo->prepare("SELECT user_id, first_name, email, phone FROM staff_users
                            WHERE RIGHT(REGEXP_REPLACE(COALESCE(phone, ''), '\D', '', 'g'), 10) = :c AND is_active = TRUE LIMIT 1");
    }
    $s->execute(['c' => $c]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

function deliver(array $cfg, $method, $user, $contact, $code) {
    $msg = "Your HealthBridge password reset code is $code. It expires in " . CODE_MINUTES . " minutes. If you didn't ask for it, ignore this message.";
    return send_code($cfg, $method, $method === 'email' ? $user['email'] : $contact, 'HealthBridge password reset code', $msg);
}

try {
    if ($action === 'request') {
        $user = findUser($pdo, $method, $contact);
        $masked = maskContact($method, $contact);
        $res = ['ok' => true, 'masked' => $masked];   // same reply whether or not the account exists

        if ($user) {
            $recent = $pdo->prepare("SELECT COUNT(*) FROM staff_password_resets WHERE user_id = :u AND created_at > NOW() - INTERVAL '15 minutes'");
            $recent->execute(['u' => $user['user_id']]);
            if ((int) $recent->fetchColumn() >= MAX_REQUESTS) {
                fail('Too many requests. Please wait a few minutes and try again.', 429);
            }
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $pdo->prepare('UPDATE staff_password_resets SET used = TRUE WHERE user_id = :u AND used = FALSE')->execute(['u' => $user['user_id']]);
            $pdo->prepare("INSERT INTO staff_password_resets (user_id, code_hash, method, expires_at)
                           VALUES (:u, :h, :m, NOW() + INTERVAL '" . (int) CODE_MINUTES . " minutes')")
                ->execute(['u' => $user['user_id'], 'h' => password_hash($code, PASSWORD_DEFAULT), 'm' => $method]);

            if (DEMO_MODE) { $res['demo_code'] = $code; }
            else { deliver($cfg, $method, $user, $contact, $code); }
        } elseif (DEMO_MODE) {
            // be honest in demo mode so a wrong email/number is obvious while testing
            fail('No staff account uses that ' . ($method === 'email' ? 'email' : 'mobile number') . '.', 404);
        }
        out($res);
    }

    if ($action === 'reset') {
        $code = trim($_POST['code'] ?? '');
        $pw   = $_POST['password'] ?? '';
        if (!preg_match('/^\d{6}$/', $code)) { fail('Enter the 6-digit code.'); }
        if (strlen($pw) < 8) { fail('Your new password needs at least 8 characters.'); }

        $bad = 'That code is incorrect or has expired.';
        $user = findUser($pdo, $method, $contact);
        if (!$user) { fail($bad); }

        $s = $pdo->prepare('SELECT reset_id, code_hash, attempts FROM staff_password_resets
                            WHERE user_id = :u AND used = FALSE AND expires_at > NOW()
                            ORDER BY created_at DESC LIMIT 1');
        $s->execute(['u' => $user['user_id']]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        if (!$r || $r['attempts'] >= MAX_ATTEMPTS) { fail($bad); }

        if (!password_verify($code, $r['code_hash'])) {
            $pdo->prepare('UPDATE staff_password_resets SET attempts = attempts + 1 WHERE reset_id = :r')->execute(['r' => $r['reset_id']]);
            usleep(400000);
            fail($bad);
        }

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE staff_users SET password_hash = :h WHERE user_id = :u')
            ->execute(['h' => password_hash($pw, PASSWORD_DEFAULT), 'u' => $user['user_id']]);
        $pdo->prepare('UPDATE staff_password_resets SET used = TRUE WHERE user_id = :u')->execute(['u' => $user['user_id']]);
        $pdo->commit();
        out(['ok' => true]);
    }

    fail('Unknown action.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('staff_reset.php: ' . $e->getMessage());
    fail('Server error. Check that database/schema.sql and roles.sql were applied.', 500);
}
