<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect(($_SESSION['role'] ?? 'user') === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$error = '';
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email    = normalize_email((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $oldEmail = $email;

    if ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } elseif (login_is_limited($email)) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, password, role, is_active FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Always run a hash check so response time does not reveal whether the email exists.
            $hash = $user['password'] ?? password_hash('placeholder', PASSWORD_DEFAULT);
            $ok = password_verify($password, $hash) && $user && (int)$user['is_active'] === 1;

            if ($ok) {
                clear_failed_logins($email);
                session_regenerate_id(true); // prevents session fixation
                unset($_SESSION['csrf']); // fresh CSRF token for the new session
                $_SESSION['user_id']       = (int)$user['id'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['last_activity'] = time();
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE users SET password = ? WHERE id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                }
                flash('success', 'Login successful.');
                redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
            }
            record_failed_login($email);
            $error = 'Invalid email or password.'; // generic on purpose
        } catch (PDOException $e) {
            error_log('[login] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
            $error = 'Something went wrong. Please try again.';
        }
    }
}

$layout = 'auth';
$pageTitle = 'Log in';
$extraCss = ['auth.css'];
$extraJs = ['auth.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="auth-card">
  <h1>Welcome to CyberSafe</h1>
  <p class="muted">Build safer online habits, one challenge at a time.</p>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="" novalidate data-validate="login">
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">Email address</label>
      <input id="email" name="email" type="email" autocomplete="email" required placeholder="Enter email address" value="<?= e($oldEmail) ?>">
      <p class="field-error" data-error-for="email"></p>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <div class="input-group">
        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter password">
        <button type="button" class="icon-btn" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
      </div>
      <p class="field-error" data-error-for="password"></p>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Log in</button>
  </form>
  <p class="auth-switch">New to CyberSafe? <a href="<?= e(url('auth/register.php')) ?>">Create account</a></p>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
