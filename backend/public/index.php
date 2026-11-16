<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
$config = require dirname(__DIR__) . '/bootstrap.php';
try {
    Auth::start($config['session_secure']);
    $method = $_SERVER['REQUEST_METHOD']; Auth::checkCsrf($method);
    $router = new Router(); (require dirname(__DIR__) . '/routes.php')($router, $config);
    $router->dispatch($method, parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
} catch (ApiException $e) { Response::error($e); }
catch (PDOException $e) {
    if ($e->getCode() === '23000') Response::error(new ApiException(409, 'DATA_CONFLICT', 'Dữ liệu trùng hoặc đang được tham chiếu.'));
    else Response::error(new ApiException(500, 'DATABASE_ERROR', 'Không thể lưu dữ liệu.', ['request_id'=>Logger::failure($e)]));
}
catch (Throwable $e) { Response::error(new ApiException(500, 'INTERNAL_ERROR', 'Có lỗi hệ thống.', ['request_id' => Logger::failure($e)])); }
