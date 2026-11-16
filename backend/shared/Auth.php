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
    public static function login(int $accountId,int $version=0): void { session_regenerate_id(true); $_SESSION = ['tai_khoan_id' => $accountId, 'auth_version'=>$version, 'csrf' => bin2hex(random_bytes(32))]; }
    public static function logout(): void { $_SESSION = []; session_regenerate_id(true); }
    public static function user(PDO $pdo): ?array {
        if (!isset($_SESSION['tai_khoan_id'])) return null;
        $q = $pdo->prepare('SELECT tai_khoan_id, email, ho_ten, mssv, vai_tro, trang_thai, auth_version, email_verification_required, email_verified_at FROM tai_khoan WHERE tai_khoan_id = ?');
        $q->execute([$_SESSION['tai_khoan_id']]); $user = $q->fetch();
        if (!$user || $user['trang_thai'] !== 'active' || (int)$user['auth_version']!==(int)($_SESSION['auth_version']??0) || ($user['email_verification_required']&&!$user['email_verified_at'])) { self::logout(); throw new ApiException(401, 'SESSION_EXPIRED', 'Phiên không còn hiệu lực. Hãy đăng nhập lại.'); }
        return $user;
    }
    public static function requireRoles(PDO $pdo, array $roles = []): array {
        $user = self::user($pdo);
        if (!$user) throw new ApiException(401, 'UNAUTHENTICATED', 'Vui lòng đăng nhập.');
        if ($roles && !in_array($user['vai_tro'], $roles, true)) throw new ApiException(403, 'FORBIDDEN', 'Bạn không có quyền thao tác.');
        return $user;
    }
}
