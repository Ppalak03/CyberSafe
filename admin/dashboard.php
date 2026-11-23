<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_init.php';

$o = admin_overview();
$cats = popular_categories(5);
$regs = recent_registrations(5);
$act = recent_platform_activity(8);
$maxAttempts = max(1, ...array_map(fn($c) => (int)$c['attempts'], $cats ?: [['attempts' => 1]]));

$layout = 'app'; $activeNav = 'dashboard'; $pageTitle = 'Admin dashboard';
$extraCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Admin dashboard</h1><p class="muted">Platform-wide user, scenario and attempt statistics with quick actions.</p></div>

<section class="stat-grid" aria-label="Platform totals">
  <div class="card stat"><span class="stat-label">Registered users</span><strong><?= $o['users'] ?></strong><small class="muted"><?= $o['active_users'] ?> active accounts</small></div>
  <div class="card stat"><span class="stat-label">Scenarios</span><strong><?= $o['scenarios'] ?></strong><small class="muted"><?= $o['active_scen'] ?> published</small></div>
  <div class="card stat"><span class="stat-label">Attempts</span><strong><?= $o['attempts'] ?></strong><small class="muted"><?= $o['accuracy'] ?>% overall accuracy</small></div>
  <div class="card stat"><span class="stat-label">Last 7 days</span><strong><?= $o['attempts_7d'] ?></strong><small class="muted"><?= $o['learners_7d'] ?> learners active</small></div>
</section>

<div class="dash-grid">
  <section class="card">
    <h2>Most popular categories</h2>
    <div class="cat-perf">
      <?php foreach ($cats as $c): ?>
      <div class="cat-row"><span class="cat-name"><?= e($c['name']) ?></span>
        <div class="progress thin"><span data-width="<?= (int)round($c['attempts'] / $maxAttempts * 100) ?>" style="width:0"></span></div>
        <span class="cat-pct"><?= (int)$c['attempts'] ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="card">
    <h2>Quick actions</h2>
    <div class="quick">
      <a class="btn btn-primary" href="<?= e(url('admin/scenario-form.php')) ?>"><i class="bi bi-plus-lg"></i> Add new scenario</a>
      <a class="btn btn-ghost" href="<?= e(url('admin/categories.php')) ?>"><i class="bi bi-tags"></i> Manage categories</a>
      <a class="btn btn-ghost" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-people"></i> View registered users</a>
      <a class="btn btn-ghost" href="<?= e(url('admin/statistics.php')) ?>"><i class="bi bi-bar-chart"></i> Statistics</a>
    </div>
  </section>

  <section class="card">
    <h2>Recent registrations</h2>
    <?php if (!$regs): ?><p class="muted">No learners have registered yet.</p><?php else: ?>
    <ul class="activity"><?php foreach ($regs as $r): ?>
      <li><span class="dot ok" aria-hidden="true"></span><span><a href="<?= e(url('admin/user-detail.php?id=' . (int)$r['id'])) ?>"><strong><?= e($r['name']) ?></strong></a>
        <small class="muted"><?= e($r['email']) ?> · <?= e(date('M j, g:i a', strtotime($r['created_at']))) ?></small></span></li>
    <?php endforeach; ?></ul><?php endif; ?>
  </section>

  <section class="card">
    <h2>Recent challenge activity</h2>
    <?php if (!$act): ?><p class="muted">No attempts yet.</p><?php else: ?>
    <ul class="activity"><?php foreach ($act as $a): ?>
      <li><span class="dot <?= $a['is_correct'] ? 'ok' : 'bad' ?>" aria-hidden="true"></span><span><strong><?= e($a['title']) ?></strong>
        <small class="muted"><?= e($a['user_name']) ?> · <?= $a['is_correct'] ? 'Correct' : 'Incorrect' ?> · <?= e(date('M j, g:i a', strtotime($a['attempted_at']))) ?></small></span></li>
    <?php endforeach; ?></ul><?php endif; ?>
  </section>
</div>
<?php $extraJs = ['dashboard.js']; include __DIR__ . '/../includes/footer.php'; ?>
