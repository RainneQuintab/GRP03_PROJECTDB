<?php
// backend/session.php - one place that starts the PHP session safely.
// Every endpoint that used to call session_start() now does: require_once __DIR__ . '/session.php';
//
// 1. The session cookie is HttpOnly (JavaScript cannot read it) and SameSite=Lax
//    (the browser will not attach it to cross-site POST requests, which blocks CSRF).
// 2. As a second layer, any POST whose Origin/Referer is another website is refused.
//    (Requests with neither header, such as curl or Postman, are allowed through.)

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),   // true automatically once you serve over HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $src = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    if ($src !== '') {
        $srcHost = parse_url($src, PHP_URL_HOST);
        $srcPort = parse_url($src, PHP_URL_PORT);
        $srcAuth = $srcHost . ($srcPort ? ':' . $srcPort : '');
        if (!hash_equals(strtolower($_SERVER['HTTP_HOST'] ?? ''), strtolower($srcAuth))) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'success' => false, 'error' => 'Request blocked (cross-site).']);
            exit;
        }
    }
}
