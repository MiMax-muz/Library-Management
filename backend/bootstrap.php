<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
foreach (['ApiException', 'Response', 'Database', 'Logger', 'Router', 'Auth'] as $class) require_once __DIR__ . '/shared/' . $class . '.php';
return require __DIR__ . '/config.php';
