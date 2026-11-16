<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$c=require __DIR__.'/../../backend/bootstrap.php';$p=Database::connection($c['db']);
if(!Input::query($p,'SELECT version FROM schema_migrations WHERE version=?',['004_email_auth'])->fetch())throw new RuntimeException('Run migrations 001–004 first.');
$p->exec(file_get_contents(__DIR__.'/005_b34.sql'));
Input::query($p,'INSERT IGNORE INTO schema_migrations(version) VALUES (?)',['005_b34']);
echo "PASS: week 3–4 schema, existing data preserved\n";
