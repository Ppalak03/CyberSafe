<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/queries.php';

$uid   = (int)$currentUser['id'];
$stats = user_stats($uid);
$perf  = category_performance($uid);
$weak  = weak_area($perf);
$total = $stats['total'];

$perPage = 10;
$pages   = max(1, (int)ceil($total / $perPage));
$page    = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$history = $total > 0 ? attempt_history($uid, $perPage, ($page - 1) * $perPage) : [];

$layout = 'app'; $activeNav = 'progress'; $pageTitle = 'My Progress';
$extraCss = ['dashboard.css', 'progress.css'];
$extraJs = ['charts.js'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Progress analytics</h1><p class="muted">Accuracy trends, category performance and weak-area recommendations.</p></div>

<section class="stat-grid five" aria-label="Overview">
  <div class="card stat"><span class="stat-label">Attempts</span><strong><?= $stats['total'] ?></strong><small class="muted">Total</small></div>
  <div class="card stat"><span class="stat-label">Correct</span><strong class="c-green"><?= $stats['correct'] ?></strong><small class="muted">Total</small></div>
  <div class="card stat"><span class="stat-label">Incorrect</span><strong class="c-red"><?= $stats['incorrect'] ?></strong><small class="muted">Total</small></div>
  <div class="card stat"><span class="stat-label">Accuracy</span><strong class="c-blue"><?= $stats['accuracy'] ?>%</strong><small class="muted">Overall</small></div>
  <div class="card stat"><span class="stat-label">Total XP</span><strong><?= (int)$currentUser['total_xp'] ?></strong><small class="muted"><?= e(level_for_xp((int)$currentUser['total_xp'])['name']) ?></small></div>
</section>

<?php if ($total === 0): ?>
<section class="card empty-hero">
  <i class="bi bi-graph-up"></i>
  <h2>No data yet</h2>
  <p class="muted">Answer your first scenario and your charts, history and recommendations will appear here.</p>
  <a class="btn btn-primary" href="<?= e(url('user/challenges.php')) ?>">Start a challenge</a>
</section>
<?php else: ?>

<div class="chart-grid">
  <section class="card span-2"><h2>Accuracy over time</h2><p class="muted small">Running accuracy by day (last 30 active days)</p>
    <div class="chart-box"><canvas id="chart-line" role="img" aria-label="Line chart of accuracy over time"></canvas></div></section>
  <section class="card"><h2>Correct vs incorrect</h2>
    <div class="chart-box short"><canvas id="chart-doughnut" role="img" aria-label="Doughnut chart of correct and incorrect answers"></canvas></div></section>
  <section class="card"><h2>Category accuracy</h2>
    <div class="chart-box short"><canvas id="chart-bar" role="img" aria-label="Bar chart of accuracy by category"></canvas></div></section>
</div>
<p id="chart-error" class="alert alert-error" role="alert" hidden>Charts could not be loaded. The numbers and history below are still accurate.</p>

<section class="card weak">
  <h2>Recommended practice</h2>
  <?php $cat = $weak['category']; ?>
  <?php if ($weak['type'] === 'perfect'): ?>
    <p>Excellent: you have answered every attempt correctly in each category you have tried. Challenge yourself with a category you have not explored yet.</p>
    <a class="btn btn-primary btn-sm" href="<?= e(url('user/challenges.php')) ?>">Browse categories</a>
  <?php elseif ($weak['type'] === 'single'): ?>
    <p>So far you have only practised <strong><?= e($cat['name']) ?></strong> (<?= (int)$cat['accuracy'] ?>% accuracy). Try other categories to get a fuller picture of your skills.</p>
    <a class="btn btn-primary btn-sm" href="<?= e(url('user/challenges.php')) ?>">Browse categories</a>
  <?php else: ?>
    <p>Your accuracy in <strong><?= e($cat['name']) ?></strong> (<?= (int)$cat['accuracy'] ?>%) is lower than your other categories. Practice more scenarios in this area.</p>
    <a class="btn btn-primary btn-sm" href="<?= e(url('user/scenario-list.php?category=' . (int)$cat['id'])) ?>">Practice now</a>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Performance history</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Scenario</th><th>Category</th><th>Your answer</th><th>Correct answer</th><th>Result</th><th>Score</th><th>XP</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($history as $h): ?>
        <tr>
          <td><?= e($h['title']) ?></td>
          <td><?= e($h['category']) ?></td>
          <td><?= e($h['selected']) ?></td>
          <td><?= e((string)$h['correct_answer']) ?></td>
          <td><span class="res <?= $h['is_correct'] ? 'ok' : 'bad' ?>"><?= $h['is_correct'] ? 'Correct' : 'Incorrect' ?></span></td>
          <td><?= (int)$h['score'] ?></td>
          <td><?= (int)$h['xp_earned'] ?></td>
          <td><?= e(date('M j, Y g:i a', strtotime($h['attempted_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <nav class="pager" aria-label="History pages">
    <?php if ($page > 1): ?><a class="btn btn-ghost btn-sm" href="?page=<?= $page - 1 ?>">← Newer</a><?php endif; ?>
    <span class="muted">Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn btn-ghost btn-sm" href="?page=<?= $page + 1 ?>">Older →</a><?php endif; ?>
  </nav>
  <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
