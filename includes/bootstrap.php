<?php
// Include this first on every page: config, secure session, DB, helpers, CSRF.
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// ---- Error handling: log everything, show nothing technical to visitors ----
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/app.log');
set_exception_handler(function (Throwable $e): void {
    error_log('[uncaught] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (str_contains((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/api/')) {
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Something went wrong.']);
    } else {
        echo APP_DEBUG ? htmlspecialchars((string)$e, ENT_QUOTES) : 'Something went wrong. Please try again later.';
    }
});

// ---- Security headers ----
$cspNonce = base64_encode(random_bytes(16)); // used by the one inline script in footer.php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store'); // pages are personal: do not show them again via the back button after logout
if (!empty($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=15552000');
}
if (CSP_ENABLED) {
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'nonce-$cspNonce' https://cdn.jsdelivr.net; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
        "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
        "img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
    );
}

// ---- Sessions ----
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

// Inactivity timeout
if (isset($_SESSION['user_id'])) {
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('warning', 'Your session expired. Please log in again.');
    } else {
        $_SESSION['last_activity'] = time();
    }
}
