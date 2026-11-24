<?php
require_once __DIR__.'/../ebooks/EbookService.php';
return static function(Router $r,array $c):void{
 $ctx=function()use($c){$p=Database::connection($c['db']);return [$p,Auth::requireRoles($p),new EbookService($p)];};
 $r->add('GET','/api/shelves',function()use($ctx){
  [$db,$user]=$ctx();$account=$user['tai_khoan_id'];
  $rows=Input::transaction($db,function()use($db,$account){
   if(Input::query($db,'INSERT IGNORE INTO shelf_owner(account_id) VALUES (?)',[$account])->rowCount()){
    foreach(['Yêu thích','Học thuật','Giải trí'] as $name){
     Input::query($db,'INSERT INTO personal_shelf(account_id,name) VALUES (?,?)',[$account,$name]);
     if($name==='Yêu thích')Input::query($db,'INSERT IGNORE INTO personal_shelf_book SELECT ?,e.sach_id FROM bookshelf_entry b JOIN ebook e USING(ebook_id) WHERE b.tai_khoan_id=? AND b.favorite=1',[$db->lastInsertId(),$account]);
    }
   }
   return Input::query($db,'SELECT p.id,p.name,b.sach_id,s.ten_sach FROM personal_shelf p LEFT JOIN personal_shelf_book b ON b.shelf_id=p.id LEFT JOIN sach s USING(sach_id) WHERE p.account_id=? ORDER BY p.id,s.ten_sach',[$account])->fetchAll();
  });
  Response::json($rows);
 });
 $r->add('POST','/api/shelves',function()use($ctx){
  [$db,$user]=$ctx();Input::query($db,'INSERT INTO personal_shelf(account_id,name) VALUES (?,?)',[$user['tai_khoan_id'],Input::text(Input::body(),'name',100)]);Response::json(['id'=>$db->lastInsertId()],201);
 });
 $r->add('PUT','/api/shelves/:id',function($a)use($ctx){
  [$db,$user]=$ctx();Input::query($db,'UPDATE personal_shelf SET name=? WHERE id=? AND account_id=?',[Input::text(Input::body(),'name',100),Input::id($a['id']),$user['tai_khoan_id']]);Response::json(['saved'=>true]);
 });
 $r->add('DELETE','/api/shelves/:id',function($a)use($ctx){
  [$db,$user]=$ctx();Input::query($db,'DELETE FROM personal_shelf WHERE id=? AND account_id=?',[Input::id($a['id']),$user['tai_khoan_id']]);Response::json(null,204);
 });
 $r->add('PUT','/api/shelves/:id/books/:book',function($a)use($ctx){
  [$db,$user]=$ctx();Input::query($db,'INSERT INTO personal_shelf_book SELECT id,sach_id FROM personal_shelf CROSS JOIN sach WHERE id=? AND account_id=? AND sach_id=? ON DUPLICATE KEY UPDATE shelf_id=VALUES(shelf_id)',[Input::id($a['id']),$user['tai_khoan_id'],Input::id($a['book'])]);Response::json(['saved'=>true]);
 });
 $r->add('DELETE','/api/shelves/:id/books/:book',function($a)use($ctx){
  [$db,$user]=$ctx();Input::query($db,'DELETE b FROM personal_shelf_book b JOIN personal_shelf p ON p.id=b.shelf_id WHERE p.id=? AND p.account_id=? AND b.sach_id=?',[Input::id($a['id']),$user['tai_khoan_id'],Input::id($a['book'])]);Response::json(null,204);
 }); $r->add('POST','/api/book-history/:id/view',function($a)use($ctx){
  [$p,$u]=$ctx();
  Input::query($p,'INSERT INTO book_view (tai_khoan_id,sach_id,viewed_at) SELECT ?,sach_id,UTC_TIMESTAMP() FROM sach WHERE sach_id=? ON DUPLICATE KEY UPDATE viewed_at=UTC_TIMESTAMP()',[$u['tai_khoan_id'],Input::id($a['id'])]);
  Response::json(['saved'=>true]);
 });
 $r->add('GET','/api/book-history',function()use($ctx){
  [$p,$u]=$ctx();$id=$u['tai_khoan_id'];
  $viewed=Input::query($p,'SELECT s.sach_id,s.ten_sach,v.viewed_at FROM book_view v JOIN sach s USING(sach_id) WHERE v.tai_khoan_id=? ORDER BY v.viewed_at DESC,s.sach_id DESC',[$id])->fetchAll();
  $borrowed=Input::query($p,"SELECT s.sach_id,s.ten_sach,b.ma_ban_sach AS copy_code,l.handed_over_at AS borrowed_at,'Bản in' AS kind
   FROM loan_item i JOIN ban_sach b USING(ban_sach_id) JOIN sach s USING(sach_id) JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id) JOIN doc_gia d USING(doc_gia_id) WHERE d.tai_khoan_id=?
   UNION ALL SELECT s.sach_id,s.ten_sach,NULL,r.reviewed_at,'eBook' FROM ebook_request r JOIN ebook e USING(ebook_id) JOIN sach s USING(sach_id)
   WHERE r.tai_khoan_id=? AND r.status='approved' ORDER BY borrowed_at DESC",[$id,$id])->fetchAll();
  Response::json(['viewed'=>$viewed,'borrowed'=>$borrowed]);
 }); $r->add('GET','/api/bookshelf',function()use($ctx){[$p,$u,$s]=$ctx();Response::json(Input::query($p,'SELECT b.*,s.ten_sach,a.expires_at,a.revoked_at FROM bookshelf_entry b JOIN ebook e USING(ebook_id) JOIN sach s USING(sach_id) LEFT JOIN ebook_access a ON a.ebook_id=b.ebook_id AND a.tai_khoan_id=b.tai_khoan_id WHERE b.tai_khoan_id=? ORDER BY b.updated_at DESC',[$u['tai_khoan_id']])->fetchAll());});
 $r->add('PUT','/api/bookshelf/:id',function($a)use($ctx){[$p,$u,$s]=$ctx();$id=Input::id($a['id']);$s->detail($id,$u);$d=Input::body();if(!isset($d['favorite'])||!is_bool($d['favorite']))throw new ApiException(422,'INVALID_INPUT','Yêu thích cần đúng/sai.');Input::query($p,'INSERT INTO bookshelf_entry(tai_khoan_id,ebook_id,favorite,updated_at) VALUES (?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE favorite=VALUES(favorite),updated_at=UTC_TIMESTAMP()',[$u['tai_khoan_id'],$id,(int)$d['favorite']]);Response::json(['saved'=>true]);});
 $r->add('GET','/api/reading/:id',function($a)use($ctx){[$p,$u,$s]=$ctx();$id=Input::id($a['id']);$s->permittedFile($id,$u);Response::json(['progress'=>Input::query($p,'SELECT page_number,percent,updated_at FROM reading_progress WHERE tai_khoan_id=? AND ebook_file_id=?',[$u['tai_khoan_id'],$id])->fetch()?:null,'bookmarks'=>Input::query($p,'SELECT bookmark_id,page_number,note FROM reading_bookmark WHERE tai_khoan_id=? AND ebook_file_id=? ORDER BY page_number',[$u['tai_khoan_id'],$id])->fetchAll()]);});
 $r->add('PUT','/api/reading/:id',function($a)use($ctx){[$p,$u,$s]=$ctx();$id=Input::id($a['id']);$d=Input::body();$page=Input::id($d['page_number']??null);$percent=filter_var($d['percent']??null,FILTER_VALIDATE_FLOAT);if($page>100000||$percent===false||$percent<0||$percent>100)throw new ApiException(422,'INVALID_PROGRESS','Trang hoặc phần trăm không hợp lệ.');
  Input::transaction($p,function()use($p,$s,$id,$u,$page,$percent){Input::query($p,'SELECT tai_khoan_id FROM tai_khoan WHERE tai_khoan_id=? FOR UPDATE',[$u['tai_khoan_id']]);$f=$s->permittedFile($id,$u);Input::query($p,'INSERT INTO reading_progress VALUES (?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE page_number=VALUES(page_number),percent=VALUES(percent),updated_at=UTC_TIMESTAMP()',[$u['tai_khoan_id'],$id,$page,$percent]);Input::query($p,'INSERT INTO reading_history(tai_khoan_id,ebook_file_id,page_number,percent,created_at) VALUES (?,?,?,?,UTC_TIMESTAMP())',[$u['tai_khoan_id'],$id,$page,$percent]);Input::query($p,'INSERT INTO bookshelf_entry(tai_khoan_id,ebook_id,updated_at) VALUES (?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE updated_at=UTC_TIMESTAMP()',[$u['tai_khoan_id'],$f['ebook_id']]);});Response::json(['saved'=>true]);});
 $r->add('POST','/api/reading/:id/bookmarks',function($a)use($ctx){[$p,$u,$s]=$ctx();$id=Input::id($a['id']);$s->permittedFile($id,$u);$d=Input::body();$page=Input::id($d['page_number']??null);if($page>100000)throw new ApiException(422,'INVALID_PAGE','Trang quá lớn.');$note=Input::text($d,'note',500);Input::query($p,'INSERT INTO reading_bookmark(tai_khoan_id,ebook_file_id,page_number,note) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE note=VALUES(note)',[$u['tai_khoan_id'],$id,$page,$note]);Response::json(['saved'=>true]);});
 $r->add('DELETE','/api/bookmarks/:id',function($a)use($ctx){[$p,$u,$s]=$ctx();Input::query($p,'DELETE FROM reading_bookmark WHERE bookmark_id=? AND tai_khoan_id=?',[Input::id($a['id']),$u['tai_khoan_id']]);Response::json(['saved'=>true]);});
 $r->add('GET','/api/reading-history',function()use($ctx){[$p,$u,$s]=$ctx();Response::json(Input::query($p,'SELECT h.history_id,h.page_number,h.percent,h.created_at,f.file_version,s.ten_sach FROM reading_history h JOIN ebook_file f USING(ebook_file_id) JOIN ebook e USING(ebook_id) JOIN sach s USING(sach_id) WHERE h.tai_khoan_id=? ORDER BY history_id DESC LIMIT 200',[$u['tai_khoan_id']])->fetchAll());});
};
