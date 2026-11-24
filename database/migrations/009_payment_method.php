<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
$config=require __DIR__.'/../../backend/bootstrap.php';
$db=Database::connection($config['db']);
$db->exec("ALTER TABLE fine_payment ADD COLUMN IF NOT EXISTS method ENUM('cash','transfer') NOT NULL DEFAULT 'cash'");
Input::query($db,'INSERT IGNORE INTO schema_migrations(version) VALUES (?)',['009_payment_method']);
