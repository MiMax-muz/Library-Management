<?php
declare(strict_types=1);

require_once __DIR__.'/../readers/ReaderService.php';
final class FinePaymentService {
    public function __construct(private PDO $pdo) {}

    public function collect(int $fineId, array $cashier, string $method="cash"): array {
        return Input::transaction($this->pdo, function () use ($fineId, $cashier, $method): array {
            if (!in_array($method, ["cash","transfer"], true)) throw new ApiException(422,"INVALID_METHOD","Hình thức thanh toán không hợp lệ.");
            $fine = Input::query($this->pdo,
                'SELECT fine_id,total FROM fine_record WHERE fine_id=? FOR UPDATE', [$fineId]
            )->fetch();
            if (!$fine) throw new ApiException(404, 'NOT_FOUND', 'Không tìm thấy khoản phạt.');

            $payment = Input::query($this->pdo,
                'SELECT payment_id,fine_id,amount,received_by,received_at FROM fine_payment WHERE fine_id=?', [$fineId]
            )->fetch();
            if ($payment) return ['payment' => $payment, 'already_paid' => true];
            $amount = (int)$fine['total'];
            if ($amount < 1) throw new ApiException(422, 'NO_AMOUNT_DUE', 'Khoản phạt này không có số tiền cần thu.');

            Input::query($this->pdo,
                'INSERT INTO fine_payment(fine_id,amount,received_by,received_at,method) VALUES (?,?,?,UTC_TIMESTAMP(),?)',
                [$fineId, $amount, (int)$cashier['tai_khoan_id'], $method]
            );
            $payment = Input::query($this->pdo,
                'SELECT payment_id,fine_id,amount,received_by,received_at FROM fine_payment WHERE fine_id=?', [$fineId]
            )->fetch();
            $reader=Input::query($this->pdo,'SELECT r.doc_gia_id FROM fine_record f JOIN loan_item i USING(loan_item_id) JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id) WHERE f.fine_id=?',[$fineId])->fetchColumn();
            if ($reader && !(new ReaderService($this->pdo))->unpaidOverdue((int)$reader)) {
                Input::query($this->pdo,"UPDATE the_doc_gia SET trang_thai='active' WHERE doc_gia_id=? AND trang_thai='locked'",[$reader]);
            }            Input::audit($this->pdo, 'fine.payment', (string)$fineId);
            return ['payment' => $payment, 'already_paid' => false];
        });
    }
}
