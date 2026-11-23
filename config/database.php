<?php
// Single, shared PDO connection. Edit credentials for your machine (XAMPP default: root / empty).
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'your_local_db';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        // Log details privately; show users a generic message only.
        error_log('[CyberSafe DB] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
        http_response_code(500);
        exit('CyberSafe is temporarily unavailable. Please check the database settings and try again.');
    }
    return $pdo;
}
