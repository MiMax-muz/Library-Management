<?php
declare(strict_types=1);

require_once __DIR__ . '/AccountService.php';

return static function (Router $router, array $config): void {
    $service = static fn() => new AccountService(Database::connection($config['db']));
    $router->add('POST', '/api/accounts/register', static fn() => Response::json($service()->register(AccountService::body()), 201));
    $router->add('POST', '/api/accounts/login', static fn() => Response::json($service()->login(AccountService::body())));
    $router->add('POST', '/api/accounts/logout', static function () use ($service): void { $service()->logout(); Response::json(['ok' => true]); });
    $router->add('PATCH', '/api/accounts/me/password', static fn() => Response::json($service()->changePassword(AccountService::body())));
    $router->add('GET', '/api/accounts', static function () use ($service): void {
        $page = $service()->listAccounts();
        Response::json($page['items'], 200, ['page' => $page['page'], 'per_page' => $page['per_page'], 'total' => $page['total']]);
    });
    $router->add('POST', '/api/accounts', static fn() => Response::json($service()->createAccount(AccountService::body()), 201));
    $router->add('PATCH', '/api/accounts/:id', static fn($p) => Response::json($service()->updateAccount($p['id'], AccountService::body())));
    $router->add('GET', '/api/employees', static fn() => Response::json($service()->listEmployees()));
    $router->add('POST', '/api/employees', static fn() => Response::json($service()->createEmployee(AccountService::body()), 201));
    $router->add('PATCH', '/api/employees/:id', static fn($p) => Response::json($service()->updateEmployee($p['id'], AccountService::body())));
};
