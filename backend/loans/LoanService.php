<?php
declare(strict_types=1);
require_once __DIR__.'/../readers/ReaderService.php';
require_once __DIR__.'/../settings/SettingsService.php';
require_once __DIR__.'/../fines/FineService.php';
require_once __DIR__.'/../notifications/NotificationService.php';
final class LoanService {
 public function __construct(private PDO $db){}
 private function today():string{return (new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');}
 private function lockReader(int $account):array{
  Input::query($this->db,'SELECT tai_khoan_id FROM tai_khoan WHERE tai_khoan_id=? FOR UPDATE',[$account]);
  $r=Input::query($this->db,'SELECT * FROM doc_gia WHERE tai_khoan_id=? FOR UPDATE',[$account])->fetch();
  if(!$r)throw new ApiException(422,'NO_PROFILE','Cần tạo hồ sơ độc giả trước.');return $r;
 }
 private function eligible(int $reader):void{
  $result=(new ReaderService($this->db))->eligibility($reader);
  if(!$result['eligible'])throw new ApiException(422,'CARD_INELIGIBLE','Thẻ chưa đủ điều kiện: '.$result['reason']);
 }
 private function owner(int $id,bool $item=false):array{
  $sql=$item?'SELECT r.*,d.tai_khoan_id,i.loan_item_id FROM loan_item i JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id) JOIN doc_gia d USING(doc_gia_id) WHERE i.loan_item_id=?':'SELECT r.*,d.tai_khoan_id FROM borrow_request r JOIN doc_gia d USING(doc_gia_id) WHERE request_id=?';
  $r=Input::query($this->db,$sql,[$id])->fetch();if(!$r)throw new ApiException(404,'NOT_FOUND','Không tìm thấy phiếu.');return $r;
 }
 private function access(array $owner,array $actor):void{if($actor['vai_tro']==='user'&&(int)$owner['tai_khoan_id']!==(int)$actor['tai_khoan_id'])throw new ApiException(404,'NOT_FOUND','Không tìm thấy phiếu.');}
 private function notify(array $owner,string $type,string $source,string $title):void{
  (new NotificationService($this->db))->createFromEvent(['recipient_id'=>(int)$owner['tai_khoan_id'],'type'=>$type,'source_id'=>$source,'title'=>$title,'message'=>$title.'. Mở mục Mượn / trả sách để xem chi tiết.','reference'=>'Yêu cầu #'.$owner['request_id']]);
 }
 public function requests(array $actor):array{
  $rows=Input::query($this->db,'SELECT r.*,d.ho_ten,d.tai_khoan_id FROM borrow_request r JOIN doc_gia d USING(doc_gia_id)'.($actor['vai_tro']==='user'?' WHERE d.tai_khoan_id=?':'').' ORDER BY request_id DESC',$actor['vai_tro']==='user'?[$actor['tai_khoan_id']]:[])->fetchAll();
  foreach($rows as &$r)$r['copies']=Input::query($this->db,'SELECT b.ban_sach_id,b.ma_ban_sach,s.ten_sach FROM borrow_request_item x JOIN ban_sach b USING(ban_sach_id) JOIN sach s USING(sach_id) WHERE request_id=?',[$r['request_id']])->fetchAll();return $rows;
 }
 public function items(array $actor,array $filter=[]):array{
  $where=[];$args=[];if($actor['vai_tro']==='user'){$where[]='d.tai_khoan_id=?';$args[]=$actor['tai_khoan_id'];}
  $state=$filter['status']??'all';if($state==='overdue'){$where[]="i.status='borrowed' AND i.due_date<?";$args[]=$this->today();}elseif($state!=='all'){if(!in_array($state,['borrowed','returned','lost','damaged'],true))throw new ApiException(422,'INVALID_STATUS','Trạng thái không hợp lệ.');$where[]='i.status=?';$args[]=$state;}
  foreach(['from'=>'>=','to'=>'<='] as $key=>$op)if(!empty($filter[$key])){$date=Input::text($filter,$key,10);Input::date($date);$where[]="DATE(DATE_ADD(l.handed_over_at,INTERVAL 7 HOUR)) $op ?";$args[]=$date;}
  return Input::query($this->db,"SELECT i.*,l.handed_over_at,r.request_id,d.ho_ten,d.tai_khoan_id,b.ma_ban_sach,s.ten_sach,s.gia_bia, f.total fine_total,(SELECT renewal_id FROM renewal_request z WHERE z.loan_item_id=i.loan_item_id AND z.status='pending') pending_renewal FROM loan_item i JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id) JOIN doc_gia d USING(doc_gia_id) JOIN ban_sach b USING(ban_sach_id) JOIN sach s USING(sach_id) LEFT JOIN fine_record f USING(loan_item_id)".($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY i.loan_item_id DESC',$args)->fetchAll();
 }
 public function request(array $d,array $actor):array{
  $ids=$d['copy_ids']??null;if(!is_array($ids)||!$ids||count($ids)>50)throw new ApiException(422,'INVALID_COPIES','Chọn ít nhất một bản sách.');$ids=array_map([Input::class,'id'],$ids);if(count(array_unique($ids))!==count($ids))throw new ApiException(422,'DUPLICATE_COPY','Không chọn trùng bản sách.');sort($ids);
  return Input::transaction($this->db,function()use($ids,$actor){
   $reader=$this->lockReader((int)$actor['tai_khoan_id']);$this->eligible((int)$reader['doc_gia_id']);$p=(new SettingsService($this->db))->values();
   if(count($ids)>(int)$p['borrow_per_request'])throw new ApiException(422,'BORROW_LIMIT','Vượt số cuốn mỗi yêu cầu.');
   $active=(int)Input::query($this->db,"SELECT COUNT(*) FROM loan_item i JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id) WHERE doc_gia_id=? AND i.status='borrowed'",[$reader['doc_gia_id']])->fetchColumn();
   if($active+count($ids)>(int)$p['borrow_total'])throw new ApiException(422,'BORROW_LIMIT','Vượt tổng hạn mức đang mượn.');
   foreach($ids as $id){$b=Input::query($this->db,'SELECT b.*,s.dang_luu_hanh book_active FROM ban_sach b JOIN sach s USING(sach_id) WHERE ban_sach_id=? FOR UPDATE',[$id])->fetch();if(!$b||!$b['book_active']||!$b['dang_luu_hanh']||$b['tinh_trang']!=='available')throw new ApiException(409,'COPY_UNAVAILABLE','Có bản sách không khả dụng.');
    if(Input::query($this->db,"SELECT 1 FROM borrow_request_item x JOIN borrow_request r USING(request_id) WHERE r.doc_gia_id=? AND x.ban_sach_id=? AND r.status IN ('pending','approved')",[$reader['doc_gia_id'],$id])->fetch())throw new ApiException(409,'DUPLICATE_REQUEST','Bạn đã có yêu cầu cho bản sách này.');}
   Input::query($this->db,'INSERT INTO borrow_request(doc_gia_id) VALUES (?)',[$reader['doc_gia_id']]);$id=(int)$this->db->lastInsertId();foreach($ids as $copy)Input::query($this->db,'INSERT INTO borrow_request_item VALUES (?,?)',[$id,$copy]);Input::audit($this->db,'loan.request',(string)$id);return ['request_id'=>$id];
  });
 }
 public function decide(int $id,string $action,array $actor):array{
  if(!in_array($action,['approve','reject','cancel','handover'],true))throw new ApiException(422,'INVALID_ACTION','Thao tác không hợp lệ.');
  if($action!=='cancel'&&$actor['vai_tro']==='user')throw new ApiException(403,'FORBIDDEN','Cần quyền nhân viên.');
  return Input::transaction($this->db,function()use($id,$action,$actor){
   $owner=$this->owner($id);$this->access($owner,$actor);$reader=$this->lockReader((int)$owner['tai_khoan_id']);
   $r=Input::query($this->db,'SELECT * FROM borrow_request WHERE request_id=? FOR UPDATE',[$id])->fetch();
   if($action==='handover'){
    if($r['status']==='handed_over')return ['saved'=>true,'already_done'=>true];
    if($r['status']!=='approved')throw new ApiException(409,'INVALID_STATE','Chỉ bàn giao yêu cầu đã duyệt.');
    $this->eligible((int)$reader['doc_gia_id']);$p=(new SettingsService($this->db))->values();$copies=Input::query($this->db,'SELECT ban_sach_id FROM borrow_request_item WHERE request_id=? ORDER BY ban_sach_id',[$id])->fetchAll(PDO::FETCH_COLUMN);
    $count=(int)Input::query($this->db,"SELECT COUNT(*) FROM loan_item i JOIN loan l USING(loan_id) JOIN borrow_request r USING(request_id) WHERE doc_gia_id=? AND i.status='borrowed'",[$reader['doc_gia_id']])->fetchColumn();
    if(count($copies)>(int)$p['borrow_per_request']||$count+count($copies)>(int)$p['borrow_total'])throw new ApiException(422,'BORROW_LIMIT','Vượt hạn mức mượn.');
    Input::query($this->db,'INSERT INTO loan(request_id,handed_over_by,handed_over_at) VALUES (?,?,UTC_TIMESTAMP())',[$id,$actor['tai_khoan_id']]);$loan=(int)$this->db->lastInsertId();$due=Input::date($this->today())->modify('+'.(int)$p['borrow_days'].' days')->format('Y-m-d');
    foreach($copies as $copy){$b=Input::query($this->db,'SELECT b.*,s.dang_luu_hanh book_active FROM ban_sach b JOIN sach s USING(sach_id) WHERE ban_sach_id=? FOR UPDATE',[$copy])->fetch();if(!$b||!$b['book_active']||!$b['dang_luu_hanh']||$b['tinh_trang']!=='available')throw new ApiException(409,'COPY_UNAVAILABLE','Bản sách không còn khả dụng; chưa bàn giao.');Input::query($this->db,"UPDATE ban_sach SET tinh_trang='borrowed' WHERE ban_sach_id=?",[$copy]);Input::query($this->db,'INSERT INTO loan_item(loan_id,ban_sach_id,due_date) VALUES (?,?,?)',[$loan,$copy,$due]);}
    Input::query($this->db,"UPDATE borrow_request SET status='handed_over' WHERE request_id=?",[$id]);
   }else{
    $status=['approve'=>'approved','reject'=>'rejected','cancel'=>'cancelled'][$action];if($r['status']===$status)return ['saved'=>true,'already_done'=>true];
    if(!in_array($r['status'],$action==='cancel'?['pending','approved']:['pending'],true))throw new ApiException(409,'INVALID_STATE','Yêu cầu đã được xử lý.');
    if($action==='approve')$this->eligible((int)$reader['doc_gia_id']);
    Input::query($this->db,'UPDATE borrow_request SET status=?,reviewed_by=?,reviewed_at=UTC_TIMESTAMP() WHERE request_id=?',[$status,$actor['tai_khoan_id'],$id]);
    if($action!=='cancel')$this->notify($owner,$action==='approve'?'borrow-approved':'borrow-rejected','request:'.$id,$action==='approve'?'Yêu cầu mượn đã được duyệt':'Yêu cầu mượn bị từ chối');
   }
   Input::audit($this->db,'loan.'.$action,(string)$id);return ['saved'=>true];
  });
 }
 public function returnItem(int $id,array $d,array $actor):array{
  return Input::transaction($this->db,function()use($id,$d,$actor){
   $o=$this->owner($id,true);$this->lockReader((int)$o['tai_khoan_id']);$i=Input::query($this->db,'SELECT * FROM loan_item WHERE loan_item_id=? FOR UPDATE',[$id])->fetch();
   if($i['status']!=='borrowed')return ['saved'=>true,'already_done'=>true];
   $v=$d['violation']??'overdue';if(!in_array($v,['overdue','lost','damaged','repair'],true))throw new ApiException(422,'INVALID_VIOLATION','Vi phạm không hợp lệ.');
   $b=Input::query($this->db,'SELECT b.*,s.gia_bia FROM ban_sach b JOIN sach s USING(sach_id) WHERE ban_sach_id=? FOR UPDATE',[$i['ban_sach_id']])->fetch();
   $p=(new SettingsService($this->db))->values();$calc=FineService::calculate(['due_date'=>$i['due_date'],'return_date'=>$this->today(),'count'=>1,'violation'=>$v,'cover_price'=>$b['gia_bia'],'repair_cost'=>$d['repair_cost']??null,'repair_evidence'=>$d['repair_evidence']??null],$p);$calc['preview_only']=false;
   $calc['basis']=['cover_price'=>$b['gia_bia'],'due_date'=>$i['due_date'],'confirmed_date'=>$this->today(),'repair_cost'=>$d['repair_cost']??null,'repair_evidence'=>$d['repair_evidence']??null];
   $status=match($v){'lost'=>'lost','damaged','repair'=>'damaged',default=>'returned'};
   Input::query($this->db,'UPDATE loan_item SET status=?,returned_at=UTC_TIMESTAMP() WHERE loan_item_id=?',[$status,$id]);
   Input::query($this->db,'UPDATE ban_sach SET tinh_trang=? WHERE ban_sach_id=?',[$status==='returned'?'available':$status,$i['ban_sach_id']]);
   Input::query($this->db,'INSERT INTO fine_record(loan_item_id,violation,calculation_json,total,evidence,confirmed_by,created_at) VALUES (?,?,?,?,?,?,UTC_TIMESTAMP())',[$id,$v,json_encode($calc,JSON_THROW_ON_ERROR),$calc['total'],$d['repair_evidence']??null,$actor['tai_khoan_id']]);
   Input::query($this->db,"UPDATE renewal_request SET status='rejected',reviewed_by=?,reviewed_at=UTC_TIMESTAMP() WHERE loan_item_id=? AND status='pending'",[$actor['tai_khoan_id'],$id]);
   if(!Input::query($this->db,"SELECT 1 FROM loan_item WHERE loan_id=? AND status='borrowed'",[$i['loan_id']])->fetch())Input::query($this->db,'UPDATE loan SET closed_at=UTC_TIMESTAMP() WHERE loan_id=?',[$i['loan_id']]);
   Input::audit($this->db,'loan.return',(string)$id);return ['saved'=>true,'fine'=>$calc];
  });
 }
 private function renewalEligible(array $i,array $o):array{
  $this->eligible((int)$o['doc_gia_id']);$p=(new SettingsService($this->db))->values();$late=(int)Input::date($i['due_date'])->diff(Input::date($this->today()))->format('%r%a');
  if($i['status']!=='borrowed'||$i['renewal_count']>=(int)$p['renewal_limit']||$late>(int)$p['renewal_overdue_days'])throw new ApiException(422,'RENEWAL_INELIGIBLE','Không đủ điều kiện gia hạn (trạng thái, số lần hoặc quá hạn).');return $p;
 }
 public function renew(int $id,array $actor):array{
  return Input::transaction($this->db,function()use($id,$actor){$o=$this->owner($id,true);$this->access($o,$actor);$this->lockReader((int)$o['tai_khoan_id']);$i=Input::query($this->db,'SELECT * FROM loan_item WHERE loan_item_id=? FOR UPDATE',[$id])->fetch();$this->renewalEligible($i,$o);
   $existing=Input::query($this->db,"SELECT renewal_id FROM renewal_request WHERE loan_item_id=? AND status='pending'",[$id])->fetchColumn();if($existing)return ['renewal_id'=>$existing];Input::query($this->db,'INSERT INTO renewal_request(loan_item_id,requested_at) VALUES (?,UTC_TIMESTAMP())',[$id]);return ['renewal_id'=>(int)$this->db->lastInsertId()];});
 }
 public function reviewRenewal(int $id,bool $approve,array $actor):array{
  return Input::transaction($this->db,function()use($id,$approve,$actor){$z=Input::query($this->db,'SELECT * FROM renewal_request WHERE renewal_id=?',[$id])->fetch();if(!$z)throw new ApiException(404,'NOT_FOUND','Không có yêu cầu gia hạn.');$o=$this->owner((int)$z['loan_item_id'],true);$this->lockReader((int)$o['tai_khoan_id']);$i=Input::query($this->db,'SELECT * FROM loan_item WHERE loan_item_id=? FOR UPDATE',[$z['loan_item_id']])->fetch();$z=Input::query($this->db,'SELECT * FROM renewal_request WHERE renewal_id=? FOR UPDATE',[$id])->fetch();if($z['status']!=='pending')return ['saved'=>true,'already_done'=>true];
   if($approve){$p=$this->renewalEligible($i,$o);$due=Input::date($i['due_date'])->modify('+'.(int)$p['renewal_days'].' days')->format('Y-m-d');Input::query($this->db,'UPDATE loan_item SET due_date=?,renewal_count=renewal_count+1 WHERE loan_item_id=?',[$due,$i['loan_item_id']]);}
   Input::query($this->db,'UPDATE renewal_request SET status=?,reviewed_by=?,reviewed_at=UTC_TIMESTAMP() WHERE renewal_id=?',[$approve?'approved':'rejected',$actor['tai_khoan_id'],$id]);$this->notify($o,$approve?'renewal-approved':'renewal-rejected','renewal:'.$id,$approve?'Yêu cầu gia hạn đã được duyệt':'Yêu cầu gia hạn bị từ chối');return ['saved'=>true];});
 }
}
