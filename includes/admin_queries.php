<?php
// Read helpers for the admin panel. All queries are prepared; dynamic fragments come from fixed whitelists.
declare(strict_types=1);

function scalar(string $sql, array $params = []): int
{
    $s = db()->prepare($sql);
    $s->execute($params);
    return (int)$s->fetchColumn();
}

function admin_overview(): array
{
    return [
        'users'         => scalar("SELECT COUNT(*) FROM users WHERE role = 'user'"),
        'active_users'  => scalar("SELECT COUNT(*) FROM users WHERE role = 'user' AND is_active = 1"),
        'scenarios'     => scalar('SELECT COUNT(*) FROM scenarios'),
        'active_scen'   => scalar('SELECT COUNT(*) FROM scenarios WHERE is_active = 1'),
        'attempts'      => scalar('SELECT COUNT(*) FROM attempts'),
        'attempts_7d'   => scalar('SELECT COUNT(*) FROM attempts WHERE attempted_at >= (NOW() - INTERVAL 7 DAY)'),
        'learners_7d'   => scalar('SELECT COUNT(DISTINCT user_id) FROM attempts WHERE attempted_at >= (NOW() - INTERVAL 7 DAY)'),
        'accuracy'      => scalar('SELECT COALESCE(ROUND(AVG(is_correct) * 100), 0) FROM attempts'),
    ];
}

function popular_categories(int $limit = 5): array
{
    $limit = max(1, min(20, $limit));
    return db()->query(
        "SELECT c.name, COUNT(a.id) AS attempts
         FROM categories c LEFT JOIN scenarios s ON s.category_id = c.id LEFT JOIN attempts a ON a.scenario_id = s.id
         GROUP BY c.id, c.name ORDER BY attempts DESC, c.name LIMIT $limit"
    )->fetchAll();
}

function recent_registrations(int $limit = 5): array
{
    $limit = max(1, min(20, $limit));
    return db()->query("SELECT id, name, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC, id DESC LIMIT $limit")->fetchAll();
}

function recent_platform_activity(int $limit = 8): array
{
    $limit = max(1, min(30, $limit));
    return db()->query(
        "SELECT a.is_correct, a.attempted_at, u.name AS user_name, s.title
         FROM attempts a JOIN users u ON u.id = a.user_id JOIN scenarios s ON s.id = a.scenario_id
         ORDER BY a.attempted_at DESC, a.id DESC LIMIT $limit"
    )->fetchAll();
}

/** Count per day for the last $days days (missing days filled with 0). $source is a fixed whitelist key. */
function daily_series(string $source, int $days = 30): array
{
    $map = ['users' => ['users', 'created_at'], 'attempts' => ['attempts', 'attempted_at']];
    if (!isset($map[$source])) {
        return ['labels' => [], 'values' => []];
    }
    [$table, $col] = $map[$source];
    $days = max(1, min(90, $days));
    $rows = db()->query(
        "SELECT DATE($col) AS d, COUNT(*) AS n FROM $table
         WHERE $col >= (CURDATE() - INTERVAL " . ($days - 1) . " DAY) GROUP BY DATE($col)"
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    $labels = []; $values = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $labels[] = date('M j', strtotime($d));
        $values[] = (int)($rows[$d] ?? 0);
    }
    return ['labels' => $labels, 'values' => $values];
}

function scenario_popularity(int $limit = 10): array
{
    $limit = max(1, min(30, $limit));
    return db()->query(
        "SELECT s.title, COUNT(a.id) AS attempts
         FROM scenarios s LEFT JOIN attempts a ON a.scenario_id = s.id
         GROUP BY s.id, s.title ORDER BY attempts DESC, s.title LIMIT $limit"
    )->fetchAll();
}

function category_accuracy_platform(): array
{
    return db()->query(
        'SELECT c.name, COUNT(a.id) AS attempts, ROUND(AVG(a.is_correct) * 100) AS accuracy
         FROM categories c JOIN scenarios s ON s.category_id = c.id JOIN attempts a ON a.scenario_id = s.id
         GROUP BY c.id, c.name ORDER BY c.id'
    )->fetchAll();
}

function difficulty_accuracy_platform(): array
{
    return db()->query(
        "SELECT s.difficulty, COUNT(a.id) AS attempts, ROUND(AVG(a.is_correct) * 100) AS accuracy
         FROM scenarios s JOIN attempts a ON a.scenario_id = s.id
         GROUP BY s.difficulty ORDER BY FIELD(s.difficulty,'Easy','Medium','Hard')"
    )->fetchAll();
}

/** Escape LIKE wildcards so a search for "100%" matches literally. */
function like_term(string $q): string
{
    return '%' . addcslashes($q, '%_\\') . '%';
}

/** Keep only whitelisted keys from a query-string array (used for safe redirects back to a filtered list). */
function allowed_query(array $src, array $keys): array
{
    return array_filter(array_intersect_key($src, array_flip($keys)), fn($v) => is_scalar($v) && $v !== '');
}

function admin_users(string $q, string $status, int $limit, int $offset): array
{
    $where = ['1=1']; $params = [];
    if ($q !== '') {
        $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
        $params[] = like_term($q); $params[] = like_term($q);
    }
    if ($status === 'active')   { $where[] = 'u.is_active = 1'; }
    if ($status === 'inactive') { $where[] = 'u.is_active = 0'; }
    $w = implode(' AND ', $where);

    $total = scalar("SELECT COUNT(*) FROM users u WHERE $w", $params);
    $limit = max(1, min(100, $limit)); $offset = max(0, $offset);
    $s = db()->prepare(
        "SELECT u.id, u.name, u.email, u.role, u.is_active, u.total_xp, u.created_at,
                (SELECT COUNT(*) FROM attempts a WHERE a.user_id = u.id) AS attempts
         FROM users u WHERE $w ORDER BY u.created_at DESC, u.id DESC LIMIT $limit OFFSET $offset"
    );
    $s->execute($params);
    return [$s->fetchAll(), $total];
}

/** Prev/next links that keep the current filters. */
function admin_pager(int $page, int $pages): void
{
    if ($pages <= 1) {
        return;
    }
    $link = fn(int $p) => e('?' . http_build_query(array_merge($_GET, ['page' => $p])));
    echo '<nav class="pager" aria-label="Pages">';
    if ($page > 1) echo '<a class="btn btn-ghost btn-sm" href="' . $link($page - 1) . '">← Previous</a>';
    echo '<span class="muted">Page ' . $page . ' of ' . $pages . '</span>';
    if ($page < $pages) echo '<a class="btn btn-ghost btn-sm" href="' . $link($page + 1) . '">Next →</a>';
    echo '</nav>';
}
