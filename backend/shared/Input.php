<?php
declare(strict_types=1);
final class Input {
    public static function body(): array {
        try { $data = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR); }
        catch (JsonException $e) { throw new ApiException(400, 'INVALID_JSON', 'JSON không hợp lệ.'); }
        if (!is_array($data) || array_is_list($data)) throw new ApiException(422, 'INVALID_INPUT', 'Cần đối tượng dữ liệu.');
        return $data;
    }
    public static function text(array $data, string $key, int $max = 255): string {
        $v = $data[$key] ?? null;
        if (!is_string($v) || trim($v) === '' || mb_strlen(trim($v)) > $max) throw new ApiException(422, 'INVALID_INPUT', "Trường $key không hợp lệ.");
        return trim($v);
    }
    public static function id(mixed $value): int {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 1) throw new ApiException(422, 'INVALID_ID', 'Mã phải là số nguyên dương.');
        return (int)$value;
    }
    public static function date(string $value): DateTimeImmutable {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Ho_Chi_Minh'));
        if (!$d || $d->format('Y-m-d') !== $value) throw new ApiException(422, 'INVALID_DATE', 'Ngày không hợp lệ.');
        return $d;
    }
    public static function query(PDO $db, string $sql, array $args = []): PDOStatement { $q=$db->prepare($sql); $q->execute($args); return $q; }
    public static function transaction(PDO $db, callable $fn): mixed {
        $db->beginTransaction();
        try { $result=$fn(); $db->commit(); return $result; }
        catch (Throwable $e) { if($db->inTransaction()) $db->rollBack(); throw $e; }
    }
    public static function audit(PDO $db, string $action, string $object): void {
        self::query($db,'INSERT INTO nhat_ky_thao_tac(tai_khoan_id,hanh_dong,doi_tuong) VALUES (?,?,?)',[$_SESSION['tai_khoan_id'] ?? null,$action,$object]);
    }
}
