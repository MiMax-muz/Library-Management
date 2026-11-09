<?php
declare(strict_types=1);

// Chạy: php -d extension=C:/php/ext/php_pdo_sqlite.dll backend/notifications/test-service.php
foreach (['ApiException', 'Auth', 'Router'] as $class) require_once dirname(__DIR__) . '/shared/' . $class . '.php';
require_once __DIR__ . '/NotificationService.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec('CREATE TABLE tai_khoan (tai_khoan_id INTEGER PRIMARY KEY, email TEXT, mssv TEXT, vai_tro TEXT, trang_thai TEXT)');
$pdo->exec('CREATE TABLE thong_bao (thong_bao_id INTEGER PRIMARY KEY AUTOINCREMENT, tai_khoan_id INTEGER NOT NULL REFERENCES tai_khoan(tai_khoan_id), loai TEXT NOT NULL, ma_nguon TEXT NOT NULL, ngay_han TEXT NULL, ma_su_kien TEXT NOT NULL UNIQUE, tieu_de TEXT NOT NULL, noi_dung TEXT NOT NULL, tham_chieu TEXT NULL, created_at TEXT NOT NULL, read_at TEXT NULL)');
$pdo->exec("INSERT INTO tai_khoan VALUES (1, 'sv1@example.test', 'SV001', 'student', 'active'), (2, 'sv2@example.test', 'SV002', 'student', 'active'), (3, 'admin@example.test', NULL, 'admin', 'active')");

$checks = 0;
function verify(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function expectApi(callable $call, int $status, string $code): void {
    try { $call(); } catch (ApiException $error) {
        verify($error->status === $status && $error->errorCode === $code, "Sai lỗi {$error->status}/{$error->errorCode}; cần $status/$code");
        return;
    }
    throw new RuntimeException("Thiếu lỗi $status/$code");
}

Auth::start(false);
$service = new NotificationService($pdo);
$borrow = [
    'recipient_id' => 1, 'type' => 'borrow-approved', 'source_id' => 'YC-1042',
    'title' => 'Yêu cầu mượn đã được duyệt', 'message' => 'Vui lòng đến quầy nhận sách.',
    'reference' => 'Yêu cầu YC-1042',
];
$first = $service->createFromEvent($borrow);
verify($first['id'] > 0 && $first['type'] === 'borrow-approved' && str_ends_with($first['createdAt'], 'Z'), 'Tạo thông báo và giờ UTC không đúng');
verify(!isset($first['recipientId']) && !isset($first['sourceId']), 'Phản hồi không được lộ dữ liệu nội bộ');
$repeat = $service->createFromEvent($borrow);
verify($repeat['id'] === $first['id'] && (int)$pdo->query('SELECT COUNT(*) FROM thong_bao')->fetchColumn() === 1, 'Sự kiện lặp phải trả bản ghi cũ');
expectApi(fn() => $service->createFromEvent([...$borrow, 'recipient_id' => 2]), 409, 'EVENT_CONFLICT');

expectApi(fn() => $service->createFromEvent([...$borrow, 'type' => 'unknown']), 422, 'INVALID_EVENT');
expectApi(fn() => $service->createFromEvent([...$borrow, 'source_id' => 'YC 1042']), 422, 'INVALID_EVENT');
expectApi(fn() => $service->createFromEvent([...$borrow, 'title' => '']), 422, 'INVALID_INPUT');
expectApi(fn() => $service->createFromEvent([...$borrow, 'type' => 'due-reminder']), 422, 'INVALID_INPUT');
expectApi(fn() => $service->createFromEvent([...$borrow, 'type' => 'due-reminder', 'due_date' => '2026-02-30']), 422, 'INVALID_INPUT');
$reminder = $service->createFromEvent([...$borrow, 'type' => 'due-reminder', 'source_id' => 'PM-0086', 'due_date' => '2026-11-10', 'reference' => 'Phiếu PM-0086']);
$nextReminder = $service->createFromEvent([...$borrow, 'type' => 'due-reminder', 'source_id' => 'PM-0086', 'due_date' => '2026-11-17', 'reference' => 'Phiếu PM-0086']);
verify($reminder['id'] !== $nextReminder['id'], 'Hạn mới phải có khóa sự kiện khác');
$other = $service->createFromEvent([...$borrow, 'recipient_id' => 2, 'source_id' => 'YC-1050', 'reference' => 'Yêu cầu YC-1050']);

$pdo->beginTransaction();
$service->createFromEvent([...$borrow, 'source_id' => 'YC-ROLLBACK']);
$pdo->rollBack();
verify((int)$pdo->query("SELECT COUNT(*) FROM thong_bao WHERE ma_nguon = 'YC-ROLLBACK'")->fetchColumn() === 0, 'Service không được tự commit giao dịch nguồn');

$_SESSION = [];
expectApi(fn() => $service->list(), 401, 'UNAUTHENTICATED');
expectApi(fn() => $service->unreadCount(), 401, 'UNAUTHENTICATED');
expectApi(fn() => $service->markRead((string)$first['id']), 401, 'UNAUTHENTICATED');
$_SESSION['tai_khoan_id'] = 1;

$page = $service->list();
verify(count($page['items']) === 3 && $page['meta']['total'] === 3 && $page['meta']['unread_total'] === 3, 'Danh sách và tổng chưa đọc của tài khoản 1 không đúng');
verify($service->unreadCount()['unread'] === 3, 'API đếm chưa đọc không đúng');
verify($service->list(['status' => 'read'])['meta']['total'] === 0, 'Bộ lọc đã đọc ban đầu không đúng');
verify($service->list(['type' => 'borrow'])['meta']['total'] === 1 && $service->list(['type' => 'due'])['meta']['total'] === 2, 'Bộ lọc loại sự kiện không đúng');
verify($service->list(['search' => 'YC-1042'])['meta']['total'] === 1, 'Tìm theo mã tham chiếu không đúng');
verify($service->list(['search' => '%'])['meta']['total'] === 0, 'Ký tự wildcard phải được tìm như văn bản thường');
expectApi(fn() => $service->list(['status' => 'bad']), 422, 'INVALID_STATUS');
expectApi(fn() => $service->list(['type' => 'bad']), 422, 'INVALID_TYPE');
expectApi(fn() => $service->list(['search' => str_repeat('a', 101)]), 422, 'INVALID_SEARCH');
expectApi(fn() => $service->list(['page' => '0']), 422, 'INVALID_INPUT');
expectApi(fn() => $service->markRead('abc'), 404, 'NOT_FOUND');
expectApi(fn() => $service->markRead((string)$other['id']), 404, 'NOT_FOUND');

$read = $service->markRead((string)$first['id']);
verify($read['readAt'] !== null && str_ends_with($read['readAt'], 'Z'), 'Đánh dấu đã đọc phải ghi UTC');
verify($service->markRead((string)$first['id'])['readAt'] === $read['readAt'], 'Thao tác đọc lại không được đổi thời điểm đọc');
verify($service->list(['status' => 'read'])['meta']['total'] === 1 && $service->list(['status' => 'unread'])['meta']['total'] === 2, 'Bộ lọc đọc/chưa đọc không đúng');
$all = $service->markAllRead();
verify($all['updated'] === 2 && $all['unread'] === 0, 'Đánh dấu tất cả của tài khoản 1 không đúng');
verify($service->markAllRead()['updated'] === 0, 'Đánh dấu tất cả lặp lại phải không cập nhật');

$_SESSION['tai_khoan_id'] = 2;
verify($service->list()['meta']['total'] === 1 && $service->unreadCount()['unread'] === 1, 'Thông báo tài khoản khác phải giữ nguyên');
expectApi(fn() => $service->markRead((string)$first['id']), 404, 'NOT_FOUND');
$_SESSION['tai_khoan_id'] = 1;
for ($i = 1; $i <= 21; $i++) $service->createFromEvent([...$borrow, 'source_id' => 'YC-PAGE-' . $i]);
verify(count($service->list(['page' => '1'])['items']) === 20 && $service->list(['page' => '2'])['meta']['total'] === 24 && count($service->list(['page' => '2'])['items']) === 4, 'Phân trang không đúng');

$router = new Router();
(require __DIR__ . '/routes.php')($router, ['db' => []]);
expectApi(fn() => $router->dispatch('POST', '/api/notifications'), 405, 'METHOD_NOT_ALLOWED');
expectApi(fn() => $router->dispatch('POST', '/api/notifications/1'), 404, 'NOT_FOUND');

$pdo->exec("UPDATE tai_khoan SET trang_thai = 'locked' WHERE tai_khoan_id = 1");
expectApi(fn() => $service->list(), 401, 'SESSION_EXPIRED');
echo "PASS: $checks notification service checks\n";
