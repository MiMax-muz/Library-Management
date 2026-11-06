<?php
declare(strict_types=1);

/** Đọc cấu hình B1 cho form TV4; B2 chung chưa cho phép ghi chính sách. */
final class SettingsService {
    private const NUMERIC = [
        'borrow_per_request' => [1, 3],
        'borrow_total' => [1, 7],
        'borrow_days' => [1, 14],
        'renewal_limit' => [0, 2],
        'renewal_days' => [1, 7],
        'renewal_overdue_days' => [0, 1],
        'ebook_per_request' => [1, 3],
        'ebook_days' => [1, 14],
        'notice_days_before_due' => [0, 30],
        'overdue_daily_vnd' => [2000, 2000],
        'damage_surcharge' => [0, 0],
    ];
    private const TEXT = [
        'card_duration' => '12_months',
        'business_timezone' => 'Asia/Ho_Chi_Minh',
    ];

    public function __construct(private PDO $pdo) {}

    public function current(): array {
        Auth::requireRoles($this->pdo, ['admin']);
        $keys = array_merge(array_keys(self::NUMERIC), array_keys(self::TEXT), ['notice_hour_local']);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $query = $this->pdo->prepare("SELECT khoa, gia_tri FROM cau_hinh WHERE khoa IN ($placeholders)");
        $query->execute($keys);
        $raw = $query->fetchAll(PDO::FETCH_KEY_PAIR);
        $result = [];
        foreach (self::NUMERIC as $key => [$min, $max]) {
            $value = $raw[$key] ?? null;
            if (!is_string($value) || !preg_match('/^(0|[1-9][0-9]*)$/D', $value) || (int)$value < $min || (int)$value > $max)
                throw new ApiException(503, 'SETTINGS_NOT_READY', "Cấu hình $key thiếu hoặc không hợp lệ.");
            $result[$key] = (int)$value;
        }
        foreach (self::TEXT as $key => $expected) {
            if (($raw[$key] ?? null) !== $expected)
                throw new ApiException(503, 'SETTINGS_NOT_READY', "Cấu hình $key thiếu hoặc không hợp lệ.");
            $result[$key] = $expected;
        }
        $hour = $raw['notice_hour_local'] ?? null;
        if (!is_string($hour) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $hour))
            throw new ApiException(503, 'SETTINGS_NOT_READY', 'Cấu hình notice_hour_local thiếu hoặc không hợp lệ.');
        $result['notice_hour_local'] = $hour;
        if ($result['borrow_total'] < $result['borrow_per_request'])
            throw new ApiException(503, 'SETTINGS_NOT_READY', 'Giới hạn mượn trong cấu hình không hợp lệ.');
        return $result;
    }
}
