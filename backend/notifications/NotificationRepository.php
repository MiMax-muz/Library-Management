<?php
declare(strict_types=1);

/** Truy vấn thông báo; dùng PDO của bên gọi và không tự điều phối giao dịch. */
final class NotificationRepository {
    public function __construct(private PDO $pdo) {}

    private static function criteria(int $recipientId, array $filters): array {
        $where = ' WHERE tai_khoan_id = ?';
        $params = [$recipientId];
        if (($filters['status'] ?? 'all') === 'unread') $where .= ' AND read_at IS NULL';
        if (($filters['status'] ?? 'all') === 'read') $where .= ' AND read_at IS NOT NULL';
        $types = match ($filters['type'] ?? 'all') {
            'borrow' => ['borrow-approved', 'borrow-rejected'],
            'renewal' => ['renewal-approved', 'renewal-rejected'],
            'due' => ['due-reminder'],
            default => [],
        };
        if ($types) {
            $where .= ' AND loai IN (' . implode(',', array_fill(0, count($types), '?')) . ')';
            array_push($params, ...$types);
        }
        if (($filters['search'] ?? '') !== '') {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']);
            $pattern = '%' . $escaped . '%';
            $where .= " AND (tieu_de LIKE ? ESCAPE '!' OR noi_dung LIKE ? ESCAPE '!' OR tham_chieu LIKE ? ESCAPE '!')";
            array_push($params, $pattern, $pattern, $pattern);
        }
        return [$where, $params];
    }

    public function countForRecipient(int $recipientId, array $filters = []): int {
        [$where, $params] = self::criteria($recipientId, $filters);
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM thong_bao' . $where);
        $query->execute($params);
        return (int)$query->fetchColumn();
    }

    public function listForRecipient(int $recipientId, array $filters, int $limit, int $offset): array {
        [$where, $params] = self::criteria($recipientId, $filters);
        $query = $this->pdo->prepare(
            'SELECT thong_bao_id, loai, tieu_de, noi_dung, tham_chieu, created_at, read_at '
            . 'FROM thong_bao' . $where
            . ' ORDER BY created_at DESC, thong_bao_id DESC LIMIT ? OFFSET ?'
        );
        foreach ($params as $index => $value) $query->bindValue($index + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $query->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $query->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function findForRecipient(int $id, int $recipientId): ?array {
        $query = $this->pdo->prepare(
            'SELECT thong_bao_id, loai, tieu_de, noi_dung, tham_chieu, created_at, read_at '
            . 'FROM thong_bao WHERE thong_bao_id = ? AND tai_khoan_id = ?'
        );
        $query->execute([$id, $recipientId]);
        return $query->fetch() ?: null;
    }

    public function findByEventKey(string $eventKey): ?array {
        $query = $this->pdo->prepare(
            'SELECT thong_bao_id, tai_khoan_id, loai, ma_nguon, ngay_han, tieu_de, noi_dung, tham_chieu, created_at, read_at '
            . 'FROM thong_bao WHERE ma_su_kien = ?'
        );
        $query->execute([$eventKey]);
        return $query->fetch() ?: null;
    }

    public function insert(array $event): int {
        $query = $this->pdo->prepare(
            'INSERT INTO thong_bao (tai_khoan_id, loai, ma_nguon, ngay_han, ma_su_kien, tieu_de, noi_dung, tham_chieu, created_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $query->execute([
            $event['recipient_id'], $event['type'], $event['source_id'], $event['due_date'],
            $event['event_key'], $event['title'], $event['message'], $event['reference'], $event['created_at'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function markRead(int $id, int $recipientId, string $readAt): void {
        $query = $this->pdo->prepare(
            'UPDATE thong_bao SET read_at = ? WHERE thong_bao_id = ? AND tai_khoan_id = ? AND read_at IS NULL'
        );
        $query->execute([$readAt, $id, $recipientId]);
    }

    public function markAllRead(int $recipientId, string $readAt): int {
        $query = $this->pdo->prepare('UPDATE thong_bao SET read_at = ? WHERE tai_khoan_id = ? AND read_at IS NULL');
        $query->execute([$readAt, $recipientId]);
        return $query->rowCount();
    }
}
