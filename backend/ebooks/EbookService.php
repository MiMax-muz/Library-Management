<?php
declare(strict_types=1);
require_once __DIR__.'/../readers/ReaderService.php';
require_once __DIR__.'/../settings/SettingsService.php';
require_once __DIR__.'/../notifications/NotificationService.php';
final class EbookService {
 public function __construct(private PDO $db){}
 public function catalog(array $u):array{
  return Input::query($this->db,"SELECT e.*,s.ten_sach,s.ma_sach,(SELECT MAX(file_version) FROM ebook_file f WHERE f.ebook_id=e.ebook_id) latest_version,a.expires_at,a.revoked_at FROM ebook e JOIN sach s USING(sach_id) LEFT JOIN ebook_access a ON a.ebook_id=e.ebook_id AND a.tai_khoan_id=?".(!in_array($u['vai_tro'],['admin','librarian'],true)?' WHERE e.active=1 AND s.dang_luu_hanh=1':'').' ORDER BY e.ebook_id DESC',[$u['tai_khoan_id']])->fetchAll();
 }
 public function detail(int $id,array $u):array{
  $e=Input::query($this->db,'SELECT e.*,s.ten_sach,s.dang_luu_hanh book_active FROM ebook e JOIN sach s USING(sach_id) WHERE ebook_id=?',[$id])->fetch();if(!$e||(!in_array($u['vai_tro'],['admin','librarian'],true)&&(!$e['active']||!$e['book_active'])))throw new ApiException(404,'NOT_FOUND','Không có eBook.');
  $e['versions']=Input::query($this->db,'SELECT ebook_file_id,file_version,size_bytes,created_at FROM ebook_file WHERE ebook_id=? ORDER BY file_version DESC',[$id])->fetchAll();
  $e['access']=Input::query($this->db,'SELECT expires_at,revoked_at FROM ebook_access WHERE ebook_id=? AND tai_khoan_id=?',[$id,$u['tai_khoan_id']])->fetch()?:null;
  $e['can_read']=in_array($u['vai_tro'],['admin','librarian'],true)||$e['access_mode']==='public'||(bool)Input::query($this->db,'SELECT 1 FROM ebook_access WHERE ebook_id=? AND tai_khoan_id=? AND revoked_at IS NULL AND expires_at>UTC_TIMESTAMP()',[$id,$u['tai_khoan_id']])->fetch();return $e;
 }
 public function permittedFile(int $file,array $u):array{
  $f=Input::query($this->db,'SELECT * FROM ebook_file WHERE ebook_file_id=?',[$file])->fetch();if(!$f)throw new ApiException(404,'NOT_FOUND','Không có phiên bản PDF.');$e=$this->detail((int)$f['ebook_id'],$u);if(!$e['can_read'])throw new ApiException(403,'EBOOK_ACCESS_REQUIRED','Cần mượn eBook; quyền đọc đã hết hạn hoặc bị thu hồi.');return $f;
 }
 public function save(array $d,?int $id=null):array{
  $book=Input::id($d['sach_id']??null);$mode=$d['access_mode']??'restricted';if(!in_array($mode,['public','restricted'],true))throw new ApiException(422,'INVALID_MODE','Quyền đọc không hợp lệ.');$active=filter_var($d['active']??1,FILTER_VALIDATE_INT);if(!in_array($active,[0,1],true))throw new ApiException(422,'INVALID_ACTIVE','Trạng thái không hợp lệ.');
  if(!Input::query($this->db,'SELECT sach_id FROM sach WHERE sach_id=?',[$book])->fetch())throw new ApiException(422,'INVALID_BOOK','Không có đầu sách.');
  if($id){if(!Input::query($this->db,'SELECT ebook_id FROM ebook WHERE ebook_id=?',[$id])->fetch())throw new ApiException(404,'NOT_FOUND','Không có eBook.');Input::query($this->db,'UPDATE ebook SET sach_id=?,access_mode=?,active=? WHERE ebook_id=?',[$book,$mode,$active,$id]);}else{Input::query($this->db,'INSERT INTO ebook(sach_id,access_mode,active) VALUES (?,?,?)',[$book,$mode,$active]);$id=(int)$this->db->lastInsertId();}return ['ebook_id'=>$id];
 }
 public function upload(int $id,array $f):array{
  if(($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($f['tmp_name']??'')||($f['size']??0)>20*1024*1024||($f['size']??0)<5)throw new ApiException(422,'INVALID_PDF','Chọn PDF hợp lệ, tối đa 20 MB (còn phụ thuộc giới hạn PHP).');
  $head=file_get_contents($f['tmp_name'],false,null,0,5);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);if($head!=='%PDF-'||$mime!=='application/pdf')throw new ApiException(422,'INVALID_PDF','Tệp phải có định dạng PDF.');
  $dir=__DIR__.'/../storage/ebooks';if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Cannot create ebook storage');$key=bin2hex(random_bytes(24)).'.pdf';$path=$dir.'/'.$key;
  try{return Input::transaction($this->db,function()use($id,$f,$key,$path){if(!Input::query($this->db,'SELECT ebook_id FROM ebook WHERE ebook_id=? FOR UPDATE',[$id])->fetch())throw new ApiException(404,'NOT_FOUND','Không có eBook.');$version=1+(int)Input::query($this->db,'SELECT MAX(file_version) FROM ebook_file WHERE ebook_id=?',[$id])->fetchColumn();if(!move_uploaded_file($f['tmp_name'],$path))throw new RuntimeException('Cannot store PDF');Input::query($this->db,'INSERT INTO ebook_file(ebook_id,file_version,storage_key,size_bytes,sha256) VALUES (?,?,?,?,?)',[$id,$version,$key,filesize($path),hash_file('sha256',$path)]);$fileId=(int)$this->db->lastInsertId();Input::audit($this->db,'ebook.upload',(string)$id);return ['ebook_file_id'=>$fileId,'file_version'=>$version];});}catch(Throwable $e){if(is_file($path))unlink($path);throw $e;}
 }
 public function borrow(array $d,array $u):array{
  $ids=$d['ebook_ids']??null;if(!is_array($ids)||!$ids||count($ids)>50)throw new ApiException(422,'INVALID_EBOOK','Chọn eBook cần mượn.');$ids=array_unique(array_map([Input::class,'id'],$ids));sort($ids);
  return Input::transaction($this->db,function()use($ids,$u){(new ReaderService($this->db))->ensureCard((int)$u['tai_khoan_id']);Input::query($this->db,'SELECT tai_khoan_id FROM tai_khoan WHERE tai_khoan_id=? FOR UPDATE',[$u['tai_khoan_id']]);$reader=Input::query($this->db,'SELECT doc_gia_id FROM doc_gia WHERE tai_khoan_id=?',[$u['tai_khoan_id']])->fetchColumn();if(!$reader||!(new ReaderService($this->db))->eligibility((int)$reader)['eligible'])throw new ApiException(422,'CARD_INELIGIBLE','Cần hồ sơ và thẻ còn hiệu lực để mượn eBook.');$p=(new SettingsService($this->db))->values();if(count($ids)>(int)$p['ebook_per_request'])throw new ApiException(422,'EBOOK_LIMIT','Vượt hạn mức eBook mỗi lần.');
   foreach($ids as $id){Input::query($this->db,'SELECT ebook_id FROM ebook WHERE ebook_id=? FOR UPDATE',[$id]);$e=$this->detail($id,$u);if(!$e['versions'])throw new ApiException(422,'NO_PDF','eBook chưa có PDF.');
    if(Input::query($this->db,'SELECT 1 FROM ebook_access WHERE ebook_id=? AND tai_khoan_id=? AND revoked_at IS NULL AND expires_at>UTC_TIMESTAMP()',[$id,$u['tai_khoan_id']])->fetch())continue;
    if(Input::query($this->db,"SELECT 1 FROM ebook_request WHERE ebook_id=? AND tai_khoan_id=? AND status='pending'",[$id,$u['tai_khoan_id']])->fetch())continue;
    Input::query($this->db,'INSERT INTO ebook_request(ebook_id,tai_khoan_id,requested_at) VALUES (?,?,UTC_TIMESTAMP())',[$id,$u['tai_khoan_id']]);
    Input::query($this->db,'INSERT INTO bookshelf_entry(tai_khoan_id,ebook_id,updated_at) VALUES (?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE updated_at=UTC_TIMESTAMP()',[$u['tai_khoan_id'],$id]);
   }return ['saved'=>true];});
 }
 public function review(int $id,bool $approve,array $actor):void{
  Input::transaction($this->db,function()use($id,$approve,$actor){$r=Input::query($this->db,'SELECT * FROM ebook_request WHERE request_id=?',[$id])->fetch();if(!$r)throw new ApiException(404,'NOT_FOUND','Không có yêu cầu eBook.');Input::query($this->db,'SELECT tai_khoan_id FROM tai_khoan WHERE tai_khoan_id=? FOR UPDATE',[$r['tai_khoan_id']]);$r=Input::query($this->db,'SELECT * FROM ebook_request WHERE request_id=? FOR UPDATE',[$id])->fetch();if($r['status']!=='pending')return;
   if($approve){$reader=Input::query($this->db,'SELECT doc_gia_id FROM doc_gia WHERE tai_khoan_id=?',[$r['tai_khoan_id']])->fetchColumn();if(!$reader||!(new ReaderService($this->db))->eligibility((int)$reader)['eligible'])throw new ApiException(422,'CARD_INELIGIBLE','Thẻ không còn đủ điều kiện.');$e=$this->detail((int)$r['ebook_id'],['tai_khoan_id'=>$r['tai_khoan_id'],'vai_tro'=>'user']);if(!$e['versions'])throw new ApiException(422,'NO_PDF','Chưa có PDF.');$p=(new SettingsService($this->db))->values();Input::query($this->db,'INSERT INTO ebook_access(ebook_id,tai_khoan_id,granted_at,expires_at) VALUES (?,?,UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? DAY)) ON DUPLICATE KEY UPDATE granted_at=VALUES(granted_at),expires_at=VALUES(expires_at),revoked_at=NULL',[$r['ebook_id'],$r['tai_khoan_id'],(int)$p['ebook_days']]);}
   Input::query($this->db,'UPDATE ebook_request SET status=?,reviewed_at=UTC_TIMESTAMP(),reviewed_by=? WHERE request_id=?',[$approve?'approved':'rejected',$actor['tai_khoan_id'],$id]);
   (new NotificationService($this->db))->createFromEvent(['recipient_id'=>(int)$r['tai_khoan_id'],'type'=>$approve?'borrow-approved':'borrow-rejected','source_id'=>'ebook-request:'.$r['request_id'],'title'=>$approve?'Đã duyệt mượn eBook':'Từ chối mượn eBook','message'=>'Mở Sách điện tử để xem quyền đọc.','reference'=>'eBook #'.$r['ebook_id']]);
  });
 }
 public function revoke(int $id,int $account):void{Input::query($this->db,'UPDATE ebook_access SET revoked_at=UTC_TIMESTAMP() WHERE ebook_id=? AND tai_khoan_id=? AND revoked_at IS NULL',[$id,$account]);Input::audit($this->db,'ebook.revoke',"$id:$account");}
 public function stream(int $file,array $u):void{
  $f=$this->permittedFile($file,$u);$path=__DIR__.'/../storage/ebooks/'.$f['storage_key'];if(!preg_match('/^[a-f0-9]{48}\.pdf$/D',$f['storage_key'])||!is_file($path))throw new ApiException(404,'PDF_MISSING','Không tìm thấy tệp PDF.');
  header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="ebook-'.$f['ebook_id'].'-v'.$f['file_version'].'.pdf"');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');header('Content-Length: '.filesize($path));session_write_close();readfile($path);
 }
}
