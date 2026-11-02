<?php
declare(strict_types=1);
// CLI-only: đặt mật khẩu ban đầu cho tài khoản quản lý fixture có hash vô hiệu.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$password = getenv('ADMIN_BOOTSTRAP_PASSWORD');
$email = getenv('ADMIN_BOOTSTRAP_EMAIL') ?: 'admin@example.test';
if (!is_string($password) || strlen($password) < 8) throw new RuntimeException('Đặt ADMIN_BOOTSTRAP_PASSWORD tối thiểu 8 ký tự.');
$config = require dirname(__DIR__) . '/bootstrap.php';
$pdo = Database::connection($config['db']);
$q = $pdo->prepare("SELECT tai_khoan_id,mat_khau_hash FROM tai_khoan WHERE email=? AND vai_tro='admin'");
$q->execute([$email]); $row = $q->fetch();
if (!$row) throw new RuntimeException('Không tìm thấy tài khoản quản lý fixture.');
if ($row['mat_khau_hash'] !== '!fixture-disabled') { echo "SKIP: tài khoản quản lý đã có mật khẩu\n"; exit; }
$q = $pdo->prepare("UPDATE tai_khoan SET mat_khau_hash=? WHERE tai_khoan_id=? AND mat_khau_hash='!fixture-disabled'");
$q->execute([password_hash($password, PASSWORD_DEFAULT), $row['tai_khoan_id']]);
if ($q->rowCount() !== 1) throw new RuntimeException('Tài khoản đã được khởi tạo bởi tiến trình khác.');
echo "PASS: tài khoản quản lý đã có mật khẩu ban đầu\n";
