<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';

$layout = 'app'; $activeNav = 'settings'; $pageTitle = 'Settings';
$extraCss = ['account.css'];
$extraJs = ['settings.js'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Settings</h1><p class="muted">Preferences and session controls.</p></div>
<div class="acct-grid">
  <section class="card">
    <h2>Preferences</h2>
    <label class="switch-row" for="pref-motion">
      <input id="pref-motion" type="checkbox">
      <span><strong>Reduce animations</strong><small class="muted">Turns off progress-bar and chart animations. Saved in this browser only.</small></span>
    </label>
  </section>

  <section class="card">
    <h2>Session</h2>
    <p class="muted">Signed in as <strong><?= e($currentUser['email']) ?></strong></p>
    <p class="muted">For your safety, you are signed out automatically after <?= (int)(SESSION_TIMEOUT / 60) ?> minutes of inactivity.</p>
    <form method="post" action="<?= e(url('auth/logout.php')) ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-ghost" data-confirm="Log out of CyberSafe?"><i class="bi bi-box-arrow-left"></i> Log out</button>
    </form>
  </section>

  <section class="card span-2">
    <h2>Account</h2>
    <p class="muted">Update your name or password on the <a href="<?= e(url('user/profile.php')) ?>">Profile page</a>.</p>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
