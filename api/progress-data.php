<?php
// JSON data for the My Progress charts. Read-only, current user only.
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/queries.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
if (empty($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Please log in again.'], 401);
}
$uid = (int)$_SESSION['user_id'];

try {
    $stats = user_stats($uid);
    $timeline = accuracy_timeline($uid, 30);
    $perf = array_values(array_filter(category_performance($uid), fn($c) => $c['attempts'] > 0));
    json_response([
        'ok'       => true,
        'stats'    => $stats,
        'timeline' => [
            'labels'     => array_map(fn($t) => date('M j', strtotime($t['date'])), $timeline),
            'cumulative' => array_column($timeline, 'cumulative'),
            'correct'    => array_column($timeline, 'correct'),
        ],
        'outcome'  => ['correct' => $stats['correct'], 'incorrect' => $stats['incorrect']],
        'category' => [
            'labels'   => array_column($perf, 'name'),
            'accuracy' => array_column($perf, 'accuracy'),
        ],
    ]);
} catch (PDOException $e) {
    error_log('[progress-data] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
    json_response(['ok' => false, 'error' => 'Unable to load your data.'], 500);
}
