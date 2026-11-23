<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_init.php';

$o = admin_overview();
$reg = daily_series('users', 30);
$att = daily_series('attempts', 30);
$pop = scenario_popularity(10);
$catAcc = category_accuracy_platform();
$diffAcc = difficulty_accuracy_platform();
$data = [
    'registrations' => $reg,
    'attempts'      => $att,
    'popularity'    => ['labels' => array_column($pop, 'title'), 'values' => array_map('intval', array_column($pop, 'attempts'))],
    'category'      => ['labels' => array_column($catAcc, 'name'), 'values' => array_map('intval', array_column($catAcc, 'accuracy'))],
    'difficulty'    => ['labels' => array_column($diffAcc, 'difficulty'), 'values' => array_map('intval', array_column($diffAcc, 'accuracy'))],
];
$hasAttempts = $o['attempts'] > 0;

$layout = 'app'; $activeNav = 'statistics'; $pageTitle = 'Statistics';
$extraCss = ['dashboard.css', 'progress.css', 'admin.css'];
$extraJs = ['admin-charts.js'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Admin statistics</h1><p class="muted">Platform activity and category analytics.</p></div>
<section class="stat-grid">
  <div class="card stat"><span class="stat-label">Total attempts</span><strong><?= $o['attempts'] ?></strong><small class="muted"><?= $o['attempts_7d'] ?> in the last 7 days</small></div>
  <div class="card stat"><span class="stat-label">Overall accuracy</span><strong class="c-blue"><?= $o['accuracy'] ?>%</strong><small class="muted">All learners</small></div>
  <div class="card stat"><span class="stat-label">Active learners (7d)</span><strong><?= $o['learners_7d'] ?></strong><small class="muted">of <?= $o['users'] ?> registered</small></div>
  <div class="card stat"><span class="stat-label">New users (30d)</span><strong><?= array_sum($reg['values']) ?></strong><small class="muted">Registrations</small></div>
</section>

<div class="chart-grid">
  <section class="card"><h2>User registrations</h2><p class="muted small">Last 30 days</p><div class="chart-box short"><canvas id="c-reg" role="img" aria-label="Registrations per day"></canvas></div></section>
  <section class="card"><h2>Learning activity</h2><p class="muted small">Attempts per day, last 30 days</p><div class="chart-box short"><canvas id="c-att" role="img" aria-label="Attempts per day"></canvas></div></section>
  <?php if ($hasAttempts): ?>
  <section class="card"><h2>Scenario popularity</h2><p class="muted small">Top 10 by attempts</p><div class="chart-box"><canvas id="c-pop" role="img" aria-label="Most attempted scenarios"></canvas></div></section>
  <section class="card"><h2>Category performance</h2><p class="muted small">Average accuracy</p><div class="chart-box"><canvas id="c-cat" role="img" aria-label="Accuracy by category"></canvas></div></section>
  <section class="card"><h2>Accuracy by difficulty</h2><div class="chart-box short"><canvas id="c-diff" role="img" aria-label="Accuracy by difficulty"></canvas></div></section>
  <?php else: ?>
  <section class="card span-2"><h2>Attempt statistics</h2><p class="muted">No attempts have been recorded yet. Scenario, category and difficulty charts appear after learners start answering.</p></section>
  <?php endif; ?>
</div>
<p id="chart-error" class="alert alert-error" role="alert" hidden>Charts could not be loaded. Check your internet connection (Chart.js loads from a CDN).</p>
<script id="admin-chart-data" type="application/json"><?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
