<?php
// Include after auth_check.php on every admin page and admin API: server-side role check.
declare(strict_types=1);

if (($currentUser['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}
