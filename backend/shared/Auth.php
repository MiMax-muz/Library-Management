<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
final class Auth {
    public static function start(bool $secure): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1');
        session_name('ittv_session');
        session_set_cookie_params(['httponly' => true, 'secure' => $secure, 'samesite' => 'Lax', 'path' => '/']);
        session_start();
    }
    public static function csrfToken(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
    public static function checkCsrf(string $method): void {
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) return;
        if (!hash_equals(self::csrfToken(), $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) throw new ApiException(403, 'CSRF_INVALID', 'Phiên thao tác không hợp lệ. Hãy tải lại trang.');
    }
    // AccountService của TV4 chỉ gọi sau khi password_verify thành công.
    public static function login(int $accountId): void { session_regenerate_id(true); $_SESSION = ['tai_khoan_id' => $accountId, 'csrf' => bin2hex(random_bytes(32))]; }
    public static function logout(): void { $_SESSION = []; session_regenerate_id(true); }
    public static function user(PDO $pdo): ?array {
        if (!isset($_SESSION['tai_khoan_id'])) return null;
        $q = $pdo->prepare('SELECT tai_khoan_id, email, mssv, vai_tro, trang_thai FROM tai_khoan WHERE tai_khoan_id = ?');
        $q->execute([$_SESSION['tai_khoan_id']]); $user = $q->fetch();
        if (!$user || $user['trang_thai'] !== 'active') { self::logout(); throw new ApiException(401, 'SESSION_EXPIRED', 'Tài khoản không còn hiệu lực.'); }
        return $user;
    }
    public static function requireRoles(PDO $pdo, array $roles = []): array {
        $user = self::user($pdo);
        if (!$user) throw new ApiException(401, 'UNAUTHENTICATED', 'Vui lòng đăng nhập.');
        if ($roles && !in_array($user['vai_tro'], $roles, true)) throw new ApiException(403, 'FORBIDDEN', 'Bạn không có quyền thao tác.');
        return $user;
    }
}
