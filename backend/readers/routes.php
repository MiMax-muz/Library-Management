<?php
declare(strict_types=1);
require_once __DIR__.'/ReaderService.php';
return static function(Router $r,array $c): void {
 $db=function()use($c){$p=Database::connection($c['db']);Auth::requireRoles($p,['librarian','admin']);return $p;};
 $r->add('GET','/api/readers',function()use($db){Response::json($db()->query('SELECT d.*,t.ma_the,t.ngay_cap,t.ngay_het_han,t.trang_thai card_status FROM doc_gia d LEFT JOIN the_doc_gia t USING(doc_gia_id) ORDER BY d.doc_gia_id')->fetchAll());});
 $r->add('GET','/api/readers/accounts',function()use($db){Response::json($db()->query("SELECT tai_khoan_id,email,mssv,ho_ten FROM tai_khoan WHERE vai_tro='user'")->fetchAll());});
 $r->add('POST','/api/readers',function()use($db){Response::json((new ReaderService($db()))->save(Input::body()),201);});
 $r->add('PUT','/api/readers/:id',function($a)use($db){Response::json((new ReaderService($db()))->save(Input::body(),Input::id($a['id'])));});
 $r->add('POST','/api/readers/:id/card',function($a)use($db){Response::json((new ReaderService($db()))->card(Input::id($a['id']),Input::body()));});
 $r->add('GET','/api/readers/:id/eligibility',function($a)use($db){Response::json((new ReaderService($db()))->eligibility(Input::id($a['id'])));});
};
