<?php

declare(strict_types=1);

/**
 * TV3 — B0: vị trí dự kiến cho FineService; chưa khai báo lớp/hàm.
 * Triển khai từ tuần 2. Kiểm tra quyền và điều kiện, điều phối nghiệp vụ/giao dịch; không tự tạo PDO riêng.
 */

class FineService
{
    // tính tiền phạt trả trễ sách
    public function calcLateReturnFine(string $dueDate, string $returnDate) : int
    { // datetime format: YYYY/MM/DD
        $due = new Datetime($dueDate);
        $return = new Datetime($returnDate);

        $interval = $due->diff($return);

        if ($interval->invert === 1) {
            return 0;
        }

        return $interval->days * 2000;
    }

    // tính tiền phạt trả hư hỏng sách
    public function calcDamagedBookFine(int $repairCost,int $bookValue) : int
    {
        return min($repairCost, $bookValue); // temporary value, need to consult with the database to get actual price
    }

    // tính tiền phạt trả mất sách
    public function calcLostBookFine(int $bookValue) : int
    {
        return $bookValue; // temporary value, need to consult with the database to get actual price
    }

    // tính tiền phạt chung
    public function calcFineMain(
        array $borrowedItems,
        string $calculationDate
    ): int {
        $totalFine = 0;

        foreach ($borrowedItems as $item) {

            if ($item['status'] === "RETURNED") {

                $fine = $this->calcLateReturnFine(
                    $item['due_date'],
                    $item['return_date']
                );

            } else if ($item['status'] === "LOST") {

                $lateFine = $this->calcLateReturnFine(
                    $item['due_date'],
                    $item['confirmation_date']
                );

                $compensation = $this->calcLostBookFine(
                    $item['price']
                );

                $fine = $lateFine + $compensation;

            } else if ($item['status'] === "DAMAGED") {

                $lateFine = $this->calcLateReturnFine(
                    $item['due_date'],
                    $item['confirmation_date']
                );

                $compensation = $this->calcDamagedBookFine(
                    $item['repair_cost'],
                    $item['price']
                );

                $fine = $lateFine + $compensation;

            } else if ($item['status'] === "BORROWED") {

                $fine = $this->calcLateReturnFine(
                    $item['due_date'],
                    $calculationDate
                );

            } else {
                throw new InvalidArgumentException(
                    "Invalid item status: " . $item['status']
                );
            }

            $totalFine += $fine;
        }

        return $totalFine;
    }

}


