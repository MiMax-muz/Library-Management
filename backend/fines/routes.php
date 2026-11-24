<?php
declare(strict_types=1);
require_once __DIR__.'/FineService.php';
require_once __DIR__.'/FinePaymentService.php';
return static function(Router $r,array $c): void {
 $r->add('GET','/api/fines/receiving-account',function()use($c){
  Auth::requireRoles(Database::connection($c['db']));
  Response::json(['bank'=>getenv('FINES_BANK_BIN')?:'', 'account'=>getenv('FINES_BANK_ACCOUNT')?:'']);
 }); $r->add('GET','/api/fines',function()use($c){$p=Database::connection($c['db']);$u=Auth::requireRoles($p);Response::json(Input::query($p,'SELECT f.*,s.ten_sach,b.ma_ban_sach,d.ho_ten,p.payment_id,p.amount paid_amount,p.received_by,p.received_at paid_at,p.method FROM fine_record f JOIN loan_item i USING(loan_item_id) JOIN ban_sach b USING(ban_sach_id) JOIN sach s USING(sach_id) JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id) JOIN doc_gia d USING(doc_gia_id) LEFT JOIN fine_payment p USING(fine_id)'.($u['vai_tro']==='user'?' WHERE d.tai_khoan_id=?':'').' ORDER BY fine_id DESC',$u['vai_tro']==='user'?[$u['tai_khoan_id']]:[])->fetchAll());});
 $r->add('POST','/api/fines/preview',function()use($c){$p=Database::connection($c['db']);Auth::requireRoles($p,['admin','librarian']);Response::json(FineService::calculate(Input::body(),$p->query('SELECT khoa,gia_tri FROM cau_hinh')->fetchAll(PDO::FETCH_KEY_PAIR)));});
 $r->add('POST','/api/fines/:id/pay',function($a)use($c){$p=Database::connection($c['db']);$u=Auth::requireRoles($p,['admin','librarian']);Response::json((new FinePaymentService($p))->collect(Input::id($a['id']),$u,Input::body()["method"]??"cash"));});
};
