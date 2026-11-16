<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config = require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../notifications/NotificationService.php';
$pdo = Database::connection($config['db']);
$notifications = new NotificationService($pdo);
$today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
$targetDate = (new DateTimeImmutable('today', new DateTimeZone('Asia/Ho_Chi_Minh')))->modify('+2 days')->format('Y-m-d');
$processed = 0;

$loans = Input::query($pdo,
    "SELECT i.loan_item_id,i.due_date,d.tai_khoan_id,s.ten_sach,b.ma_ban_sach,r.request_id
     FROM loan_item i JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id)
     JOIN doc_gia d USING(doc_gia_id) JOIN ban_sach b USING(ban_sach_id) JOIN sach s USING(sach_id)
     WHERE i.status='borrowed' AND i.due_date=? AND d.tai_khoan_id IS NOT NULL",
    [$targetDate]
)->fetchAll();
foreach ($loans as $loan) {
    $notifications->createFromEvent([
        'recipient_id'=>(int)$loan['tai_khoan_id'], 'type'=>'due-reminder',
        'source_id'=>'loan-item:'.$loan['loan_item_id'], 'due_date'=>$loan['due_date'],
        'title'=>'Sách sắp đến hạn trả',
        'message'=>'Sách “'.$loan['ten_sach'].'” ('.$loan['ma_ban_sach'].') cần được trả trong 2 ngày tới.',
        'reference'=>'Phiếu #'.$loan['request_id'],
    ]);
    $processed++;
}

$overdue = Input::query($pdo,
    "SELECT i.loan_item_id,i.due_date,d.tai_khoan_id,s.ten_sach,b.ma_ban_sach,r.request_id
     FROM loan_item i JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id)
     JOIN doc_gia d USING(doc_gia_id) JOIN ban_sach b USING(ban_sach_id) JOIN sach s USING(sach_id)
     WHERE i.status='borrowed' AND i.due_date<? AND d.tai_khoan_id IS NOT NULL",
    [$today]
)->fetchAll();
foreach ($overdue as $loan) {
    $notifications->createFromEvent([
        'recipient_id'=>(int)$loan['tai_khoan_id'], 'type'=>'loan-overdue',
        'source_id'=>'loan-item:'.$loan['loan_item_id'], 'due_date'=>$loan['due_date'],
        'title'=>'Sách đã quá hạn trả',
        'message'=>'Sách “'.$loan['ten_sach'].'” ('.$loan['ma_ban_sach'].') đã quá hạn. Vui lòng liên hệ thư viện để hoàn trả.',
        'reference'=>'Phiếu #'.$loan['request_id'],
    ]);
    $processed++;
}

$localStart = new DateTimeImmutable($targetDate.' 00:00:00', new DateTimeZone('Asia/Ho_Chi_Minh'));
$utcStart = $localStart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
$utcEnd = $localStart->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
$ebooks = Input::query($pdo,
    'SELECT a.ebook_id,a.tai_khoan_id,a.expires_at,a.granted_at,s.ten_sach FROM ebook_access a JOIN ebook e USING(ebook_id) JOIN sach s USING(sach_id) WHERE a.revoked_at IS NULL AND a.expires_at>=? AND a.expires_at<?',
    [$utcStart, $utcEnd]
)->fetchAll();
foreach ($ebooks as $ebook) {
    $notifications->createFromEvent([
        'recipient_id'=>(int)$ebook['tai_khoan_id'], 'type'=>'due-reminder',
        'source_id'=>'ebook-access:'.$ebook['ebook_id'].':'.$ebook['tai_khoan_id'].':'.preg_replace('/\\D/','',$ebook['granted_at']),
        'due_date'=>$targetDate, 'title'=>'Quyền đọc eBook sắp hết hạn',
        'message'=>'Quyền đọc eBook “'.$ebook['ten_sach'].'” sẽ hết hạn sau 2 ngày.',
        'reference'=>'eBook #'.$ebook['ebook_id'],
    ]);
    $processed++;
}

$todayStart = new DateTimeImmutable($today.' 00:00:00', new DateTimeZone('Asia/Ho_Chi_Minh'));
$utcNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$utcCutoff = $utcNow->modify('-30 days')->format('Y-m-d H:i:s');
$utcNowText = $utcNow->format('Y-m-d H:i:s');
$expired = Input::query($pdo,
    'SELECT a.ebook_id,a.tai_khoan_id,a.expires_at,a.granted_at,s.ten_sach FROM ebook_access a JOIN ebook e USING(ebook_id) JOIN sach s USING(sach_id) WHERE a.revoked_at IS NULL AND a.expires_at>=? AND a.expires_at<?',
    [$utcCutoff, $utcNowText]
)->fetchAll();
foreach ($expired as $ebook) {
    $expiredDate = (new DateTimeImmutable($ebook['expires_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('Y-m-d');
    $notifications->createFromEvent([
        'recipient_id'=>(int)$ebook['tai_khoan_id'], 'type'=>'access-expired',
        'source_id'=>'ebook-access:'.$ebook['ebook_id'].':'.$ebook['tai_khoan_id'].':'.preg_replace('/\\D/','',$ebook['granted_at']),
        'due_date'=>$expiredDate, 'title'=>'Quyền đọc eBook đã hết hạn',
        'message'=>'Quyền đọc eBook “'.$ebook['ten_sach'].'” đã hết hạn. Gửi yêu cầu mượn lại nếu bạn muốn tiếp tục đọc.',
        'reference'=>'eBook #'.$ebook['ebook_id'],
    ]);
    $processed++;
}
echo json_encode(['reminder_date'=>$targetDate,'overdue_as_of'=>$today,'events_processed'=>$processed,'status'=>'ok'], JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
