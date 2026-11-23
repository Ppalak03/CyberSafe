<?php
// Login rate limiting helpers (per email + IP, stored in login_attempts).
declare(strict_types=1);

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function login_is_limited(string $email): bool
{
    $window = (int)LOGIN_WINDOW; // constant cast to int, safe to inline
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE email = ? AND ip_address = ? AND attempted_at > (NOW() - INTERVAL $window SECOND)"
    );
    $stmt->execute([$email, client_ip()]);
    if ((int)$stmt->fetchColumn() >= LOGIN_MAX_FAILS) {
        return true;
    }
    // Also cap one IP trying many different emails (credential stuffing).
    $ip = db()->prepare(
        "SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > (NOW() - INTERVAL $window SECOND)"
    );
    $ip->execute([client_ip()]);
    return (int)$ip->fetchColumn() >= LOGIN_MAX_FAILS_PER_IP;
}

function record_failed_login(string $email): void
{
    db()->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)')
        ->execute([$email, client_ip()]);
    if (random_int(1, 20) === 1) { // housekeeping: drop rows older than a day
        db()->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }
}

function clear_failed_logins(string $email): void
{
    db()->prepare('DELETE FROM login_attempts WHERE email = ? AND ip_address = ?')
        ->execute([$email, client_ip()]);
}

function destroy_session_and_cookie(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
