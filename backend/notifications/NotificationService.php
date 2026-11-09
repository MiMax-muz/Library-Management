<?php
declare(strict_types=1);

require_once __DIR__ . '/NotificationRepository.php';

/** TV4 tuần 4: thông báo cá nhân và sự kiện nội bộ, chưa nối nguồn giao dịch B3. */
final class NotificationService {
    private const TYPES = [
        'borrow-approved', 'borrow-rejected', 'renewal-approved',
        'renewal-rejected', 'due-reminder',
    ];
    private NotificationRepository $repository;

    public function __construct(private PDO $pdo) {
        $this->repository = new NotificationRepository($pdo);
    }

    private static function positiveId(mixed $value, int $status = 422): int {
        $text = is_int($value) || is_string($value) ? (string)$value : '';
        if (!preg_match('/^[1-9][0-9]*$/D', $text) || filter_var($text, FILTER_VALIDATE_INT) === false)
            throw new ApiException($status, $status === 404 ? 'NOT_FOUND' : 'INVALID_INPUT', $status === 404 ? 'Không tìm thấy thông báo.' : 'ID không hợp lệ.');
        return (int)$text;
    }

    private static function textField(array $data, string $key, int $min, int $max): string {
        if (!isset($data[$key]) || !is_string($data[$key]))
            throw new ApiException(422, 'INVALID_INPUT', "Trường $key không hợp lệ.");
        $value = trim($data[$key]);
        $length = mb_strlen($value, 'UTF-8');
        if ($length < $min || $length > $max)
            throw new ApiException(422, 'INVALID_INPUT', "Trường $key phải dài từ $min đến $max ký tự.");
        return $value;
    }

    private static function dueDate(mixed $value, bool $required): ?string {
        if ($value === null && !$required) return null;
        if (!is_string($value)) throw new ApiException(422, 'INVALID_INPUT', 'Ngày hạn không hợp lệ.');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value)
            throw new ApiException(422, 'INVALID_INPUT', 'Ngày hạn phải có dạng YYYY-MM-DD.');
        return $value;
    }

    private static function utcNow(): string {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    private static function iso(?string $value): ?string {
        return $value === null ? null : str_replace(' ', 'T', $value) . 'Z';
    }

    private static function output(array $row): array {
        return [
            'id' => (int)$row['thong_bao_id'], 'type' => $row['loai'],
            'title' => $row['tieu_de'], 'message' => $row['noi_dung'],
            'reference' => $row['tham_chieu'],
            'createdAt' => self::iso($row['created_at']), 'readAt' => self::iso($row['read_at']),
        ];
    }

    private function currentRecipient(): int {
        return (int)Auth::requireRoles($this->pdo)['tai_khoan_id'];
    }

    public function list(array $query = []): array {
        $recipientId = $this->currentRecipient();
        $page = self::positiveId($query['page'] ?? '1');
        if ($page > 1000000) throw new ApiException(422, 'INVALID_PAGE', 'Trang không hợp lệ.');
        $status = $query['status'] ?? 'all';
        if (!is_string($status) || !in_array($status, ['all', 'unread', 'read'], true))
            throw new ApiException(422, 'INVALID_STATUS', 'Bộ lọc trạng thái không hợp lệ.');
        $type = $query['type'] ?? 'all';
        if (!is_string($type) || !in_array($type, ['all', 'borrow', 'renewal', 'due'], true))
            throw new ApiException(422, 'INVALID_TYPE', 'Bộ lọc loại thông báo không hợp lệ.');
        $search = $query['search'] ?? '';
        if (!is_string($search) || mb_strlen(trim($search), 'UTF-8') > 100)
            throw new ApiException(422, 'INVALID_SEARCH', 'Từ khóa tìm kiếm không hợp lệ.');
        $filters = ['status' => $status, 'type' => $type, 'search' => trim($search)];
        $perPage = 20;
        $total = $this->repository->countForRecipient($recipientId, $filters);
        $unreadTotal = $this->repository->countForRecipient($recipientId, ['status' => 'unread']);
        $rows = $this->repository->listForRecipient($recipientId, $filters, $perPage, ($page - 1) * $perPage);
        return [
            'items' => array_map(self::output(...), $rows),
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'unread_total' => $unreadTotal],
        ];
    }

    public function unreadCount(): array {
        $recipientId = $this->currentRecipient();
        return ['unread' => $this->repository->countForRecipient($recipientId, ['status' => 'unread'])];
    }

    public function markRead(string $key): array {
        $recipientId = $this->currentRecipient();
        $id = self::positiveId($key, 404);
        $this->repository->markRead($id, $recipientId, self::utcNow());
        $row = $this->repository->findForRecipient($id, $recipientId);
        if (!$row) throw new ApiException(404, 'NOT_FOUND', 'Không tìm thấy thông báo.');
        return self::output($row);
    }

    public function markAllRead(): array {
        $recipientId = $this->currentRecipient();
        $updated = $this->repository->markAllRead($recipientId, self::utcNow());
        return ['updated' => $updated, 'unread' => $this->repository->countForRecipient($recipientId, ['status' => 'unread'])];
    }

    /** Chỉ gọi từ service/job tin cậy. Dùng cùng PDO của giao dịch nguồn; không có route POST công khai. */
    public function createFromEvent(array $event): array {
        $recipientId = self::positiveId($event['recipient_id'] ?? null);
        $type = self::textField($event, 'type', 1, 32);
        if (!in_array($type, self::TYPES, true))
            throw new ApiException(422, 'INVALID_EVENT', 'Loại sự kiện thông báo không hợp lệ.');
        $sourceId = self::textField($event, 'source_id', 1, 80);
        if (!preg_match('/^[A-Za-z0-9:_-]+$/D', $sourceId))
            throw new ApiException(422, 'INVALID_EVENT', 'Mã giao dịch nguồn không hợp lệ.');
        $dueDate = self::dueDate($event['due_date'] ?? null, $type === 'due-reminder');
        $title = self::textField($event, 'title', 2, 180);
        $message = self::textField($event, 'message', 2, 2000);
        $reference = isset($event['reference']) ? self::textField($event, 'reference', 1, 120) : null;
        $eventKey = hash('sha256', $type . "\0" . $sourceId . "\0" . ($dueDate ?? ''));
        $record = [
            'recipient_id' => $recipientId, 'type' => $type, 'source_id' => $sourceId,
            'due_date' => $dueDate, 'event_key' => $eventKey, 'title' => $title,
            'message' => $message, 'reference' => $reference, 'created_at' => self::utcNow(),
        ];
        try {
            $id = $this->repository->insert($record);
            $row = $this->repository->findForRecipient($id, $recipientId);
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000') throw $error;
            $existing = $this->repository->findByEventKey($eventKey);
            if (!$existing) throw $error;
            if ((int)$existing['tai_khoan_id'] !== $recipientId || $existing['loai'] !== $type || $existing['ma_nguon'] !== $sourceId || $existing['ngay_han'] !== $dueDate)
                throw new ApiException(409, 'EVENT_CONFLICT', 'Sự kiện đã được dùng cho thông báo khác.');
            $row = $existing;
        }
        if (!$row) throw new RuntimeException('Không đọc được thông báo vừa tạo.');
        return self::output($row);
    }
}
