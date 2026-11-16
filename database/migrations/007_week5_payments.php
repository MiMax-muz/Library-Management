<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config = require __DIR__ . '/../../backend/bootstrap.php';
$pdo = Database::connection($config['db']);
if (!Input::query($pdo, 'SELECT version FROM schema_migrations WHERE version=?', ['005_b34'])->fetch()) {
    throw new RuntimeException('Run migrations 001–005 first.');
}
$pdo->exec(file_get_contents(__DIR__ . '/007_week5_payments.sql'));
Input::query($pdo, 'INSERT IGNORE INTO schema_migrations(version) VALUES (?)', ['007_week5_payments']);
echo "PASS: week 5 payment ledger created; existing records preserved\n";
