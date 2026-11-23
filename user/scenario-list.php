<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';

$catId = (int)($_GET['category'] ?? 0);
$c = db()->prepare('SELECT id, name FROM categories WHERE id = ? AND is_active = 1');
$c->execute([$catId]);
$cat = $c->fetch();
if (!$cat) {
    flash('warning', 'That category is not available.');
    redirect('user/challenges.php');
}

$s = db()->prepare(
    'SELECT s.id, s.title, s.difficulty,
            (SELECT COUNT(*) FROM attempts a WHERE a.user_id = ? AND a.scenario_id = s.id) AS tries,
            (SELECT COALESCE(MAX(a.is_correct),0) FROM attempts a WHERE a.user_id = ? AND a.scenario_id = s.id) AS solved
     FROM scenarios s WHERE s.category_id = ? AND s.is_active = 1 ORDER BY s.id'
);
$s->execute([$currentUser['id'], $currentUser['id'], $catId]);
$rows = $s->fetchAll();

$layout = 'app'; $activeNav = 'challenges'; $pageTitle = $cat['name'];
$extraCss = ['challenges.css'];
include __DIR__ . '/../includes/header.php';
?>
<p class="crumb"><a href="<?= e(url('user/challenges.php')) ?>">Challenges</a> / <?= e($cat['name']) ?></p>
<div class="page-head"><h1><?= e($cat['name']) ?></h1><p class="muted">Choose a scenario. You can replay any scenario, but XP is awarded once, the first time you answer it correctly.</p></div>
<?php if (!$rows): ?>
  <div class="card"><p class="muted">No active scenarios in this category yet.</p></div>
<?php else: ?>
<div class="list">
  <?php foreach ($rows as $r):
    $status = (int)$r['tries'] === 0 ? ['New', 'new'] : ((int)$r['solved'] === 1 ? ['Completed', 'done'] : ['Attempted', 'tried']); ?>
  <div class="card list-row">
    <div><h2><?= e($r['title']) ?></h2>
      <p><span class="pill <?= e(strtolower($r['difficulty'])) ?>"><?= e($r['difficulty']) ?></span> <span class="status <?= e($status[1]) ?>"><?= e($status[0]) ?></span></p></div>
    <a class="btn btn-primary btn-sm" href="<?= e(url('user/challenge.php?id=' . (int)$r['id'])) ?>"><?= (int)$r['tries'] === 0 ? 'Start challenge' : 'Replay' ?></a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
