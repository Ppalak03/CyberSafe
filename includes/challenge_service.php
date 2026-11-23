<?php
// Challenge business logic. Correctness, score and XP are ALWAYS decided here, never by the browser.
declare(strict_types=1);

require_once __DIR__ . '/gamification.php';

function get_active_scenario(int $id): ?array
{
    $s = db()->prepare(
        'SELECT s.*, c.name AS category_name, c.icon AS category_icon
         FROM scenarios s JOIN categories c ON c.id = s.category_id
         WHERE s.id = ? AND s.is_active = 1 AND c.is_active = 1'
    );
    $s->execute([$id]);
    $row = $s->fetch();
    return $row ?: null;
}

/** Answer choices for display. Deliberately does NOT select is_correct. */
function scenario_options(int $scenarioId): array
{
    $s = db()->prepare(
        "SELECT id, option_text, classification FROM options
         WHERE scenario_id = ? ORDER BY FIELD(classification,'SAFE','SUSPICIOUS','SCAM')"
    );
    $s->execute([$scenarioId]);
    return $s->fetchAll();
}

/** Position of a scenario inside its category, e.g. [2, 10]. */
function scenario_position(int $scenarioId, int $categoryId): array
{
    $s = db()->prepare('SELECT id FROM scenarios WHERE category_id = ? AND is_active = 1 ORDER BY id');
    $s->execute([$categoryId]);
    $ids = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    $pos = array_search($scenarioId, $ids, true);
    return [$pos === false ? 1 : $pos + 1, count($ids)];
}

/** One-time token tied to this scenario page load; consumed on a successful submit. */
function issue_attempt_token(int $scenarioId): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['attempt_tokens'] ??= [];
    $_SESSION['attempt_tokens'][$token] = $scenarioId;
    if (count($_SESSION['attempt_tokens']) > 50) { // keep the session small
        $_SESSION['attempt_tokens'] = array_slice($_SESSION['attempt_tokens'], -50, null, true);
    }
    return $token;
}

function find_attempt_by_token(string $token): ?array
{
    $s = db()->prepare('SELECT id, user_id FROM attempts WHERE attempt_token = ?');
    $s->execute([$token]);
    return $s->fetch() ?: null;
}

/**
 * Validate and record one answer.
 * Returns ['ok'=>true,'attempt_id'=>int,'duplicate'=>bool] or ['ok'=>false,'error'=>string,'status'=>int].
 *
 * Rules: score = POINTS_CORRECT for any correct answer; XP = XP_CORRECT only the first time the user
 * answers THIS scenario correctly (replays are stored but earn no extra XP).
 */
function submit_attempt(int $userId, int $scenarioId, int $optionId, string $token, ?int $responseTime): array
{
    $fail = fn(string $msg, int $status = 400) => ['ok' => false, 'error' => $msg, 'status' => $status];

    if (!preg_match('/^[a-f0-9]{64}$/', $token) || $scenarioId < 1 || $optionId < 1) {
        return $fail('Invalid request.');
    }

    // Idempotency: a repeated request with a used token returns the original attempt.
    if ($existing = find_attempt_by_token($token)) {
        return (int)$existing['user_id'] === $userId
            ? ['ok' => true, 'attempt_id' => (int)$existing['id'], 'duplicate' => true]
            : $fail('Invalid request.', 403);
    }
    if (($_SESSION['attempt_tokens'][$token] ?? null) !== $scenarioId) {
        return $fail('This page has expired. Please reopen the scenario and try again.');
    }

    $scenario = get_active_scenario($scenarioId);
    if (!$scenario) {
        return $fail('This scenario is no longer active.');
    }

    // The option must belong to this scenario; correctness comes from the database.
    $o = db()->prepare('SELECT id, is_correct FROM options WHERE id = ? AND scenario_id = ?');
    $o->execute([$optionId, $scenarioId]);
    $option = $o->fetch();
    if (!$option) {
        return $fail('Invalid answer choice.');
    }
    $isCorrect = (int)$option['is_correct'] === 1;
    $responseTime = $responseTime !== null ? max(0, min(3600, $responseTime)) : null;

    $pdo = db();
    try {
        $pdo->beginTransaction();
        // Lock the user row so concurrent submissions are processed one at a time.
        $lock = $pdo->prepare('SELECT total_xp FROM users WHERE id = ? FOR UPDATE');
        $lock->execute([$userId]);
        $xpBefore = (int)$lock->fetchColumn();

        $prior = $pdo->prepare('SELECT COUNT(*) FROM attempts WHERE user_id = ? AND scenario_id = ? AND is_correct = 1');
        $prior->execute([$userId, $scenarioId]);
        $firstCorrect = $isCorrect && (int)$prior->fetchColumn() === 0;

        $score = $isCorrect ? POINTS_CORRECT : 0;
        $xp    = $firstCorrect ? XP_CORRECT : 0;

        $pdo->prepare(
            'INSERT INTO attempts (user_id, scenario_id, selected_option_id, is_correct, score, xp_earned, response_time, attempt_token)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $scenarioId, $optionId, $isCorrect ? 1 : 0, $score, $xp, $responseTime, $token]);
        $attemptId = (int)$pdo->lastInsertId();

        if ($xp > 0) {
            $pdo->prepare('UPDATE users SET total_xp = total_xp + ? WHERE id = ?')->execute([$xp, $userId]);
        }
        // Badges are checked in the same transaction, so an attempt, its XP and its badges succeed or fail together.
        $newBadges = award_badges($pdo, $userId);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000' && ($dup = find_attempt_by_token($token)) && (int)$dup['user_id'] === $userId) {
            return ['ok' => true, 'attempt_id' => (int)$dup['id'], 'duplicate' => true]; // lost a double-click race
        }
        error_log('[submit_attempt] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
        return $fail('Unable to save your progress. Please try again.', 500);
    }

    unset($_SESSION['attempt_tokens'][$token]);
    record_rewards($attemptId, $xpBefore, $xp, $newBadges);
    return ['ok' => true, 'attempt_id' => $attemptId, 'duplicate' => false];
}
