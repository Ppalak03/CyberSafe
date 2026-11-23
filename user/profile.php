<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/queries.php';

$uid = (int)$currentUser['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_name') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = 'Enter your full name (up to 100 characters).';
        } else {
            // Only the name is updatable here; role and email are never read from this form.
            db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $uid]);
            flash('success', 'Profile updated.');
            redirect('user/profile.php');
        }
    } elseif ($action === 'change_password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $h = db()->prepare('SELECT password FROM users WHERE id = ?');
        $h->execute([$uid]);
        if (!password_verify($current, (string)$h->fetchColumn())) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }
        if ($problems = password_problems($new)) {
            $errors['new_password'] = implode(' ', $problems);
        } elseif ($new === $current) {
            $errors['new_password'] = 'Choose a password different from your current one.';
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
            redirect('user/profile.php');
        }
    }
}

// Keep what the user typed if the name form failed validation; otherwise show the saved name.
$nameValue = (($_POST['action'] ?? '') === 'update_name') ? (string)($_POST['name'] ?? '') : (string)$currentUser['name'];
$level  = level_for_xp((int)$currentUser['total_xp']);
$badges = recent_badges($uid, 50);

$layout = 'app'; $activeNav = 'profile'; $pageTitle = 'Profile';
$extraCss = ['account.css'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Profile</h1><p class="muted">Account details, learning level and security actions.</p></div>
<div class="acct-grid">
  <section class="card">
    <h2>Account information</h2>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?><input type="hidden" name="action" value="update_name">
      <div class="field"><label for="name">Full name</label>
        <input id="name" name="name" type="text" maxlength="100" required value="<?= e($nameValue) ?>">
        <p class="field-error"><?= e($errors['name'] ?? '') ?></p></div>
      <div class="field"><label for="email">Email</label>
        <input id="email" type="email" value="<?= e($currentUser['email']) ?>" disabled aria-describedby="email-note">
        <p id="email-note" class="hint">Email cannot be changed here.</p></div>
      <p class="muted">Member since <?= e(date('M j, Y', strtotime($currentUser['created_at']))) ?></p>
      <button class="btn btn-primary" type="submit">Save changes</button>
    </form>
  </section>

  <section class="card">
    <h2>Learning profile</h2>
    <p class="stat-label">Level <?= (int)$level['level'] ?></p>
    <h3><?= e($level['name']) ?></h3>
    <div class="progress" role="progressbar" aria-valuenow="<?= (int)$level['percent'] ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= (int)$level['percent'] ?>%"></span></div>
    <p class="muted"><?= (int)$currentUser['total_xp'] ?> XP earned · Badges earned: <?= count($badges) ?></p>
    <?php if ($badges): ?>
    <div class="badge-row"><?php foreach ($badges as $b): ?>
      <div class="badge-chip" title="<?= e($b['description']) ?>"><i class="bi <?= e($b['icon']) ?>"></i><span><?= e($b['name']) ?></span></div>
    <?php endforeach; ?></div>
    <?php endif; ?>
    <a class="link-more" href="<?= e(url('user/badges.php')) ?>">View all badges →</a>
  </section>

  <section class="card span-2">
    <h2>Security: change password</h2>
    <form method="post" action="" novalidate class="pw-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="change_password">
      <div class="field"><label for="current_password">Current password</label>
        <div class="input-group"><input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
          <button type="button" class="icon-btn" data-toggle-password="current_password" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
        <p class="field-error"><?= e($errors['current_password'] ?? '') ?></p></div>
      <div class="field"><label for="new_password">New password</label>
        <div class="input-group"><input id="new_password" name="new_password" type="password" autocomplete="new-password" required>
          <button type="button" class="icon-btn" data-toggle-password="new_password" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
        <p class="hint">At least 8 characters with uppercase, lowercase and a number.</p>
        <p class="field-error"><?= e($errors['new_password'] ?? '') ?></p></div>
      <div class="field"><label for="confirm_password">Confirm new password</label>
        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
        <p class="field-error"><?= e($errors['confirm_password'] ?? '') ?></p></div>
      <button class="btn btn-primary" type="submit">Change password</button>
    </form>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
