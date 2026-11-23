<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// Logout changes state, so it is POST + CSRF only (a plain link cannot log you out).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('user/dashboard.php');
}
csrf_require();

destroy_session_and_cookie();
session_start();
session_regenerate_id(true);
flash('success', 'You have been logged out.');
redirect('index.php');
