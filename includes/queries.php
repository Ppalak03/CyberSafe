<?php
// Read-only data-access helpers for dashboards. Every query is prepared; nothing is hardcoded.
declare(strict_types=1);

function user_stats(int $userId): array
{
    $s = db()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(is_correct),0) AS correct FROM attempts WHERE user_id = ?');
    $s->execute([$userId]);
    $r = $s->fetch();
    $total = (int)$r['total'];
    $correct = (int)$r['correct'];
    return [
        'total'     => $total,
        'correct'   => $correct,
        'incorrect' => $total - $correct,
        'accuracy'  => $total > 0 ? (int)round($correct / $total * 100) : 0,
    ];
}

/** Active categories with scenario count and this user's attempts/accuracy in each. */
function category_performance(int $userId): array
{
    $s = db()->prepare(
        'SELECT c.id, c.name, c.icon, c.description,
                COUNT(DISTINCT s.id) AS scenario_count,
                COUNT(a.id) AS attempts,
                COALESCE(SUM(a.is_correct),0) AS correct
         FROM categories c
         LEFT JOIN scenarios s ON s.category_id = c.id AND s.is_active = 1
         LEFT JOIN attempts a ON a.scenario_id = s.id AND a.user_id = ?
         WHERE c.is_active = 1
         GROUP BY c.id, c.name, c.icon, c.description
         ORDER BY c.id'
    );
    $s->execute([$userId]);
    $rows = $s->fetchAll();
    foreach ($rows as &$r) {
        $r['attempts'] = (int)$r['attempts'];
        $r['correct']  = (int)$r['correct'];
        $r['scenario_count'] = (int)$r['scenario_count'];
        $r['accuracy'] = $r['attempts'] > 0 ? (int)round($r['correct'] / $r['attempts'] * 100) : null;
    }
    return $rows;
}

function recent_activity(int $userId, int $limit = 5): array
{
    $limit = max(1, min(20, $limit));
    $s = db()->prepare(
        "SELECT a.is_correct, a.xp_earned, a.attempted_at, s.title, c.name AS category
         FROM attempts a
         JOIN scenarios s ON s.id = a.scenario_id
         JOIN categories c ON c.id = s.category_id
         WHERE a.user_id = ?
         ORDER BY a.attempted_at DESC, a.id DESC
         LIMIT $limit"
    );
    $s->execute([$userId]);
    return $s->fetchAll();
}

function recent_badges(int $userId, int $limit = 3): array
{
    $limit = max(1, min(20, $limit));
    $s = db()->prepare(
        "SELECT b.name, b.icon, b.description, ub.earned_at
         FROM user_badges ub JOIN badges b ON b.id = ub.badge_id
         WHERE ub.user_id = ?
         ORDER BY ub.earned_at DESC, ub.id DESC
         LIMIT $limit"
    );
    $s->execute([$userId]);
    return $s->fetchAll();
}

/**
 * "Continue learning": next active scenario the user has not attempted, preferring
 * the category of their most recent attempt. Returns null when everything is done.
 */
function continue_learning(int $userId): ?array
{
    $sql = 'SELECT s.id, s.title, s.difficulty, c.name AS category
            FROM scenarios s JOIN categories c ON c.id = s.category_id
            WHERE s.is_active = 1 AND c.is_active = 1
              AND NOT EXISTS (SELECT 1 FROM attempts a WHERE a.user_id = :u AND a.scenario_id = s.id)
            ORDER BY (s.category_id = COALESCE(
                        (SELECT sc.category_id FROM attempts a2 JOIN scenarios sc ON sc.id = a2.scenario_id
                         WHERE a2.user_id = :u2 ORDER BY a2.attempted_at DESC, a2.id DESC LIMIT 1), 0)) DESC,
                     s.id ASC
            LIMIT 1';
    $s = db()->prepare($sql);
    $s->execute([':u' => $userId, ':u2' => $userId]);
    return $s->fetch() ?: null;
}

/** Up to 3 categories to practise: weakest attempted ones first, then unattempted ones. */
function recommended_categories(array $perf, int $limit = 3): array
{
    $usable = array_filter($perf, fn($c) => $c['scenario_count'] > 0);
    $weak = array_filter($usable, fn($c) => $c['attempts'] > 0 && $c['accuracy'] < 100);
    usort($weak, fn($a, $b) => $a['accuracy'] <=> $b['accuracy']);
    $fresh = array_values(array_filter($usable, fn($c) => $c['attempts'] === 0));
    return array_slice(array_merge($weak, $fresh), 0, $limit);
}

/** Daily results with a running (cumulative) accuracy. Returns the most recent $days active days. */
function accuracy_timeline(int $userId, int $days = 30): array
{
    $s = db()->prepare(
        'SELECT DATE(attempted_at) AS d, COUNT(*) AS n, COALESCE(SUM(is_correct),0) AS ok
         FROM attempts WHERE user_id = ? GROUP BY DATE(attempted_at) ORDER BY d'
    );
    $s->execute([$userId]);
    $total = 0; $correct = 0; $out = [];
    foreach ($s->fetchAll() as $r) {
        $total += (int)$r['n'];
        $correct += (int)$r['ok'];
        $out[] = [
            'date'       => $r['d'],
            'correct'    => (int)$r['ok'],
            'attempts'   => (int)$r['n'],
            'cumulative' => (int)round($correct / $total * 100),
        ];
    }
    return array_slice($out, -max(1, $days));
}

function attempt_count(int $userId): int
{
    $s = db()->prepare('SELECT COUNT(*) FROM attempts WHERE user_id = ?');
    $s->execute([$userId]);
    return (int)$s->fetchColumn();
}

function attempt_history(int $userId, int $limit, int $offset): array
{
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);
    $s = db()->prepare(
        "SELECT a.id, a.is_correct, a.score, a.xp_earned, a.attempted_at,
                s.title, c.name AS category, so.option_text AS selected, co.option_text AS correct_answer
         FROM attempts a
         JOIN scenarios s ON s.id = a.scenario_id
         JOIN categories c ON c.id = s.category_id
         JOIN options so ON so.id = a.selected_option_id
         LEFT JOIN options co ON co.scenario_id = a.scenario_id AND co.is_correct = 1
         WHERE a.user_id = ?
         ORDER BY a.attempted_at DESC, a.id DESC
         LIMIT $limit OFFSET $offset"
    );
    $s->execute([$userId]);
    return $s->fetchAll();
}

/**
 * Weak-area analysis from category_performance() rows.
 * Returns null (no attempts) or ['type' => weak|single|perfect, 'category' => row].
 */
function weak_area(array $perf): ?array
{
    $tried = array_values(array_filter($perf, fn($c) => $c['attempts'] > 0));
    if (!$tried) {
        return null;
    }
    usort($tried, fn($a, $b) => [$a['accuracy'], -$a['attempts']] <=> [$b['accuracy'], -$b['attempts']]);
    $low = $tried[0];
    if ($low['accuracy'] === 100) {
        return ['type' => 'perfect', 'category' => $low];
    }
    return ['type' => count($tried) === 1 ? 'single' : 'weak', 'category' => $low];
}
