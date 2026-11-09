<?php
declare(strict_types=1);

require_once __DIR__ . '/NotificationService.php';

return static function (Router $router, array $config): void {
    $service = static fn() => new NotificationService(Database::connection($config['db']));
    $router->add('GET', '/api/notifications', static function () use ($service): void {
        $result = $service()->list($_GET);
        Response::json($result['items'], 200, $result['meta']);
    });
    $router->add('GET', '/api/notifications/unread-count', static fn() => Response::json($service()->unreadCount()));
    $router->add('PATCH', '/api/notifications/read-all', static fn() => Response::json($service()->markAllRead()));
    $router->add('PATCH', '/api/notifications/:id/read', static fn($params) => Response::json($service()->markRead($params['id'])));
};
