<?php
// Include at the top of EVERY admin page: session + login + server-side admin role check.
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/admin_check.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/admin_queries.php';
$isAdminPage = true;
