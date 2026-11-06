<?php
declare(strict_types=1);
// Chạy: php -d extension=C:/php/ext/php_pdo_sqlite.dll backend/reports/test-service.php
foreach (['ApiException', 'Auth', 'Router'] as $class) require_once dirname(__DIR__) . '/shared/' . $class . '.php';
require_once __DIR__ . '/ReportService.php';
require_once __DIR__ . '/SettingsService.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('CREATE TABLE tai_khoan (tai_khoan_id INTEGER PRIMARY KEY, email TEXT, mssv TEXT, mat_khau_hash TEXT, vai_tro TEXT, trang_thai TEXT)');
$pdo->exec('CREATE TABLE doc_gia (doc_gia_id INTEGER PRIMARY KEY, tai_khoan_id INTEGER, ho_ten TEXT)');
$pdo->exec('CREATE TABLE the_doc_gia (doc_gia_id INTEGER UNIQUE, ma_the TEXT, ngay_het_han TEXT, trang_thai TEXT)');
$pdo->exec('CREATE TABLE the_loai (the_loai_id INTEGER PRIMARY KEY, ten_the_loai TEXT)');
$pdo->exec('CREATE TABLE sach (sach_id INTEGER PRIMARY KEY, ma_sach TEXT, ten_sach TEXT, the_loai_id INTEGER, dang_luu_hanh INTEGER)');
$pdo->exec('CREATE TABLE ban_sach (ban_sach_id INTEGER PRIMARY KEY, sach_id INTEGER, tinh_trang TEXT, dang_luu_hanh INTEGER)');
$pdo->exec('CREATE TABLE cau_hinh (khoa TEXT PRIMARY KEY, gia_tri TEXT)');
$pdo->exec("INSERT INTO tai_khoan VALUES (1,'admin@example.test',NULL,'secret-hash','admin','active'),(2,'student@example.test','SV001','secret-hash','student','active'),(3,'reader@example.test','SV002','secret-hash','student','active'),(4,'orphan@example.test','SV003','secret-hash','student','active')");
$pdo->exec("INSERT INTO doc_gia VALUES (1,2,'Độc giả có thẻ'),(2,3,'Độc giả chưa có thẻ')");
$pdo->exec("INSERT INTO the_doc_gia VALUES (1,'THE001','2027-10-01','active')");
$pdo->exec("INSERT INTO the_loai VALUES (1,'CNTT'),(2,'Văn học')");
$pdo->exec("INSERT INTO sach VALUES (1,'S001','Sách đang lưu hành',1,1),(2,'S002','Sách ngừng lưu hành',2,0)");
$pdo->exec("INSERT INTO ban_sach VALUES (1,1,'available',1),(2,1,'damaged',1),(3,2,'available',1),(4,2,'lost',1)");
$settings = [
    'borrow_per_request' => '3', 'borrow_total' => '7', 'borrow_days' => '14',
    'renewal_limit' => '2', 'renewal_days' => '7', 'renewal_overdue_days' => '1',
    'ebook_per_request' => '3', 'ebook_days' => '14', 'notice_days_before_due' => '2',
    'notice_hour_local' => '09:00', 'overdue_daily_vnd' => '2000',
    'card_duration' => '12_months', 'business_timezone' => 'Asia/Ho_Chi_Minh',
    'damage_surcharge' => '0',
];
$insert = $pdo->prepare('INSERT INTO cau_hinh(khoa, gia_tri) VALUES (?, ?)');
foreach ($settings as $key => $value) $insert->execute([$key, $value]);

Auth::start(false);
$reports = new ReportService($pdo);
$policies = new SettingsService($pdo);
$checks = 0;
function verify(bool $condition, string $message): void { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function expectApi(callable $call, int $status, string $code): void {
    try { $call(); } catch (ApiException $e) {
        verify($e->status === $status && $e->errorCode === $code, "Sai lỗi {$e->status}/{$e->errorCode}; cần $status/$code");
        return;
    }
    throw new RuntimeException("Thiếu lỗi $status/$code");
}

$_SESSION = [];
expectApi(fn() => $reports->baseline(), 401, 'UNAUTHENTICATED');
expectApi(fn() => $policies->current(), 401, 'UNAUTHENTICATED');
$_SESSION['tai_khoan_id'] = 2;
expectApi(fn() => $reports->baseline(), 403, 'FORBIDDEN');
expectApi(fn() => $policies->current(), 403, 'FORBIDDEN');
$_SESSION['tai_khoan_id'] = 1;

$data = $reports->baseline();
verify(count($data['books']) === 2 && count($data['books'][0]['copies']) === 2, 'Đầu sách/bản sách không đúng');
verify($data['books'][0]['dang_luu_hanh'] === true && $data['books'][1]['dang_luu_hanh'] === false, 'Cờ lưu hành phải là boolean');
verify($data['books'][1]['copies'][0]['tinh_trang'] === 'available', 'Bản available ngừng lưu hành vẫn phải được trả đúng trạng thái nguồn');
verify(count($data['readers']) === 2 && $data['readers'][1]['ma_the'] === null && $data['readers'][1]['trang_thai'] === null, 'Độc giả chưa có thẻ phải xuất hiện');
verify(count($data['accounts']) === 4 && $data['accounts'][3]['email'] === 'orphan@example.test', 'Tài khoản chưa có hồ sơ vẫn phải xuất hiện');
verify(!array_key_exists('mat_khau_hash', $data['accounts'][0]), 'Không trả hash mật khẩu');
verify(!$pdo->inTransaction(), 'Báo cáo phải đóng transaction đọc');

$current = $policies->current();
verify(count($current) === count($settings) && $current['borrow_total'] === 7 && $current['damage_surcharge'] === 0, 'Chính sách số phải đọc từ DB và trả dạng integer');
verify($current['notice_hour_local'] === '09:00', 'Giờ nhắc hạn không đúng');
$pdo->exec("DELETE FROM cau_hinh WHERE khoa = 'notice_hour_local'");
expectApi(fn() => $policies->current(), 503, 'SETTINGS_NOT_READY');
$insert->execute(['notice_hour_local', '25:00']);
expectApi(fn() => $policies->current(), 503, 'SETTINGS_NOT_READY');
$pdo->exec("UPDATE cau_hinh SET gia_tri = '09:00' WHERE khoa = 'notice_hour_local'");
verify($policies->current()['notice_hour_local'] === '09:00', 'Cấu hình hợp lệ phải đọc lại được');

$router = new Router();
(require __DIR__ . '/routes.php')($router, ['db' => []]);
expectApi(fn() => $router->dispatch('PATCH', '/api/settings'), 405, 'METHOD_NOT_ALLOWED');

$pdo->exec("UPDATE tai_khoan SET trang_thai = 'locked' WHERE tai_khoan_id = 1");
expectApi(fn() => $reports->baseline(), 401, 'SESSION_EXPIRED');
expectApi(fn() => $policies->current(), 401, 'UNAUTHENTICATED');
echo "PASS: $checks report/settings service checks\n";
