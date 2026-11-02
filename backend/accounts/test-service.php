<?php
declare(strict_types=1);
// Chạy: php -d extension=C:/php/ext/php_pdo_sqlite.dll backend/accounts/test-service.php
foreach (['ApiException','Auth'] as $class) require_once dirname(__DIR__) . '/shared/' . $class . '.php';
require_once __DIR__ . '/AccountService.php';

final class AccountTestPdo extends PDO {
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}

$pdo = new AccountTestPdo('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec("CREATE TABLE tai_khoan (tai_khoan_id INTEGER PRIMARY KEY AUTOINCREMENT,email TEXT NOT NULL UNIQUE COLLATE NOCASE,mssv TEXT UNIQUE COLLATE NOCASE,mat_khau_hash TEXT NOT NULL,vai_tro TEXT NOT NULL,trang_thai TEXT NOT NULL,ho_ten TEXT,dien_thoai TEXT)");
$pdo->exec("CREATE TABLE nhan_vien (nhan_vien_id INTEGER PRIMARY KEY AUTOINCREMENT,tai_khoan_id INTEGER NOT NULL UNIQUE,ma_nhan_vien TEXT NOT NULL UNIQUE COLLATE NOCASE,ho_ten TEXT NOT NULL,dien_thoai TEXT,chuc_vu TEXT,trang_thai TEXT NOT NULL)");
$pdo->exec("CREATE TABLE doc_gia (doc_gia_id INTEGER PRIMARY KEY AUTOINCREMENT,tai_khoan_id INTEGER NOT NULL UNIQUE)");
$q = $pdo->prepare("INSERT INTO tai_khoan(email,mssv,mat_khau_hash,vai_tro,trang_thai,ho_ten) VALUES ('admin@example.test',NULL,?,'admin','active','Quản lý')");
$q->execute([password_hash('admin-pass-123', PASSWORD_DEFAULT)]);
Auth::start(false);
$service = new AccountService($pdo);
$checks = 0;
function check(bool $condition, string $message): void { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function expectCode(callable $fn, string $code): void {
    try { $fn(); } catch (ApiException $e) { check($e->errorCode === $code, "Sai lỗi: {$e->errorCode} thay vì $code"); return; }
    throw new RuntimeException("Thiếu lỗi $code");
}

$student = $service->register(['email'=>'  NEW@Example.Test ','mssv'=>' sv002 ','ho_ten'=>'Sinh viên Thử','dien_thoai'=>'+84912345678','password'=>'student-pass-123']);
check($student['email'] === 'new@example.test' && $student['mssv'] === 'SV002', 'Chuẩn hóa email/MSSV');
check($student['vai_tro'] === 'student' && !$student['has_profile'], 'Đăng ký chỉ tạo tài khoản chờ hồ sơ');
check($student['dien_thoai'] === '0912345678' && !isset($student['mat_khau_hash']), 'Điện thoại và phản hồi an toàn');
expectCode(fn() => $service->register(['email'=>'other@example.test','mssv'=>'sv002','ho_ten'=>'Khác','password'=>'password-123']), 'DUPLICATE');
expectCode(fn() => $service->register(['email'=>'NEW@example.test','mssv'=>'SV003','ho_ten'=>'Khác','password'=>'password-123']), 'DUPLICATE');
expectCode(fn() => $service->register(['email'=>'x@example.test','mssv'=>'SV003','ho_ten'=>'Khác','password'=>'password-123','vai_tro'=>'admin']), 'INVALID_INPUT');
expectCode(fn() => $service->login(['identifier'=>'SV002','password'=>'wrong-pass']), 'INVALID_CREDENTIALS');
$login = $service->login(['identifier'=>'sv002','password'=>'student-pass-123']);
check($login['user']['tai_khoan_id'] === $student['tai_khoan_id'] && strlen($login['csrf_token']) === 64, 'Đăng nhập MSSV và phiên');
expectCode(fn() => $service->listAccounts(), 'FORBIDDEN');
expectCode(fn() => $service->changePassword(['current_password'=>'bad','new_password'=>'new-pass-123']), 'INVALID_PASSWORD');
$service->changePassword(['current_password'=>'student-pass-123','new_password'=>'new-pass-123']);
$service->logout();
expectCode(fn() => $service->login(['identifier'=>'new@example.test','password'=>'student-pass-123']), 'INVALID_CREDENTIALS');
$service->login(['identifier'=>'NEW@example.test','password'=>'new-pass-123']);
check(Auth::requireRoles($pdo, ['student'])['tai_khoan_id'] == $student['tai_khoan_id'], 'Mật khẩu mới và quyền');
$service->logout();

$service->login(['identifier'=>'admin@example.test','password'=>'admin-pass-123']);
$list = $service->listAccounts();
check($list['total'] === 2 && !isset($list['items'][0]['mat_khau_hash']), 'Danh sách quản lý không lộ hash');
$created = $service->createAccount(['email'=>'second@example.test','mssv'=>'sv003','ho_ten'=>'Sinh viên Hai','password'=>'password-123','vai_tro'=>'student']);
check($created['mssv'] === 'SV003', 'Quản lý tạo tài khoản');
$unlinked = $service->createAccount(['email'=>'linked@example.test','ho_ten'=>'Tài khoản Có sẵn','password'=>'linked-pass-123','vai_tro'=>'staff']);
$linked = $service->createEmployee(['tai_khoan_id'=>(string)$unlinked['tai_khoan_id'],'ma_nhan_vien'=>'NV099','ho_ten'=>'Nhân viên Liên kết','email'=>'linked@example.test','chuc_vu'=>'Nhân viên','vai_tro'=>'staff']);
check($linked['tai_khoan_id'] === $unlinked['tai_khoan_id'], 'Liên kết tài khoản có sẵn');
expectCode(fn() => $service->createEmployee(['tai_khoan_id'=>(string)$created['tai_khoan_id'],'ma_nhan_vien'=>'NV098','ho_ten'=>'Sinh viên Hai','email'=>'second@example.test','chuc_vu'=>'Nhân viên','vai_tro'=>'staff']), 'PROFILE_CONFLICT');
expectCode(fn() => $service->updateAccount('1', ['trang_thai'=>'locked']), 'SELF_LOCK');
expectCode(fn() => $service->updateAccount('1', ['vai_tro'=>'staff']), 'SELF_LOCK');
$service->updateAccount((string)$student['tai_khoan_id'], ['trang_thai'=>'locked']);
$service->logout();
expectCode(fn() => $service->login(['identifier'=>'SV002','password'=>'new-pass-123']), 'INVALID_CREDENTIALS');
$service->login(['identifier'=>'admin@example.test','password'=>'admin-pass-123']);

$employee = $service->createEmployee(['ma_nhan_vien'=>' nv100 ','ho_ten'=>'Nhân viên Mới','email'=>'staff@example.test','dien_thoai'=>'0911111111','chuc_vu'=>'Thủ thư','vai_tro'=>'librarian','trang_thai'=>'active','password'=>'staff-pass-123']);
check($employee['ma_nhan_vien'] === 'NV100' && $employee['account_status'] === 'active', 'Tạo nhân viên và tài khoản cùng lúc');
$before = (int)$pdo->query('SELECT COUNT(*) FROM tai_khoan')->fetchColumn();
expectCode(fn() => $service->createEmployee(['ma_nhan_vien'=>'NV100','ho_ten'=>'Nhân viên Trùng','email'=>'another@example.test','chuc_vu'=>'Thủ thư','vai_tro'=>'staff','password'=>'staff-pass-123']), 'DUPLICATE');
check((int)$pdo->query('SELECT COUNT(*) FROM tai_khoan')->fetchColumn() === $before, 'Lỗi tạo nhân viên hoàn tác tài khoản');
$stopped = $service->updateEmployee((string)$employee['nhan_vien_id'], ['trang_thai'=>'inactive']);
check($stopped['trang_thai'] === 'inactive' && $stopped['account_status'] === 'locked', 'Ngừng nhân viên khóa tài khoản');
expectCode(fn() => $service->login(['identifier'=>'staff@example.test','password'=>'staff-pass-123']), 'INVALID_CREDENTIALS');
$restarted = $service->updateEmployee((string)$employee['nhan_vien_id'], ['trang_thai'=>'active']);
check($restarted['account_status'] === 'active', 'Tái kích hoạt nhân viên mở tài khoản');
check(count($service->listEmployees()) === 2, 'Danh sách nhân viên');
$service->logout();
$service->login(['identifier'=>'staff@example.test','password'=>'staff-pass-123']);
$q = $pdo->prepare("UPDATE tai_khoan SET trang_thai='locked' WHERE tai_khoan_id=?"); $q->execute([$employee['tai_khoan_id']]);
expectCode(fn() => Auth::requireRoles($pdo), 'SESSION_EXPIRED');
check(Auth::user($pdo) === null, 'Tài khoản bị khóa mất phiên ngay');

echo "PASS: $checks account service checks\n";
