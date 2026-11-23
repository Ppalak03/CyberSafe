<?php
// Non-JavaScript fallback for the answer form. Uses the same service as the API.
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/challenge_service.php';

require_method('POST');
csrf_require();

$scenarioId = (int)($_POST['scenario_id'] ?? 0);
$res = submit_attempt((int)$currentUser['id'], $scenarioId, (int)($_POST['option_id'] ?? 0), (string)($_POST['attempt_token'] ?? ''), null);

if (!$res['ok']) {
    flash('error', $res['error']);
    redirect($scenarioId > 0 ? 'user/challenge.php?id=' . $scenarioId : 'user/challenges.php');
}
redirect('user/result.php?attempt=' . $res['attempt_id']);
