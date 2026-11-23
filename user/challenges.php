<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';

$s = db()->prepare(
    "SELECT c.id, c.name, c.description, c.icon,
            COUNT(s.id) AS total,
            COALESCE(SUM(s.difficulty='Easy'),0) AS easy,
            COALESCE(SUM(s.difficulty='Medium'),0) AS medium,
            COALESCE(SUM(s.difficulty='Hard'),0) AS hard,
            (SELECT COUNT(DISTINCT a.scenario_id) FROM attempts a JOIN scenarios s2 ON s2.id = a.scenario_id
              WHERE a.user_id = ? AND s2.category_id = c.id AND s2.is_active = 1) AS done
     FROM categories c LEFT JOIN scenarios s ON s.category_id = c.id AND s.is_active = 1
     WHERE c.is_active = 1 GROUP BY c.id, c.name, c.description, c.icon ORDER BY c.id"
);
$s->execute([$currentUser['id']]);
$cats = $s->fetchAll();

$layout = 'app'; $activeNav = 'challenges'; $pageTitle = 'Challenges';
$extraCss = ['challenges.css'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Challenge categories</h1><p class="muted">Pick a category and decide whether each scenario is safe, suspicious or a scam.</p></div>
<?php if (!$cats): ?>
  <div class="card"><p class="muted">No categories are available yet.</p></div>
<?php else: ?>
<div class="cat-grid">
  <?php foreach ($cats as $c): ?>
  <article class="card cat-card">
    <i class="bi <?= e($c['icon']) ?>"></i>
    <h2><?= e($c['name']) ?></h2>
    <p class="muted"><?= e($c['description']) ?></p>
    <p class="meta"><strong><?= (int)$c['total'] ?></strong> scenarios · <?= (int)$c['done'] ?> attempted</p>
    <p class="diffs"><span class="pill easy">Easy <?= (int)$c['easy'] ?></span><span class="pill medium">Medium <?= (int)$c['medium'] ?></span><span class="pill hard">Hard <?= (int)$c['hard'] ?></span></p>
    <?php if ((int)$c['total'] > 0): ?>
      <a class="btn btn-primary btn-block" href="<?= e(url('user/scenario-list.php?category=' . (int)$c['id'])) ?>">View challenges</a>
    <?php else: ?>
      <button class="btn btn-ghost btn-block" disabled>Coming soon</button>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
