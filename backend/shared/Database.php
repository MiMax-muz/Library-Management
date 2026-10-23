<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
final class Database {
    private static ?PDO $pdo = null;
    public static function connection(array $c): PDO {
        if (self::$pdo === null) self::$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']), $c['user'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        return self::$pdo;
    }
}
