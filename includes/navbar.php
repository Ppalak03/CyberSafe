<?php $initial = mb_strtoupper(mb_substr($currentUser['name'] ?? '?', 0, 1)); ?>
<header class="topbar">
  <button id="menu-toggle" class="icon-btn menu-toggle" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
    <i class="bi bi-list"></i>
  </button>
  <span class="topbar-title"><?= e($pageTitle ?? '') ?></span>
  <div class="topbar-right">
    <a class="user-chip" href="<?= e(url('user/profile.php')) ?>">
      <span class="avatar" aria-hidden="true"><?= e($initial) ?></span>
      <span class="user-name"><?= e($currentUser['name'] ?? '') ?></span>
    </a>
  </div>
</header>
