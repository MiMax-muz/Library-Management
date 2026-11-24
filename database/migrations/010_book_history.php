<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') exit;
$c=require __DIR__.'/../../backend/bootstrap.php';
$db=Database::connection($c['db']);
$db->exec("CREATE TABLE IF NOT EXISTS book_view (
 tai_khoan_id BIGINT UNSIGNED NOT NULL, sach_id BIGINT UNSIGNED NOT NULL, viewed_at DATETIME NOT NULL,
 PRIMARY KEY(tai_khoan_id,sach_id), FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id) ON DELETE CASCADE,
 FOREIGN KEY(sach_id) REFERENCES sach(sach_id) ON DELETE CASCADE
) ENGINE=InnoDB");
Input::query($db,'INSERT IGNORE INTO schema_migrations(version) VALUES (?)',['010_book_history']);
