
<?php

require_once 'FineService.php';

// Tạo đối tượng FineService
$fineService = new FineService();

// Thử tính tiền phạt
// $fine = $fineService->calcLateReturnFine(
//     '2026-11-01',
//     '2026-11-09'
// );

// // Hiển thị kết quả
// echo "Late fine: " . $fine . " VND";

// $borrowedItems = [
//     [
//         'status' => 'LOST',
//         'due_date' => '2026-11-01',
//         'confirmation_date' => '2026-11-05',
//         'price' => 100000
//     ]
// ];

// echo "Total fine: " . $fine . " VND";

$borrowedItems = [
    [
        'status' => 'RETURNED',
        'due_date' => '2026-11-01',
        'return_date' => '2026-11-03'
    ],

    [
        'status' => 'LOST',
        'due_date' => '2026-11-01',
        'confirmation_date' => '2026-11-05',
        'price' => 100000
    ],

    [
        'status' => 'DAMAGED',
        'due_date' => '2026-11-01',
        'confirmation_date' => '2026-11-02',
        'repair_cost' => 50000,
        'price' => 150000
    ],

    [
        'status' => 'BORROWED',
        'due_date' => '2026-11-04'
    ]
];

$calculationDate = '2026-11-06';


$totalFine = $fineService->calcFineMain(
    $borrowedItems,
    $calculationDate 
);

echo "Total fine: " . $totalFine . " VND";
