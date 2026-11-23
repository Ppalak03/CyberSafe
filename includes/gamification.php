<?php
// Badge engine. All eligibility maths runs server-side, inside the submit transaction.
declare(strict_types=1);

/**
 * Progress numbers used both to award badges and to draw progress bars.
 * "Solved" counts DISTINCT scenarios answered correctly, so replaying one scenario cannot farm badges.
 */
function user_metrics(PDO $pdo, int $uid): array
{
    $q = $pdo->prepare(
        'SELECT COUNT(*) AS n, COALESCE(SUM(is_correct),0) AS ok,
                COUNT(DISTINCT CASE WHEN is_correct = 1 THEN scenario_id END) AS solved
         FROM attempts WHERE user_id = ?'
    );
    $q->execute([$uid]);
    $a = $q->fetch();

    $p = $pdo->prepare(
        'SELECT COUNT(DISTINCT a.scenario_id) FROM attempts a
         JOIN scenarios s ON s.id = a.scenario_id JOIN categories c ON c.id = s.category_id
         WHERE a.user_id = ? AND a.is_correct = 1 AND c.name = ?'
    );
    $p->execute([$uid, BADGE_PHISHING_CATEGORY]);

    $s = $pdo->prepare(
        "SELECT COUNT(DISTINCT a.scenario_id) FROM attempts a
         JOIN options o ON o.id = a.selected_option_id
         WHERE a.user_id = ? AND a.is_correct = 1 AND o.classification = 'SCAM'"
    );
    $s->execute([$uid]);

    $x = $pdo->prepare('SELECT total_xp FROM users WHERE id = ?');
    $x->execute([$uid]);

    $n = (int)$a['n'];
    $ok = (int)$a['ok'];
    return [
        'attempts' => $n,
        'correct'  => $ok,
        'solved'   => (int)$a['solved'],
        'phishing' => (int)$p->fetchColumn(),
        'scam'     => (int)$s->fetchColumn(),
        'accuracy' => $n > 0 ? (int)round($ok / $n * 100) : 0,
        'xp'       => (int)$x->fetchColumn(),
    ];
}

function badge_met(array $badge, array $m): bool
{
    $v = (int)$badge['requirement_value'];
    return match ($badge['requirement_type']) {
        'first_attempt'    => $m['attempts'] >= $v,
        'correct_total'    => $m['solved'] >= $v,
        'phishing_correct' => $m['phishing'] >= $v,
        'scam_correct'     => $m['scam'] >= $v,
        'accuracy'         => $m['attempts'] >= BADGE_MIN_ATTEMPTS && $m['correct'] * 100 >= $v * $m['attempts'],
        'xp_total'         => $m['xp'] >= $v,
        default            => false,
    };
}

/** Progress for locked-badge cards: percent (0-100) and a readable label. */
function badge_progress(array $badge, array $m): array
{
    $v = max(1, (int)$badge['requirement_value']);
    switch ($badge['requirement_type']) {
        case 'first_attempt':    $cur = $m['attempts']; $unit = 'scenarios'; break;
        case 'correct_total':    $cur = $m['solved'];   $unit = 'correct'; break;
        case 'phishing_correct': $cur = $m['phishing']; $unit = 'phishing scenarios'; break;
        case 'scam_correct':     $cur = $m['scam'];     $unit = 'scam scenarios'; break;
        case 'xp_total':         $cur = $m['xp'];       $unit = 'XP'; break;
        case 'accuracy':
            $a = min(1, $m['attempts'] / BADGE_MIN_ATTEMPTS);
            $b = min(1, $m['accuracy'] / $v);
            return [
                'percent' => (int)floor(($a + $b) / 2 * 100),
                'label'   => min($m['attempts'], BADGE_MIN_ATTEMPTS) . '/' . BADGE_MIN_ATTEMPTS . ' attempts · ' . $m['accuracy'] . '% accuracy (need ' . $v . '%)',
            ];
        default: return ['percent' => 0, 'label' => ''];
    }
    $cur = min($cur, $v);
    return ['percent' => (int)floor($cur / $v * 100), 'label' => $cur . ' / ' . $v . ' ' . $unit];
}

/**
 * Awards every badge the user now qualifies for. MUST run inside a transaction that already holds the
 * user's row lock. Returns the newly awarded badges. A badge's XP reward is added to total_xp, which
 * can unlock an XP-based badge, so we re-check (bounded) while rewards keep changing XP.
 * user_badges has UNIQUE(user_id, badge_id); INSERT IGNORE makes duplicates impossible.
 */
function award_badges(PDO $pdo, int $uid): array
{
    $awarded = [];
    for ($round = 0; $round < 5; $round++) {
        $m = user_metrics($pdo, $uid);
        $st = $pdo->prepare(
            'SELECT b.* FROM badges b
             WHERE NOT EXISTS (SELECT 1 FROM user_badges ub WHERE ub.user_id = ? AND ub.badge_id = b.id)
             ORDER BY b.id'
        );
        $st->execute([$uid]);
        $xpChanged = false;
        foreach ($st->fetchAll() as $b) {
            if (!badge_met($b, $m)) {
                continue;
            }
            $ins = $pdo->prepare('INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)');
            $ins->execute([$uid, $b['id']]);
            if ($ins->rowCount() !== 1) {
                continue; // already owned (concurrent request)
            }
            $reward = (int)$b['xp_reward'];
            if ($reward > 0) {
                $pdo->prepare('UPDATE users SET total_xp = total_xp + ? WHERE id = ?')->execute([$reward, $uid]);
                $xpChanged = true;
            }
            $awarded[] = ['id' => (int)$b['id'], 'name' => $b['name'], 'icon' => $b['icon'],
                          'description' => $b['description'], 'xp_reward' => $reward];
        }
        if (!$xpChanged) {
            break;
        }
    }
    return $awarded;
}

/**
 * After a committed attempt: remember rewards for the result page (session) and queue one-time toasts.
 */
function record_rewards(int $attemptId, int $xpBefore, int $xpGained, array $badges): void
{
    $bonus = array_sum(array_column($badges, 'xp_reward'));
    $before = level_for_xp($xpBefore);
    $after  = level_for_xp($xpBefore + $xpGained + $bonus);
    $leveled = $after['level'] > $before['level'];

    $_SESSION['attempt_rewards'][$attemptId] = [
        'badges'     => $badges,
        'bonus_xp'   => $bonus,
        'level_up'   => $leveled,
        'level'      => $after['level'],
        'level_name' => $after['name'],
    ];
    if (count($_SESSION['attempt_rewards']) > 20) {
        $_SESSION['attempt_rewards'] = array_slice($_SESSION['attempt_rewards'], -20, null, true);
    }
    if ($badges) {
        flash('success', 'New badge unlocked: ' . implode(', ', array_column($badges, 'name')));
    }
    if ($leveled) {
        flash('info', 'Level up! You are now Level ' . $after['level'] . ': ' . $after['name']);
    }
}
