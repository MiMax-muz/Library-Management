<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path === '/api' || str_starts_with($path, '/api/')) { require __DIR__ . '/public/index.php'; return true; }
// Không phục vụ bản tham khảo, trang khung hoặc công cụ thử độc lập trong bản web.
if (preg_match('~^/(prototype)(/|$)~', $path)
    || (str_ends_with($path, '.html') && !in_array($path, ['/index.html'], true))
    || str_ends_with($path, '.test.mjs')) {
    http_response_code(404); echo 'Không tìm thấy trang.'; return true;
}
$root = realpath(__DIR__ . '/../frontend'); $file = realpath($root . $path);
if ($file !== false && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)) {
    $types = ['js' => 'application/javascript', 'css' => 'text/css', 'html' => 'text/html', 'svg' => 'image/svg+xml'];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (isset($types[$extension])) {
        header('Cache-Control: no-store');
        header('Content-Type: ' . $types[$extension] . '; charset=utf-8');
        readfile($file); return true;
    }
    return false;
}
header('Cache-Control: no-store');
if ($path === '/') { require $root . '/index.html'; return true; }
http_response_code(404); echo 'Không tìm thấy trang.'; return true;
