<?php
declare(strict_types=1);
require_once __DIR__.'/AccountService.php';
require_once __DIR__.'/../service/mail/EmailAuthService.php';
return static function(Router $r,array $config): void {
    $db=fn()=>Database::connection($config['db']);
    $r->add('POST','/api/accounts/login',function()use($db){Response::json((new AccountService($db()))->login(Input::body()));});
    $mail=fn()=>new EmailAuthService($db(),new EmailSender(require __DIR__.'/../mail-config.php'));
    $r->add('POST','/api/accounts/register',function()use($mail){Response::json($mail()->register(Input::body()),201);});
    $r->add('POST','/api/accounts/resend-verification',function()use($mail){Response::json($mail()->requestLink(Input::body(),'verify'));});
    $r->add('POST','/api/accounts/forgot-password',function()use($mail){Response::json($mail()->requestLink(Input::body(),'reset'));});
    $r->add('POST','/api/accounts/verify-email',function()use($mail){Response::json($mail()->consume(Input::body(),'verify'));});
    $r->add('POST','/api/accounts/reset-password',function()use($mail){Response::json($mail()->consume(Input::body(),'reset'));});
    $r->add('POST','/api/accounts/logout',function(){Auth::logout();Response::json(['csrf_token'=>Auth::csrfToken()]);});
    $r->add('POST','/api/accounts/password',function()use($db){$p=$db();$u=Auth::requireRoles($p);(new AccountService($p))->changePassword(Input::body(),$u);Response::json(['csrf_token'=>Auth::csrfToken()]);});
    $r->add('GET','/api/accounts',function()use($db){$p=$db();Auth::requireRoles($p,['admin']);Response::json($p->query('SELECT tai_khoan_id,email,mssv,vai_tro,trang_thai,ho_ten,dien_thoai FROM tai_khoan ORDER BY tai_khoan_id')->fetchAll());});
    $r->add('POST','/api/accounts',function()use($db){$p=$db();Auth::requireRoles($p,['admin']);Response::json((new AccountService($p))->create(Input::body(),true),201);});
    $r->add('PATCH','/api/accounts/:id',function($args)use($db){$p=$db();$u=Auth::requireRoles($p,['admin']);(new AccountService($p))->update(Input::id($args['id']),Input::body(),$u);Response::json(['saved'=>true]);});
    $r->add('GET','/api/employees',function()use($db){$p=$db();Auth::requireRoles($p,['admin']);Response::json($p->query('SELECT n.*,t.email FROM nhan_vien n JOIN tai_khoan t USING(tai_khoan_id)')->fetchAll());});
    $r->add('POST','/api/employees',function()use($db){$p=$db();Auth::requireRoles($p,['admin']);(new AccountService($p))->employee(Input::body());Response::json(['saved'=>true]);});
};
