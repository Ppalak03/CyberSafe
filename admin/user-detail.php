<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_init.php';

$id = (int)($_GET['id'] ?? 0);
// Never select the password hash.
$s = db()->prepare('SELECT id, name, email, role, is_active, total_xp, created_at FROM users WHERE id = ?');
$s->execute([$id]);
$u = $s->fetch();
if (!$u) {
    flash('warning', 'That user was not found.');
    redirect('admin/users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (($_POST['action'] ?? '') === 'toggle_active') {
        if ((int)$u['id'] === (int)$currentUser['id'] || $u['role'] !== 'user') {
            flash('error', 'Administrator accounts cannot be deactivated here.'); // prevents lock-out
        } else {
            db()->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ? AND role = \'user\'')->execute([$id]);
            flash('success', $u['is_active'] ? 'Account deactivated.' : 'Account reactivated.');
        }
    }
    redirect('admin/user-detail.php?id=' . $id);
}

$stats = user_stats($id);
$level = level_for_xp((int)$u['total_xp']);
$perf = category_performance($id);
$badges = recent_badges($id, 50);
$perPage = 10;
$pages = max(1, (int)ceil($stats['total'] / $perPage));
$page = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$history = $stats['total'] > 0 ? attempt_history($id, $perPage, ($page - 1) * $perPage) : [];

$layout = 'app'; $activeNav = 'users'; $pageTitle = 'User performance';
$extraCss = ['dashboard.css', 'progress.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>
<p class="crumb"><a href="<?= e(url('admin/users.php')) ?>">Users</a> / <?= e($u['name']) ?></p>
<div class="page-head user-head">
  <div><h1><?= e($u['name']) ?></h1>
    <p class="muted"><?= e($u['email']) ?> · <?= e($u['role']) ?> · joined <?= e(date('M j, Y', strtotime($u['created_at']))) ?> ·
      <span class="status <?= $u['is_active'] ? 'done' : 'off' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></p></div>
  <?php if ($u['role'] === 'user'): ?>
  <form method="post" action=""><?= csrf_field() ?><input type="hidden" name="action" value="toggle_active">
    <button class="btn <?= $u['is_active'] ? 'btn-danger' : 'btn-primary' ?>" type="submit"
      data-confirm="<?= $u['is_active'] ? 'Deactivate this account? They will be unable to log in.' : 'Reactivate this account?' ?>">
      <?= $u['is_active'] ? 'Deactivate account' : 'Reactivate account' ?></button></form>
  <?php endif; ?>
</div>

<section class="stat-grid five">
  <div class="card stat"><span class="stat-label">Attempts</span><strong><?= $stats['total'] ?></strong></div>
  <div class="card stat"><span class="stat-label">Correct</span><strong class="c-green"><?= $stats['correct'] ?></strong></div>
  <div class="card stat"><span class="stat-label">Incorrect</span><strong class="c-red"><?= $stats['incorrect'] ?></strong></div>
  <div class="card stat"><span class="stat-label">Accuracy</span><strong class="c-blue"><?= $stats['accuracy'] ?>%</strong></div>
  <div class="card stat"><span class="stat-label">Total XP</span><strong><?= (int)$u['total_xp'] ?></strong><small class="muted">Level <?= (int)$level['level'] ?> · <?= e($level['name']) ?></small></div>
</section>

<div class="dash-grid">
  <section class="card"><h2>Earned badges (<?= count($badges) ?>)</h2>
    <?php if (!$badges): ?><p class="muted">No badges yet.</p><?php else: ?>
    <div class="badge-row"><?php foreach ($badges as $b): ?><div class="badge-chip" title="<?= e($b['description']) ?>"><i class="bi <?= e($b['icon']) ?>"></i><span><?= e($b['name']) ?></span></div><?php endforeach; ?></div><?php endif; ?>
  </section>
  <section class="card"><h2>Category performance</h2>
    <div class="cat-perf"><?php foreach ($perf as $c): $cls = $c['accuracy'] === null ? '' : ($c['accuracy'] >= 75 ? 'good' : ($c['accuracy'] >= 50 ? 'mid' : 'low')); ?>
      <div class="cat-row"><span class="cat-name"><?= e($c['name']) ?></span>
        <div class="progress thin"><span class="<?= e($cls) ?>" style="width:<?= (int)($c['accuracy'] ?? 0) ?>%"></span></div>
        <span class="cat-pct"><?= $c['accuracy'] === null ? '—' : (int)$c['accuracy'] . '%' ?></span></div>
    <?php endforeach; ?></div>
  </section>
</div>

<section class="card" style="margin-top:1rem">
  <h2>Attempt history</h2>
  <?php if (!$history): ?><p class="muted">This learner has no attempts yet.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Scenario</th><th>Category</th><th>Answer</th><th>Correct</th><th>Result</th><th>Score</th><th>XP</th><th>Date</th></tr></thead>
    <tbody><?php foreach ($history as $h): ?><tr>
      <td><?= e($h['title']) ?></td><td><?= e($h['category']) ?></td><td><?= e($h['selected']) ?></td><td><?= e((string)$h['correct_answer']) ?></td>
      <td><span class="res <?= $h['is_correct'] ? 'ok' : 'bad' ?>"><?= $h['is_correct'] ? 'Correct' : 'Incorrect' ?></span></td>
      <td><?= (int)$h['score'] ?></td><td><?= (int)$h['xp_earned'] ?></td><td><?= e(date('M j, Y g:i a', strtotime($h['attempted_at']))) ?></td></tr><?php endforeach; ?></tbody>
  </table></div>
  <?php admin_pager($page, $pages); endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
