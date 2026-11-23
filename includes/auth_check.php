<?php
// Include at the top of every logged-in user page (after bootstrap.php).
declare(strict_types=1);

if (empty($_SESSION['user_id'])) {
    flash('warning', 'Please log in to continue.');
    redirect('auth/login.php');
}
// Re-check the account on every request so deactivated users lose access immediately.
$stmt = db()->prepare('SELECT id, name, email, role, total_xp, is_active, created_at FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();
if (!$currentUser || !$currentUser['is_active']) {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('error', 'This account is not available.');
    redirect('auth/login.php');
}
