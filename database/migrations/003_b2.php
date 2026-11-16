<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config=require __DIR__.'/../../backend/bootstrap.php';
$db=Database::connection($config['db']);
$db->exec('CREATE TABLE IF NOT EXISTS schema_migrations(version VARCHAR(80) PRIMARY KEY,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
$version='003_b2';
if(Input::query($db,'SELECT version FROM schema_migrations WHERE version=?',[$version])->fetch()){echo "SKIP: B2\n";exit;}
$sql=file_get_contents(__DIR__.'/003_b2.sql');
$db->exec($sql);
Input::query($db,'INSERT INTO schema_migrations(version) VALUES (?)',[$version]);
echo "PASS: B2 schema\n";
