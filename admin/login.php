<?php
// Separate administrator sign-in. Only accounts with role = admin can pass.
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    redirect('admin/dashboard.php');
}

$error = '';
$oldEmail = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = normalize_email((string)($_POST['email'] ?? ''));
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
            $hash = $user['password'] ?? password_hash('placeholder', PASSWORD_DEFAULT);
            // Wrong password, unknown email, inactive account and non-admin all give the SAME message.
            $ok = password_verify($password, $hash) && $user && (int)$user['is_active'] === 1 && $user['role'] === 'admin';
            if ($ok) {
                clear_failed_logins($email);
                session_regenerate_id(true);
                unset($_SESSION['csrf']); // fresh CSRF token for the new session
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['last_activity'] = time();
                flash('success', 'Login successful.');
                redirect('admin/dashboard.php');
            }
            record_failed_login($email);
            $error = 'Invalid email or password.';
        } catch (PDOException $e) {
            error_log('[admin-login] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
            $error = 'Something went wrong. Please try again.';
        }
    }
}

$layout = 'auth';
$pageTitle = 'Administrator sign in';
$extraCss = ['auth.css'];
$extraJs = ['auth.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="auth-card">
  <h1>Administrator sign in</h1>
  <p class="muted">Authorised staff only.</p>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="" novalidate data-validate="login">
    <?= csrf_field() ?>
    <div class="field"><label for="email">Admin email</label>
      <input id="email" name="email" type="email" autocomplete="username" required placeholder="Enter admin email" value="<?= e($oldEmail) ?>">
      <p class="field-error" data-error-for="email"></p></div>
    <div class="field"><label for="password">Password</label>
      <div class="input-group"><input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter password">
        <button type="button" class="icon-btn" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
      <p class="field-error" data-error-for="password"></p></div>
    <button type="submit" class="btn btn-primary btn-block">Log in</button>
  </form>
  <p class="auth-switch"><a href="<?= e(url('auth/login.php')) ?>">Learner login</a></p>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
