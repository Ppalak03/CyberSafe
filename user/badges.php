<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/gamification.php';

$uid = (int)$currentUser['id'];
$pdo = db();

// Catch-up check: awards anything earned before badges existed. Idempotent (UNIQUE user+badge).
try {
    $pdo->beginTransaction();
    $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$uid]);
    $new = award_badges($pdo, $uid);
    $pdo->commit();
    if ($new) {
        flash('success', 'New badge unlocked: ' . implode(', ', array_column($new, 'name')));
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('[badges] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
}

$m = user_metrics($pdo, $uid);
$lvl = level_for_xp($m['xp']);
$q = $pdo->prepare(
    'SELECT b.*, ub.earned_at FROM badges b
     LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = ?
     ORDER BY b.id'
);
$q->execute([$uid]);
$all = $q->fetchAll();
$earned = array_filter($all, fn($b) => $b['earned_at'] !== null);
$locked = array_filter($all, fn($b) => $b['earned_at'] === null);

$layout = 'app'; $activeNav = 'badges'; $pageTitle = 'Badges';
$extraCss = ['badges.css'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Badges and achievements</h1><p class="muted">Unlock badges by practising. Eligibility is checked on the server after every answer.</p></div>

<section class="card level-card">
  <div>
    <p class="stat-label">Current level</p>
    <h2>Level <?= (int)$lvl['level'] ?> · <?= e($lvl['name']) ?></h2>
    <div class="progress" role="progressbar" aria-valuenow="<?= (int)$lvl['percent'] ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= (int)$lvl['percent'] ?>%"></span></div>
    <p class="muted"><?= (int)$m['xp'] ?> XP<?= $lvl['next_xp'] !== null ? ' · ' . (int)$lvl['next_xp'] . ' XP for ' . e($lvl['next_name']) : ' · top level reached' ?></p>
  </div>
  <ol class="levels">
    <?php foreach (LEVELS as $n => $l): ?>
    <li class="<?= $n === $lvl['level'] ? 'current' : ($n < $lvl['level'] ? 'passed' : '') ?>"><span><?= (int)$n ?></span> <?= e($l['name']) ?> <small><?= (int)$l['min_xp'] ?> XP</small></li>
    <?php endforeach; ?>
  </ol>
</section>

<h2 class="section-h">Earned (<?= count($earned) ?>)</h2>
<?php if (!$earned): ?>
  <div class="card"><p class="muted">No badges yet. Finish your first scenario to earn First Shield.</p>
    <a class="btn btn-primary btn-sm" href="<?= e(url('user/challenges.php')) ?>">Start a challenge</a></div>
<?php else: ?>
<div class="badge-grid">
  <?php foreach ($earned as $b): ?>
  <article class="card bcard unlocked">
    <span class="state">Unlocked</span>
    <i class="bi <?= e($b['icon']) ?>"></i>
    <h3><?= e($b['name']) ?></h3>
    <p class="muted"><?= e($b['description']) ?></p>
    <p class="when">Earned <?= e(date('M j, Y', strtotime($b['earned_at']))) ?><?= (int)$b['xp_reward'] > 0 ? ' · +' . (int)$b['xp_reward'] . ' XP' : '' ?></p>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($locked): ?>
<h2 class="section-h">Locked (<?= count($locked) ?>)</h2>
<div class="badge-grid">
  <?php foreach ($locked as $b): $p = badge_progress($b, $m); ?>
  <article class="card bcard locked">
    <span class="state">Locked</span>
    <i class="bi <?= e($b['icon']) ?>"></i>
    <h3><?= e($b['name']) ?></h3>
    <p class="muted"><?= e($b['description']) ?><?= (int)$b['xp_reward'] > 0 ? ' Reward: +' . (int)$b['xp_reward'] . ' XP.' : '' ?></p>
    <div class="progress thin" role="progressbar" aria-valuenow="<?= (int)$p['percent'] ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= (int)$p['percent'] ?>%"></span></div>
    <p class="when"><?= e($p['label']) ?></p>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
