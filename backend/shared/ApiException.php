<?php
declare(strict_types=1);
// TV1: nền dùng chung tuần 1.
final class ApiException extends RuntimeException {
    public function __construct(public int $status, public string $errorCode, string $message, public array $details = []) { parent::__construct($message); }
}
