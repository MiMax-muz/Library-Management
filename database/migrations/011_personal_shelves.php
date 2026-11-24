<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$c=require __DIR__.'/../../backend/bootstrap.php';
$db=Database::connection($c['db']);
$db->exec("CREATE TABLE IF NOT EXISTS shelf_owner(account_id BIGINT UNSIGNED PRIMARY KEY REFERENCES tai_khoan(tai_khoan_id) ON DELETE CASCADE) ENGINE=InnoDB");
$db->exec("CREATE TABLE IF NOT EXISTS personal_shelf(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,account_id BIGINT UNSIGNED NOT NULL,name VARCHAR(100) NOT NULL,UNIQUE(account_id,name),FOREIGN KEY(account_id) REFERENCES tai_khoan(tai_khoan_id) ON DELETE CASCADE) ENGINE=InnoDB");
$db->exec("CREATE TABLE IF NOT EXISTS personal_shelf_book(shelf_id BIGINT UNSIGNED NOT NULL,sach_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(shelf_id,sach_id),FOREIGN KEY(shelf_id) REFERENCES personal_shelf(id) ON DELETE CASCADE,FOREIGN KEY(sach_id) REFERENCES sach(sach_id) ON DELETE CASCADE) ENGINE=InnoDB");
Input::query($db,'INSERT IGNORE INTO schema_migrations(version) VALUES (?)',['011_personal_shelves']);
