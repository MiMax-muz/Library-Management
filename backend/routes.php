<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
return static function (Router $router, array $config): void {
    $router->add('GET', '/api/health', static function () { Response::json(['status' => 'ok', 'baseline' => 'week1-foundation']); });
    $router->add('GET', '/api/health/db', static function () use ($config) {
        try {
            $pdo = Database::connection($config['db']);
            $pdo->query('SELECT 1')->fetchColumn();
            $pdo->query('SELECT tai_khoan_id FROM tai_khoan LIMIT 1');
            Response::json(['status' => 'ok']);
        } catch (PDOException $e) { Logger::failure($e); throw new ApiException(503, 'DATABASE_NOT_READY', 'Chưa kết nối được DB hoặc chưa nhập schema.'); }
    });
    $router->add('GET', '/api/session', static function () use ($config) {
        $user = isset($_SESSION['tai_khoan_id']) ? Auth::user(Database::connection($config['db'])) : null;
        Response::json(['user' => $user, 'csrf_token' => Auth::csrfToken()]);
    });
    foreach (['books', 'ebooks', 'readers', 'bookshelf', 'loans', 'fines', 'accounts', 'notifications', 'reports'] as $module) {
        $file = __DIR__ . '/' . $module . '/routes.php';
        if (!is_file($file)) continue;
        $register = require $file;
        if (is_callable($register)) $register($router, $config);
    }
};
