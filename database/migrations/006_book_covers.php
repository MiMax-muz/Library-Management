<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$c=require __DIR__.'/../../backend/bootstrap.php';$db=Database::connection($c['db']);
if(!Input::query($db,"SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sach' AND COLUMN_NAME='cover_path'")->fetch())$db->exec('ALTER TABLE sach ADD COLUMN cover_path VARCHAR(255) NULL');
Input::query($db,'INSERT IGNORE INTO schema_migrations(version) VALUES (?)',['006_book_covers']);
echo "PASS: book covers ready\n";
