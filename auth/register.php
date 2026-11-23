<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    redirect('user/dashboard.php');
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name    = trim((string)($_POST['name'] ?? ''));
    $email   = normalize_email((string)($_POST['email'] ?? ''));
    $pw      = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $old     = ['name' => $name, 'email' => $email];

    if ($name === '' || mb_strlen($name) > 100) {
        $errors['name'] = 'Enter your full name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($problems = password_problems($pw)) {
        $errors['password'] = implode(' ', $problems);
    }
    if ($pw !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            // role is never taken from the form; the column default ('user') applies.
            db()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)')
                ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
            flash('success', 'Account created. You can log in now.');
            redirect('auth/login.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['email'] = 'An account with this email already exists. Try logging in.';
            } else {
                error_log('[register] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
                $errors['form'] = 'We could not create your account. Please try again.';
            }
        }
    }
}

$layout = 'auth';
$pageTitle = 'Create account';
$extraCss = ['auth.css'];
$extraJs = ['auth.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="auth-card">
  <h1>Welcome to CyberSafe</h1>
  <p class="muted">Build safer online habits, one challenge at a time.</p>
  <?php if (isset($errors['form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
  <form method="post" action="" novalidate data-validate="register">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Full name</label>
      <input id="name" name="name" type="text" autocomplete="name" maxlength="100" required placeholder="Enter full name" value="<?= e($old['name']) ?>">
      <p class="field-error" data-error-for="name"><?= e($errors['name'] ?? '') ?></p>
    </div>
    <div class="field">
      <label for="email">Email address</label>
      <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required placeholder="Enter email address" value="<?= e($old['email']) ?>">
      <p class="field-error" data-error-for="email"><?= e($errors['email'] ?? '') ?></p>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <div class="input-group">
        <input id="password" name="password" type="password" autocomplete="new-password" required placeholder="Enter password">
        <button type="button" class="icon-btn" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
      </div>
      <p class="hint">At least 8 characters with uppercase, lowercase and a number.</p>
      <p class="field-error" data-error-for="password"><?= e($errors['password'] ?? '') ?></p>
    </div>
    <div class="field">
      <label for="confirm_password">Confirm password</label>
      <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required placeholder="Enter confirm password">
      <p class="field-error" data-error-for="confirm_password"><?= e($errors['confirm_password'] ?? '') ?></p>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Create account</button>
  </form>
  <p class="auth-switch">Already have an account? <a href="<?= e(url('auth/login.php')) ?>">Log in</a></p>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
