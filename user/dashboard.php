<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/queries.php';

$uid      = (int)$currentUser['id'];
$xp       = (int)$currentUser['total_xp'];
$level    = level_for_xp($xp);
$stats    = user_stats($uid);
$perf     = category_performance($uid);
$activity = recent_activity($uid, 5);
$badges   = recent_badges($uid, 3);
$continue = continue_learning($uid);
$recs     = recommended_categories($perf);
$isNew    = $stats['total'] === 0;

function bar_class(?int $acc): string
{
    return $acc === null ? '' : ($acc >= 75 ? 'good' : ($acc >= 50 ? 'mid' : 'low'));
}

$layout = 'app';
$activeNav = 'dashboard';
$pageTitle = 'Dashboard';
$extraCss = ['dashboard.css'];
$extraJs = ['dashboard.js'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1>Welcome back, <?= e($currentUser['name']) ?>!</h1>
  <p class="muted">Personal overview of your level, XP, learning metrics and recommended practice.</p>
</div>

<?php if ($isNew): ?>
<section class="card empty-hero">
  <i class="bi bi-shield-check"></i>
  <h2>Ready for your first challenge?</h2>
  <p class="muted">Read a realistic message, decide if it is safe, suspicious or a scam, and learn why. Each correct answer earns XP.</p>
  <a class="btn btn-primary" href="<?= e(url('user/challenges.php')) ?>">Start your first challenge</a>
</section>
<?php endif; ?>

<section class="stat-grid" aria-label="Statistics">
  <div class="card stat"><span class="stat-label">Total attempts</span><strong><?= $stats['total'] ?></strong><small class="muted">Across all categories</small></div>
  <div class="card stat"><span class="stat-label">Correct answers</span><strong class="c-green"><?= $stats['correct'] ?></strong><small class="muted">Spotted correctly</small></div>
  <div class="card stat"><span class="stat-label">Incorrect answers</span><strong class="c-red"><?= $stats['incorrect'] ?></strong><small class="muted">Learning moments</small></div>
  <div class="card stat"><span class="stat-label">Accuracy</span><strong class="c-blue"><?= $stats['accuracy'] ?>%</strong><small class="muted"><?= $isNew ? 'Starts after your first answer' : 'Overall' ?></small></div>
</section>

<div class="dash-grid">
  <section class="card">
    <p class="stat-label">Your learning level</p>
    <h2>Level <?= (int)$level['level'] ?> · <?= e($level['name']) ?></h2>
    <div class="progress" role="progressbar" aria-valuenow="<?= (int)$level['percent'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progress to next level">
      <span data-width="<?= (int)$level['percent'] ?>" style="width:0"></span>
    </div>
    <p class="muted">
      <?php if ($level['next_xp'] !== null): ?>
        <?= $xp ?> / <?= (int)$level['next_xp'] ?> XP to <?= e($level['next_name']) ?>
      <?php else: ?>
        <?= $xp ?> XP · you have reached the top level
      <?php endif; ?>
    </p>
  </section>

  <section class="card">
    <p class="stat-label">Continue learning</p>
    <?php if ($continue): ?>
      <h2><?= e($continue['title']) ?></h2>
      <p class="muted"><?= e($continue['category']) ?> · <?= e($continue['difficulty']) ?></p>
      <a class="btn btn-primary btn-sm" href="<?= e(url('user/challenge.php?id=' . (int)$continue['id'])) ?>">Resume</a>
    <?php else: ?>
      <h2>All caught up</h2>
      <p class="muted">You have attempted every available scenario. New ones are added regularly.</p>
    <?php endif; ?>
  </section>

  <section class="card span-2">
    <h2>Recommended for you</h2>
    <?php if (!$recs): ?>
      <p class="muted">No scenarios are available yet.</p>
    <?php else: ?>
    <div class="rec-list">
      <?php foreach ($recs as $c): ?>
      <a class="rec" href="<?= e(url('user/scenario-list.php?category=' . (int)$c['id'])) ?>">
        <i class="bi <?= e($c['icon']) ?>"></i>
        <span><strong><?= e($c['name']) ?></strong>
          <small class="muted"><?= $c['attempts'] > 0 ? 'Accuracy ' . (int)$c['accuracy'] . '% · room to improve' : 'Not tried yet · ' . (int)$c['scenario_count'] . ' scenarios' ?></small></span>
        <i class="bi bi-arrow-right"></i>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>Recent activity</h2>
    <?php if (!$activity): ?>
      <p class="muted">Your answers will show up here.</p>
    <?php else: ?>
    <ul class="activity">
      <?php foreach ($activity as $a): ?>
      <li>
        <span class="dot <?= $a['is_correct'] ? 'ok' : 'bad' ?>" aria-hidden="true"></span>
        <span><strong><?= e($a['title']) ?></strong>
          <small class="muted"><?= e($a['category']) ?> · <?= $a['is_correct'] ? 'Correct' : 'Incorrect' ?> · +<?= (int)$a['xp_earned'] ?> XP · <?= e(date('M j, g:i a', strtotime($a['attempted_at']))) ?></small></span>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>Recent achievements</h2>
    <?php if (!$badges): ?>
      <p class="muted">Earn your first badge by completing a scenario.</p>
    <?php else: ?>
    <div class="badge-row">
      <?php foreach ($badges as $b): ?>
      <div class="badge-chip" title="<?= e($b['description']) ?>"><i class="bi <?= e($b['icon']) ?>"></i><span><?= e($b['name']) ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <a class="link-more" href="<?= e(url('user/badges.php')) ?>">View all badges →</a>
  </section>

  <section class="card span-2">
    <h2>Category performance</h2>
    <?php if (!$perf): ?>
      <p class="muted">No categories available yet.</p>
    <?php else: ?>
    <div class="cat-perf">
      <?php foreach ($perf as $c): ?>
      <div class="cat-row">
        <span class="cat-name"><i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></span>
        <div class="progress thin"><span class="<?= e(bar_class($c['accuracy'])) ?>" data-width="<?= (int)($c['accuracy'] ?? 0) ?>" style="width:0"></span></div>
        <span class="cat-pct"><?= $c['accuracy'] === null ? '—' : (int)$c['accuracy'] . '%' ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
