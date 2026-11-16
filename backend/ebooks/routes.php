<?php
require_once __DIR__.'/EbookService.php';
return static function(Router $r,array $c):void{
 $ctx=function(array $roles=[])use($c){$p=Database::connection($c['db']);return [$p,Auth::requireRoles($p,$roles),new EbookService($p)];};
 $r->add('GET','/api/ebooks',function()use($ctx){[$p,$u,$s]=$ctx();Response::json($s->catalog($u));});
 $r->add('GET','/api/ebooks/:id',function($a)use($ctx){[$p,$u,$s]=$ctx();Response::json($s->detail(Input::id($a['id']),$u));});
 $r->add('POST','/api/ebooks',function()use($ctx){[$p,$u,$s]=$ctx(['admin','librarian']);Response::json($s->save(Input::body()),201);});
 $r->add('PUT','/api/ebooks/:id',function($a)use($ctx){[$p,$u,$s]=$ctx(['admin','librarian']);Response::json($s->save(Input::body(),Input::id($a['id'])));});
 $r->add('POST','/api/ebooks/:id/files',function($a)use($ctx){[$p,$u,$s]=$ctx(['admin','librarian']);Response::json($s->upload(Input::id($a['id']),$_FILES['pdf']??[]),201);});
 $r->add('GET','/api/ebook-files/:id/content',function($a)use($c){$p=Database::connection($c['db']);$u=Auth::user($p)??['tai_khoan_id'=>0,'vai_tro'=>'guest'];(new EbookService($p))->stream(Input::id($a['id']),$u);});
 $r->add('GET','/api/public/ebooks',function()use($c){$p=Database::connection($c['db']);Response::json($p->query("SELECT e.ebook_id,s.ten_sach,f.ebook_file_id,f.file_version FROM ebook e JOIN sach s USING(sach_id) JOIN ebook_file f USING(ebook_id) WHERE e.active=1 AND s.dang_luu_hanh=1 AND e.access_mode='public' AND f.file_version=(SELECT MAX(v.file_version) FROM ebook_file v WHERE v.ebook_id=e.ebook_id) ORDER BY s.ten_sach")->fetchAll());});
 $r->add('POST','/api/ebook-access',function()use($ctx){[$p,$u,$s]=$ctx(['user']);Response::json($s->borrow(Input::body(),$u));});
 $r->add('GET','/api/ebook-requests',function()use($ctx){[$p,$u,$s]=$ctx();Response::json(Input::query($p,'SELECT r.*,t.email,s.ten_sach FROM ebook_request r JOIN tai_khoan t USING(tai_khoan_id) JOIN ebook e USING(ebook_id) JOIN sach s USING(sach_id)'.($u['vai_tro']==='user'?' WHERE r.tai_khoan_id=?':'').' ORDER BY request_id DESC',$u['vai_tro']==='user'?[$u['tai_khoan_id']]:[])->fetchAll());});
 $r->add('POST','/api/ebook-requests/:id',function($a)use($ctx){[$p,$u,$s]=$ctx(['admin','librarian']);$d=Input::body();if(!isset($d['approve'])||!is_bool($d['approve']))throw new ApiException(422,'INVALID_INPUT','Cần quyết định duyệt hoặc từ chối.');$s->review(Input::id($a['id']),$d['approve'],$u);Response::json(['saved'=>true]);});
 $r->add('GET','/api/ebook-access',function()use($ctx){[$p,$u,$s]=$ctx(['admin','librarian']);Response::json($p->query('SELECT a.*,t.email,s.ten_sach FROM ebook_access a JOIN tai_khoan t USING(tai_khoan_id) JOIN ebook e USING(ebook_id) JOIN sach s USING(sach_id) ORDER BY access_id DESC')->fetchAll());});
 $r->add('POST','/api/ebooks/:id/revoke',function($a)use($ctx){[$p,$u,$s]=$ctx(['admin','librarian']);$s->revoke(Input::id($a['id']),Input::id(Input::body()['tai_khoan_id']??null));Response::json(['saved'=>true]);});
};
