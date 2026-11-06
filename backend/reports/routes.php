<?php
declare(strict_types=1);

require_once __DIR__ . '/ReportService.php';
require_once __DIR__ . '/SettingsService.php';

return static function (Router $router, array $config): void {
    $pdo = static fn() => Database::connection($config['db']);
    $router->add('GET', '/api/reports/baseline', static fn() => Response::json((new ReportService($pdo()))->baseline()));
    $router->add('GET', '/api/settings', static fn() => Response::json((new SettingsService($pdo()))->current()));
};
