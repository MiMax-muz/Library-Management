<?php
require_once __DIR__.'/LoanService.php';
return static function(Router $r,array $c):void{
 $context=function(array $roles=[])use($c){$p=Database::connection($c['db']);return [new LoanService($p),Auth::requireRoles($p,$roles)];};
 $r->add('GET','/api/loans/policy',function()use($c){
  $db=Database::connection($c['db']);Auth::requireRoles($db);
  Response::json($db->query("SELECT khoa,gia_tri FROM cau_hinh WHERE khoa IN ('borrow_per_request','borrow_days')")->fetchAll(PDO::FETCH_KEY_PAIR));
 }); $r->add('GET','/api/loans/requests',function()use($context){[$s,$u]=$context();Response::json($s->requests($u));});
 $r->add('POST','/api/loans/requests',function()use($context){[$s,$u]=$context(['user']);Response::json($s->request(Input::body(),$u),201);});
 $r->add('POST','/api/loans/requests/:id/:action',function($a)use($context){[$s,$u]=$context();Response::json($s->decide(Input::id($a['id']),$a['action'],$u,$a['action']==='handover'?Input::body():[]));});
 $r->add('GET','/api/loans/items',function()use($context){[$s,$u]=$context();Response::json($s->items($u,$_GET));});
 $r->add('POST','/api/loans/items/:id/return',function($a)use($context){[$s,$u]=$context(['admin','librarian']);Response::json($s->returnItem(Input::id($a['id']),Input::body(),$u));});
 $r->add('POST','/api/loans/items/:id/renew',function($a)use($context){[$s,$u]=$context();Response::json($s->renew(Input::id($a['id']),$u));});
 $r->add('POST','/api/loans/renewals/:id',function($a)use($context){[$s,$u]=$context(['admin','librarian']);$d=Input::body();if(!isset($d['approve'])||!is_bool($d['approve']))throw new ApiException(422,'INVALID_INPUT','Cần quyết định duyệt hoặc từ chối.');Response::json($s->reviewRenewal(Input::id($a['id']),$d['approve'],$u));});
};
