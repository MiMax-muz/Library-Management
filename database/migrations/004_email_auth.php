<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$c=require __DIR__.'/../../backend/bootstrap.php';$p=Database::connection($c['db']);
$p->exec('CREATE TABLE IF NOT EXISTS schema_migrations(version VARCHAR(80) PRIMARY KEY,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
if(Input::query($p,'SELECT version FROM schema_migrations WHERE version=?',['004_email_auth'])->fetch()){echo "SKIP: email auth\n";exit;}
foreach(['email_verified_at'=>'DATETIME NULL','email_verification_required'=>'BOOLEAN NOT NULL DEFAULT 0','auth_version'=>'INT UNSIGNED NOT NULL DEFAULT 0'] as $column=>$type){
 if(!Input::query($p,'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',['tai_khoan',$column])->fetch())$p->exec("ALTER TABLE tai_khoan ADD COLUMN $column $type");
}
$p->exec("CREATE TABLE IF NOT EXISTS account_email_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tai_khoan_id BIGINT UNSIGNED NOT NULL,
 purpose ENUM('verify','reset') NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 INDEX account_purpose(tai_khoan_id,purpose,id),
 FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$p->exec('CREATE TABLE IF NOT EXISTS auth_rate_limits (bucket CHAR(64) PRIMARY KEY, hits INT UNSIGNED NOT NULL, window_start DATETIME NOT NULL) ENGINE=InnoDB');
Input::query($p,'INSERT INTO schema_migrations(version) VALUES (?)',['004_email_auth']);
echo "PASS: email auth; existing accounts retain access\n";
