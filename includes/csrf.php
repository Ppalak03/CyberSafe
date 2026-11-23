<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Accepts the token from a POST field or an X-CSRF-Token header (fetch requests). */
function csrf_validate(): bool
{
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['csrf'] ?? '', $sent);
}

function csrf_require(): void
{
    if (!csrf_validate()) {
        http_response_code(419);
        exit('Invalid or expired form. Please go back, refresh, and try again.');
    }
}
