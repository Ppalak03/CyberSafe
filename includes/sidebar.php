<?php
$activeNav = $activeNav ?? '';
$isAdminPage = $isAdminPage ?? false;
if ($isAdminPage) {
    $label = 'Administration';
    $home = 'admin/dashboard.php';
    $items = [
      'dashboard'  => ['admin/dashboard.php',  'bi-grid-1x2',     'Dashboard'],
      'scenarios'  => ['admin/scenarios.php',  'bi-collection',   'Scenarios'],
      'categories' => ['admin/categories.php', 'bi-tags',         'Categories'],
      'users'      => ['admin/users.php',      'bi-people',       'Users'],
      'statistics' => ['admin/statistics.php', 'bi-bar-chart',    'Statistics'],
    ];
} else {
    $label = 'Workspace';
    $home = 'user/dashboard.php';
    $items = [
      'dashboard'  => ['user/dashboard.php',  'bi-grid-1x2',     'Dashboard'],
      'challenges' => ['user/challenges.php', 'bi-bullseye',     'Challenges'],
      'progress'   => ['user/progress.php',   'bi-graph-up',     'My Progress'],
      'badges'     => ['user/badges.php',     'bi-award',        'Badges'],
      'profile'    => ['user/profile.php',    'bi-person',       'Profile'],
      'settings'   => ['user/settings.php',   'bi-gear',         'Settings'],
    ];
}
?>
<aside id="sidebar" class="sidebar" aria-label="<?= e($label) ?>">
  <a class="brand" href="<?= e(url($home)) ?>"><i class="bi bi-shield-lock-fill"></i> CyberSafe</a>
  <p class="sidebar-label"><?= e($label) ?></p>
  <nav>
<?php foreach ($items as $key => [$href, $icon, $text]): ?>
    <a href="<?= e(url($href)) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>" <?= $activeNav === $key ? 'aria-current="page"' : '' ?>>
      <i class="bi <?= e($icon) ?>"></i> <?= e($text) ?>
    </a>
<?php endforeach; ?>
<?php if (($currentUser['role'] ?? '') === 'admin'): ?>
    <?php if ($isAdminPage): ?>
    <a href="<?= e(url('user/dashboard.php')) ?>"><i class="bi bi-box-arrow-up-right"></i> Learner view</a>
    <?php else: ?>
    <a href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-speedometer2"></i> Admin panel</a>
    <?php endif; ?>
<?php endif; ?>
  </nav>
  <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="sidebar-logout">
    <?= csrf_field() ?>
    <button type="submit"><i class="bi bi-box-arrow-left"></i> Logout</button>
  </form>
</aside>
<div id="sidebar-backdrop" class="sidebar-backdrop" hidden></div>
