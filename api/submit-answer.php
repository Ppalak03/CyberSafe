<?php
// JSON endpoint used by challenges.js. Auth, CSRF and every rule are enforced server-side.
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/challenge_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
if (empty($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Please log in again.'], 401);
}
if (!csrf_validate()) {
    json_response(['ok' => false, 'error' => 'Your session expired. Refresh the page and try again.'], 419);
}

$u = db()->prepare('SELECT id FROM users WHERE id = ? AND is_active = 1');
$u->execute([$_SESSION['user_id']]);
if (!$u->fetch()) {
    json_response(['ok' => false, 'error' => 'Account not available.'], 403);
}

$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
}
$rt = isset($in['response_time']) && is_numeric($in['response_time']) ? (int)$in['response_time'] : null;

$res = submit_attempt(
    (int)$_SESSION['user_id'],
    (int)($in['scenario_id'] ?? 0),
    (int)($in['option_id'] ?? 0),
    (string)($in['attempt_token'] ?? ''),
    $rt
);
if (!$res['ok']) {
    json_response(['ok' => false, 'error' => $res['error']], $res['status']);
}
json_response(['ok' => true, 'redirect' => url('user/result.php?attempt=' . $res['attempt_id'])]);
