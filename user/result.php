<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';

$uid = (int)$currentUser['id'];
$a = db()->prepare(
    'SELECT a.id, a.is_correct, a.score, a.xp_earned, a.scenario_id,
            s.title, s.category_id, s.explanation, s.red_flags, s.safety_tip,
            c.name AS category_name, so.option_text AS selected_text, so.classification AS selected_class
     FROM attempts a
     JOIN scenarios s ON s.id = a.scenario_id
     JOIN categories c ON c.id = s.category_id
     JOIN options so ON so.id = a.selected_option_id
     WHERE a.id = ? AND a.user_id = ?'          // ownership check: users only see their own attempts
);
$a->execute([(int)($_GET['attempt'] ?? 0), $uid]);
$r = $a->fetch();
if (!$r) {
    flash('warning', 'That result was not found.');
    redirect('user/challenges.php');
}

$co = db()->prepare('SELECT option_text, classification FROM options WHERE scenario_id = ? AND is_correct = 1');
$co->execute([$r['scenario_id']]);
$correct = $co->fetch();

$next = db()->prepare(
    'SELECT s.id FROM scenarios s
     WHERE s.category_id = ? AND s.is_active = 1 AND s.id <> ?
       AND NOT EXISTS (SELECT 1 FROM attempts x WHERE x.user_id = ? AND x.scenario_id = s.id)
     ORDER BY s.id LIMIT 1'
);
$next->execute([$r['category_id'], $r['scenario_id'], $uid]);
$nextId = $next->fetchColumn();

$sum = null;
if (!$nextId) { // category finished: show a summary
    $q = db()->prepare(
        'SELECT COUNT(*) AS n, COALESCE(SUM(a.is_correct),0) AS ok FROM attempts a
         JOIN scenarios s ON s.id = a.scenario_id WHERE a.user_id = ? AND s.category_id = ?'
    );
    $q->execute([$uid, $r['category_id']]);
    $sum = $q->fetch();
}

$ok = (int)$r['is_correct'] === 1;
$rewards = $_SESSION['attempt_rewards'][(int)$r['id']] ?? null;
$lvl = level_for_xp((int)$currentUser['total_xp']);
$flags = array_filter(array_map('trim', explode("\n", (string)$r['red_flags'])));
$flagTitle = $correct['classification'] === 'SAFE' ? 'Signs it is legitimate' : 'Red flags';

$layout = 'app'; $activeNav = 'challenges'; $pageTitle = 'Result';
$extraCss = ['challenges.css'];
include __DIR__ . '/../includes/header.php';
?>
<div class="result <?= $ok ? 'ok' : 'learn' ?>">
  <p class="badge-state"><?= $ok ? 'Correct' : 'Not quite' ?></p>
  <h1><?= $ok ? 'Great catch!' : 'Let us learn from this' ?></h1>
  <p class="muted">You chose <strong><?= e(strtoupper($r['selected_text'])) ?></strong>. Correct answer: <strong><?= e(strtoupper($correct['option_text'])) ?></strong></p>

  <div class="score-row">
    <div class="card stat"><span class="stat-label">Score</span><strong>+<?= (int)$r['score'] ?></strong><small class="muted">Points</small></div>
    <div class="card stat"><span class="stat-label">XP earned</span><strong>+<?= (int)$r['xp_earned'] ?></strong><small class="muted">Experience</small></div>
  </div>
  <?php if ($rewards && $rewards['level_up']): ?>
  <div class="levelup"><i class="bi bi-arrow-up-circle"></i> Level up! You are now Level <?= (int)$rewards['level'] ?>: <?= e($rewards['level_name']) ?></div>
  <?php endif; ?>
  <?php if ($rewards && $rewards['badges']): ?>
  <section class="card newbadges">
    <h2>New badge<?= count($rewards['badges']) > 1 ? 's' : '' ?> unlocked</h2>
    <div class="badge-row">
      <?php foreach ($rewards['badges'] as $b): ?>
      <div class="badge-chip" title="<?= e($b['description']) ?>"><i class="bi <?= e($b['icon']) ?>"></i><span><?= e($b['name']) ?><?= $b['xp_reward'] > 0 ? ' · +' . (int)$b['xp_reward'] . ' XP' : '' ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
  <section class="card">
    <p class="stat-label">Your progress</p>
    <h2>Level <?= (int)$lvl['level'] ?> · <?= e($lvl['name']) ?></h2>
    <div class="progress" role="progressbar" aria-valuenow="<?= (int)$lvl['percent'] ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= (int)$lvl['percent'] ?>%"></span></div>
    <p class="muted"><?= (int)$currentUser['total_xp'] ?> XP total<?= $lvl['next_xp'] !== null ? ' · ' . (int)$lvl['next_xp'] . ' XP for ' . e($lvl['next_name']) : ' · top level reached' ?></p>
  </section>
  <?php if ($ok && (int)$r['xp_earned'] === 0): ?>
    <p class="muted">You have answered this one correctly before, so no extra XP this time. Great for practice.</p>
  <?php endif; ?>

  <section class="card">
    <h2>Why this is the right answer</h2>
    <p><?= e($r['explanation']) ?></p>
    <h3><?= e($flagTitle) ?></h3>
    <ul class="flags"><?php foreach ($flags as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
    <p class="tip"><i class="bi bi-lightbulb"></i> <strong>Safety tip:</strong> <?= e($r['safety_tip']) ?></p>
  </section>

  <?php if ($sum): ?>
  <section class="card summary">
    <h2>Set complete: <?= e($r['category_name']) ?></h2>
    <p class="muted">You have attempted every scenario here. Across all your attempts in this category: <?= (int)$sum['ok'] ?> correct out of <?= (int)$sum['n'] ?> (<?= (int)$sum['n'] > 0 ? (int)round($sum['ok'] / $sum['n'] * 100) : 0 ?>%).</p>
  </section>
  <?php endif; ?>

  <div class="btn-row">
    <?php if ($nextId): ?>
      <a class="btn btn-primary" href="<?= e(url('user/challenge.php?id=' . (int)$nextId)) ?>">Continue to next challenge</a>
    <?php else: ?>
      <a class="btn btn-primary" href="<?= e(url('user/challenges.php')) ?>">Choose another category</a>
    <?php endif; ?>
    <a class="btn btn-ghost" href="<?= e(url('user/scenario-list.php?category=' . (int)$r['category_id'])) ?>">Try another scenario</a>
    <a class="btn btn-ghost" href="<?= e(url('user/dashboard.php')) ?>">Return to dashboard</a>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
