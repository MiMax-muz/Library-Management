<?php
declare(strict_types=1);

final class FinePaymentService {
    public function __construct(private PDO $pdo) {}

    public function collect(int $fineId, array $cashier): array {
        return Input::transaction($this->pdo, function () use ($fineId, $cashier): array {
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
                'INSERT INTO fine_payment(fine_id,amount,received_by,received_at) VALUES (?,?,?,UTC_TIMESTAMP())',
                [$fineId, $amount, (int)$cashier['tai_khoan_id']]
            );
            $payment = Input::query($this->pdo,
                'SELECT payment_id,fine_id,amount,received_by,received_at FROM fine_payment WHERE fine_id=?', [$fineId]
            )->fetch();
            Input::audit($this->pdo, 'fine.payment', (string)$fineId);
            return ['payment' => $payment, 'already_paid' => false];
        });
    }
}
