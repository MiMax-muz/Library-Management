<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path === '/api' || str_starts_with($path, '/api/')) { require __DIR__ . '/public/index.php'; return true; }
$root = realpath(__DIR__ . '/../frontend'); $file = realpath($root . $path);
if ($file !== false && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)) return false;
if ($path === '/') { require $root . '/index.html'; return true; }
http_response_code(404); echo 'Không tìm thấy trang.'; return true;
