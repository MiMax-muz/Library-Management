<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
final class Logger {
    public static function failure(Throwable $e): string {
        $id = bin2hex(random_bytes(8));
        // Không ghi tham số, mật khẩu, DSN vào nhật ký.
        error_log('[' . $id . '] ' . get_class($e));
        return $id;
    }
}
