<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
// Chỉ chạy với DB thử đã nhập schema/seed. Mọi thay đổi được rollback.
$config = require dirname(__DIR__) . '/bootstrap.php';
if (!str_ends_with($config['db']['name'], '_test')) throw new RuntimeException('DB_NAME phải kết thúc bằng _test.');
Auth::start(false);
$pdo = Database::connection($config['db']);
$checks = 0;
function check(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function expectError(callable $fn, string $code): void {
    try { $fn(); } catch (ApiException $e) { check($e->errorCode === $code, 'Sai mã lỗi: ' . $e->errorCode); return; }
    throw new RuntimeException('Không phát sinh lỗi ' . $code);
}
function rejects(PDO $pdo, string $sql): void {
    try { $pdo->exec($sql); } catch (PDOException $e) { check(true, 'Constraint'); return; }
    throw new RuntimeException('SQL không bị từ chối: ' . $sql);
}
$router = new Router(); $captured = null;
$router->add('GET', '/api/books/:sach_id', function ($params) use (&$captured) { $captured = $params; });
$router->dispatch('GET', '/api/books/42'); check($captured['sach_id'] === '42', 'Route param');
expectError(fn() => $router->dispatch('GET', '/missing'), 'NOT_FOUND');
expectError(fn() => $router->dispatch('POST', '/api/books/42'), 'METHOD_NOT_ALLOWED');
expectError(fn() => Auth::checkCsrf('POST'), 'CSRF_INVALID');
$_SERVER['HTTP_X_CSRF_TOKEN'] = Auth::csrfToken(); Auth::checkCsrf('POST'); check(true, 'CSRF hợp lệ');
expectError(fn() => Auth::requireRoles($pdo), 'UNAUTHENTICATED');
$pdo->beginTransaction();
try {
    $pdo->exec("UPDATE tai_khoan SET trang_thai = 'active' WHERE tai_khoan_id = 1");
    $before = session_id(); Auth::login(1); check(session_id() !== $before, 'Không xoay session id');
    check(Auth::requireRoles($pdo, ['student'])['tai_khoan_id'] == 1, 'Quyền student');
    expectError(fn() => Auth::requireRoles($pdo, ['admin']), 'FORBIDDEN');
    $pdo->exec("UPDATE tai_khoan SET trang_thai = 'locked' WHERE tai_khoan_id = 1");
    expectError(fn() => Auth::requireRoles($pdo), 'SESSION_EXPIRED');
    check(Auth::user($pdo) === null, 'Phiên khóa phải bị xóa');
    rejects($pdo, "INSERT INTO tai_khoan(email,mssv,mat_khau_hash,vai_tro) VALUES ('student@example.test','OTHER','x','student')");
    rejects($pdo, "INSERT INTO tai_khoan(email,mssv,mat_khau_hash,vai_tro) VALUES ('other@example.test','SV001','x','student')");
    rejects($pdo, "INSERT INTO ban_sach(ma_ban_sach,sach_id,the_loai_id,vi_tri_id) VALUES ('S001-01',1,1,1)");
    rejects($pdo, "INSERT INTO ban_sach(ma_ban_sach,sach_id,the_loai_id,vi_tri_id) VALUES ('WRONG-LOCATION',1,1,2)");
    rejects($pdo, "UPDATE the_doc_gia SET ngay_het_han = '2020-01-01' WHERE doc_gia_id = 1");
    rejects($pdo, "UPDATE ban_sach SET tinh_trang = 'borrowed', dang_luu_hanh = 0 WHERE ban_sach_id = 1");
    check((int)$pdo->query("SELECT COUNT(*) FROM ban_sach WHERE tinh_trang = 'available' AND dang_luu_hanh = 1")->fetchColumn() === 2, 'Tồn khả dụng');
    check($pdo->query("SELECT gia_tri FROM cau_hinh WHERE khoa = 'damage_surcharge'")->fetchColumn() === null, 'Không tự chốt phụ phí');
} finally { $pdo->rollBack(); Auth::logout(); }
echo "PASS: $checks checks\n";
