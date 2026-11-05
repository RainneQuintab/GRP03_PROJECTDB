<?php
// backend/notify.php - sends a short message by email or SMS using the settings in config.php.
// Email: SMTP through PHPMailer if installed (composer require phpmailer/phpmailer), otherwise PHP mail().
// SMS:   Semaphore (semaphore.co, Philippines) if 'sms' => ['apikey' => ...] is set.
// Returns true if the message was handed to the provider, false otherwise (details go to the server log).

function send_code(array $cfg, string $method, string $to, string $subject, string $text): bool {
    return $method === 'email' ? send_email($cfg, $to, $subject, $text) : send_sms($cfg, $to, $text);
}

function send_email(array $cfg, string $to, string $subject, string $text): bool {
    $smtp = $cfg['smtp'] ?? [];
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!empty($smtp['host']) && is_file($autoload)) {
        require_once $autoload;
        try {
            $m = new PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP();
            $m->Host       = $smtp['host'];
            $m->Port       = (int) ($smtp['port'] ?? 587);
            $m->SMTPAuth   = true;
            $m->Username   = $smtp['user'] ?? '';
            $m->Password   = $smtp['pass'] ?? '';
            $m->SMTPSecure = ($smtp['secure'] ?? 'tls') === 'ssl' ? 'ssl' : 'tls';
            $m->setFrom($smtp['from'] ?? ($smtp['user'] ?? 'no-reply@healthbridge.com'), 'HealthBridge');
            $m->addAddress($to);
            $m->Subject = $subject;
            $m->Body    = $text;
            $m->send();
            return true;
        } catch (Throwable $e) {
            error_log('notify.php (smtp): ' . $e->getMessage());
            return false;
        }
    }
    $from = $smtp['from'] ?? 'no-reply@healthbridge.com';
    $ok = @mail($to, $subject, $text, 'From: ' . $from);
    if (!$ok) { error_log('notify.php: mail() failed. Configure SMTP in config.php (see config.sample.php).'); }
    return $ok;
}

function send_sms(array $cfg, string $local10, string $text): bool {
    $sms = $cfg['sms'] ?? [];
    if (empty($sms['apikey'])) {
        error_log('notify.php: SMS is not configured (sms.apikey in config.php).');
        return false;
    }
    $body = ['apikey' => $sms['apikey'], 'number' => '0' . $local10, 'message' => $text];
    if (!empty($sms['sender'])) { $body['sendername'] = $sms['sender']; }
    $ch = curl_init('https://api.semaphore.co/api/v4/messages');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($body),
                            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false || $code >= 300) { error_log("notify.php (sms): HTTP $code " . substr((string) $res, 0, 200)); return false; }
    return true;
}
