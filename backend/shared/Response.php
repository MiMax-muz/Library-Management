<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
final class Response {
    public static function json(mixed $data, int $status = 200, ?array $meta = null): void {
        $body = ['data' => $data, 'error' => null];
        if ($meta !== null) $body['meta'] = $meta;
        self::send($body, $status);
    }
    public static function error(ApiException $e): void {
        self::send(['data' => null, 'error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'details' => (object)$e->details]], $e->status);
    }
    private static function send(array $body, int $status): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        if ($status !== 204) echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
