<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
return ['db' => ['host' => getenv('DB_HOST') ?: '127.0.0.1', 'port' => getenv('DB_PORT') ?: '3306', 'name' => getenv('DB_NAME') ?: 'ittv_library', 'user' => getenv('DB_USER') ?: 'root', 'password' => getenv('DB_PASSWORD') ?: ''], 'session_secure' => getenv('SESSION_SECURE') === '1'];
