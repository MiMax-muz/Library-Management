<?php
declare(strict_types=1);
final class ReaderService {
 public function __construct(private PDO $db) {}
 public static function addYear(DateTimeImmutable $d): string {
  $y=(int)$d->format('Y')+1;$m=(int)$d->format('m');$f=$d->setDate($y,$m,1);
  return $f->setDate($y,$m,min((int)$d->format('d'),(int)$f->format('t')))->format('Y-m-d');
 }
 public function save(array $d,?int $id=null): array {
  $account=Input::id($d['tai_khoan_id']??null);$name=Input::text($d,'ho_ten',150);$birth=$d['ngay_sinh']??null;
  if($birth){Input::date($birth);if($birth>date('Y-m-d'))throw new ApiException(422,'INVALID_DATE','Ngày sinh trong tương lai.');}
  $phone=$d['dien_thoai']??null;$address=$d['dia_chi']??null;
  if($phone!==null&&$phone!==''&&(!is_string($phone)||!preg_match('/^\+?[0-9 ()-]{7,20}$/D',$phone)))throw new ApiException(422,'INVALID_PHONE','Số điện thoại không hợp lệ.');
  if($address!==null&&(!is_string($address)||mb_strlen($address)>255))throw new ApiException(422,'INVALID_ADDRESS','Địa chỉ tối đa 255 ký tự.');
  if(!Input::query($this->db,"SELECT tai_khoan_id FROM tai_khoan WHERE tai_khoan_id=? AND vai_tro='user'",[$account])->fetch())throw new ApiException(422,'INVALID_ACCOUNT','Cần tài khoản User.');
  $values=[$account,$name,$birth?:null,$d['dien_thoai']??null,$d['dia_chi']??null];
  if($id){
   $existing=Input::query($this->db,'SELECT tai_khoan_id FROM doc_gia WHERE doc_gia_id=?',[$id])->fetchColumn();
   if(!$existing)throw new ApiException(404,'NOT_FOUND','Không có độc giả.');
   if((int)$existing!==$account)throw new ApiException(422,'OWNER_IMMUTABLE','Không thể chuyển hồ sơ/thẻ sang tài khoản khác.');
   $values[]=$id;Input::query($this->db,'UPDATE doc_gia SET tai_khoan_id=?,ho_ten=?,ngay_sinh=?,dien_thoai=?,dia_chi=? WHERE doc_gia_id=?',$values);
  }else{Input::query($this->db,'INSERT INTO doc_gia(tai_khoan_id,ho_ten,ngay_sinh,dien_thoai,dia_chi) VALUES (?,?,?,?,?)',$values);$id=(int)$this->db->lastInsertId();}
  return ['doc_gia_id'=>$id];
 }
 public function card(int $id,array $d): array {
  return Input::transaction($this->db,function()use($id,$d){
   if(!Input::query($this->db,'SELECT doc_gia_id FROM doc_gia WHERE doc_gia_id=? FOR UPDATE',[$id])->fetch())throw new ApiException(404,'NOT_FOUND','Không có độc giả.');
   $card=Input::query($this->db,'SELECT * FROM the_doc_gia WHERE doc_gia_id=? FOR UPDATE',[$id])->fetch();
   $today=(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');$action=$d['action']??'issue';
   if($action==='issue'){
    if($card)throw new ApiException(409,'CARD_EXISTS','Đã có thẻ.');
    Input::query($this->db,'INSERT INTO the_doc_gia(doc_gia_id,ma_the,ngay_cap,ngay_het_han) VALUES (?,?,?,?)',[$id,Input::text($d,'ma_the',32),$today,self::addYear(Input::date($today))]);
   }else{
    if(!$card)throw new ApiException(404,'NOT_FOUND','Chưa cấp thẻ.');
    if($action==='renew')Input::query($this->db,'UPDATE the_doc_gia SET ngay_het_han=? WHERE doc_gia_id=?',[self::addYear(Input::date(max($today,$card['ngay_het_han']))),$id]);
    elseif(in_array($action,['lock','unlock'],true))Input::query($this->db,'UPDATE the_doc_gia SET trang_thai=? WHERE doc_gia_id=?',[$action==='lock'?'locked':'active',$id]);
    else throw new ApiException(422,'INVALID_ACTION','Thao tác không hợp lệ.');
   }
   Input::audit($this->db,'card.'.$action,(string)$id);
   return Input::query($this->db,'SELECT * FROM the_doc_gia WHERE doc_gia_id=?',[$id])->fetch();
  });
 }
 public function eligibility(int $id): array {
  $r=Input::query($this->db,'SELECT t.*,a.trang_thai account_status FROM doc_gia d JOIN tai_khoan a USING(tai_khoan_id) LEFT JOIN the_doc_gia t USING(doc_gia_id) WHERE d.doc_gia_id=?',[$id])->fetch();
  $today=(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
  $reason=!$r?'NO_PROFILE':(!$r['ma_the']?'NO_CARD':($r['account_status']!=='active'?'ACCOUNT_LOCKED':($r['trang_thai']!=='active'?'CARD_LOCKED':($r['ngay_cap']>$today||$r['ngay_het_han']<$today?'CARD_EXPIRED':null))));
  return ['eligible'=>$reason===null,'reason'=>$reason];
 }
}
