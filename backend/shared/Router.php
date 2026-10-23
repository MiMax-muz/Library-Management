<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
final class Router {
    private array $routes = [];
    public function add(string $method, string $path, callable $handler): void {
        $segments = explode('/', $path);
        $names = [];
        foreach ($segments as &$segment) {
            if (preg_match('/^:([a-zA-Z_][a-zA-Z0-9_]*)$/', $segment, $m)) { $names[] = $m[1]; $segment = '([^/]+)'; }
            else $segment = preg_quote($segment, '~');
        }
        unset($segment);
        $this->routes[] = [strtoupper($method), '~^' . implode('/', $segments) . '$~D', $names, $handler];
    }
    public function dispatch(string $method, string $path): void {
        $allowed = [];
        foreach ($this->routes as [$verb, $pattern, $names, $handler]) {
            if (!preg_match($pattern, $path, $matches)) continue;
            $allowed[] = $verb;
            if ($verb !== $method) continue;
            array_shift($matches);
            $handler(array_combine($names, array_map('rawurldecode', $matches)));
            return;
        }
        if ($allowed) { header('Allow: ' . implode(', ', array_unique($allowed))); throw new ApiException(405, 'METHOD_NOT_ALLOWED', 'Phương thức không được hỗ trợ.'); }
        throw new ApiException(404, 'NOT_FOUND', 'Không tìm thấy API.');
    }
}
