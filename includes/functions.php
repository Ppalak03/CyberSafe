<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function pull_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        http_response_code(405);
        header('Allow: ' . $method);
        exit('Method not allowed.');
    }
}

/** Level info derived from total XP: current level, name, next threshold, progress %. */
function level_for_xp(int $xp): array
{
    $current = 1;
    foreach (LEVELS as $n => $lvl) {
        if ($xp >= $lvl['min_xp']) {
            $current = $n;
        }
    }
    $next    = LEVELS[$current + 1] ?? null;
    $floor   = LEVELS[$current]['min_xp'];
    $percent = $next ? (int)floor((($xp - $floor) / ($next['min_xp'] - $floor)) * 100) : 100;
    return [
        'level'     => $current,
        'name'      => LEVELS[$current]['name'],
        'next_xp'   => $next['min_xp'] ?? null,
        'next_name' => $next['name'] ?? null,
        'percent'   => $percent,
    ];
}

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

/** Password policy: 8+ chars, upper, lower, digit. Returns list of problems (empty = OK). */
function password_problems(string $pw): array
{
    $p = [];
    if (strlen($pw) < 8)             $p[] = 'Use at least 8 characters.';
    if (strlen($pw) > 72)            $p[] = 'Use at most 72 characters.';
    if (!preg_match('/[A-Z]/', $pw)) $p[] = 'Add an uppercase letter.';
    if (!preg_match('/[a-z]/', $pw)) $p[] = 'Add a lowercase letter.';
    if (!preg_match('/\d/', $pw))    $p[] = 'Add a number.';
    return $p;
}
