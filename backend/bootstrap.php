<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Ho_Chi_Minh');
// TV1: nền dùng chung tuần 1.
foreach (['ApiException', 'Response', 'Database', 'Logger', 'Router', 'Auth'] as $class) require_once __DIR__ . '/shared/' . $class . '.php';
require_once __DIR__ . '/shared/Input.php';
return require __DIR__ . '/config.php';
