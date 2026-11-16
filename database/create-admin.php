<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$c=require __DIR__.'/../backend/bootstrap.php';
require __DIR__.'/../backend/accounts/AccountService.php';
$email=getenv('ADMIN_EMAIL');$password=getenv('ADMIN_PASSWORD');
if(!$email||!$password)throw new RuntimeException('Đặt ADMIN_EMAIL và ADMIN_PASSWORD (tối thiểu 8 ký tự).');
$db=Database::connection($c['db']);
if(Input::query($db,'SELECT tai_khoan_id FROM tai_khoan WHERE email=?',[$email])->fetch())throw new RuntimeException('Email đã tồn tại; không ghi đè tài khoản.');
(new AccountService($db))->create(['email'=>$email,'password'=>$password,'ho_ten'=>'Quản trị viên','vai_tro'=>'admin'],true);
echo "Đã tạo Admin.\n";
