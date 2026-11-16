<?php
declare(strict_types=1);
final class BookService {
 public function __construct(private PDO $db) {}
 public function listing(array $filter): array {
  $sql="SELECT s.*,t.ten_the_loai,(SELECT COUNT(*) FROM ban_sach b WHERE b.sach_id=s.sach_id AND b.tinh_trang='available' AND b.dang_luu_hanh=1 AND s.dang_luu_hanh=1) available FROM sach s JOIN the_loai t USING(the_loai_id) WHERE (s.ten_sach LIKE ? OR s.ma_sach LIKE ? OR s.isbn LIKE ?)";
  $q='%'.($filter['q']??'').'%';$args=[$q,$q,$q];
  if(!empty($filter['category'])){$sql.=' AND s.the_loai_id=?';$args[]=Input::id($filter['category']);}
  return Input::query($this->db,$sql.' ORDER BY s.sach_id DESC LIMIT 500',$args)->fetchAll();
 }
 public function detail(int $id): array {
  $book=Input::query($this->db,'SELECT * FROM sach WHERE sach_id=?',[$id])->fetch();
  if(!$book)throw new ApiException(404,'NOT_FOUND','Không có sách.');
  $book['copies']=Input::query($this->db,'SELECT b.*,v.ma_vi_tri FROM ban_sach b JOIN vi_tri_luu_tru v USING(vi_tri_id) WHERE b.sach_id=?',[$id])->fetchAll();
  $book['authors']=Input::query($this->db,'SELECT t.* FROM tac_gia t JOIN sach_tac_gia s USING(tac_gia_id) WHERE s.sach_id=?',[$id])->fetchAll();
  return $book;
 }
 public function save(array $d,?int $id=null): array {
  return Input::transaction($this->db,function()use($d,$id){
   $category=Input::id($d['the_loai_id']??null);$active=$this->flag($d['dang_luu_hanh']??1);
   $price=$d['gia_bia']??null;
   if($price!==null && (filter_var($price,FILTER_VALIDATE_INT)===false||$price<0||$price>999999999999))throw new ApiException(422,'INVALID_PRICE','Giá bìa không hợp lệ.');
   $year=$d['nam_xuat_ban']??null;if($year!==null && (filter_var($year,FILTER_VALIDATE_INT)===false||$year<1||$year>9999))throw new ApiException(422,'INVALID_YEAR','Năm không hợp lệ.');
   if($id){
    $old=Input::query($this->db,'SELECT * FROM sach WHERE sach_id=? FOR UPDATE',[$id])->fetch();
    if(!$old)throw new ApiException(404,'NOT_FOUND','Không có sách.');
    $copies=Input::query($this->db,'SELECT * FROM ban_sach WHERE sach_id=? FOR UPDATE',[$id])->fetchAll();
    if($copies && (int)$old['the_loai_id']!==$category)throw new ApiException(409,'CATEGORY_IN_USE','Không đổi thể loại sách đã có bản.');
    foreach($copies as $copy)if(!$active && $copy['tinh_trang']==='borrowed')throw new ApiException(409,'COPY_BORROWED','Sách đang mượn không thể ngừng lưu hành.');
   }
   $values=[Input::text($d,'ma_sach',32),Input::text($d,'ten_sach',255),$category,($d['isbn']??'')?:null,$d['nha_xuat_ban_id']??null,$year,$price,$d['mo_ta']??null,$active];
   if($id){$values[]=$id;Input::query($this->db,'UPDATE sach SET ma_sach=?,ten_sach=?,the_loai_id=?,isbn=?,nha_xuat_ban_id=?,nam_xuat_ban=?,gia_bia=?,mo_ta=?,dang_luu_hanh=? WHERE sach_id=?',$values);}
   else{Input::query($this->db,'INSERT INTO sach(ma_sach,ten_sach,the_loai_id,isbn,nha_xuat_ban_id,nam_xuat_ban,gia_bia,mo_ta,dang_luu_hanh) VALUES (?,?,?,?,?,?,?,?,?)',$values);$id=(int)$this->db->lastInsertId();}
   if(isset($d['author_ids'])){
    if(!is_array($d['author_ids']))throw new ApiException(422,'INVALID_AUTHORS','Danh sách tác giả không hợp lệ.');
    Input::query($this->db,'DELETE FROM sach_tac_gia WHERE sach_id=?',[$id]);
    foreach(array_unique($d['author_ids']) as $author)Input::query($this->db,'INSERT INTO sach_tac_gia VALUES (?,?)',[$id,Input::id($author)]);
   }
   Input::audit($this->db,'book.save',(string)$id);return ['sach_id'=>$id];
  });
 }
 private function flag(mixed $v): int {if(!in_array($v,[0,1,'0','1',true,false],true))throw new ApiException(422,'INVALID_FLAG','Trạng thái không hợp lệ.');return (int)$v;}
 public function copy(int $book,array $d,?int $id=null): array {
  return Input::transaction($this->db,function()use($book,$d,$id){
   $s=Input::query($this->db,'SELECT * FROM sach WHERE sach_id=? FOR UPDATE',[$book])->fetch();
   if(!$s)throw new ApiException(404,'NOT_FOUND','Không có sách.');
   $status=$d['tinh_trang']??'available';$active=$this->flag($d['dang_luu_hanh']??1);
   if(!in_array($status,['available','damaged','lost'],true))throw new ApiException(422,'INVALID_STATUS','Trạng thái borrowed chỉ do nghiệp vụ bàn giao cập nhật.');
   if($id){$old=Input::query($this->db,'SELECT * FROM ban_sach WHERE ban_sach_id=? AND sach_id=? FOR UPDATE',[$id,$book])->fetch();if(!$old)throw new ApiException(404,'NOT_FOUND','Không có bản sách.');if($old['tinh_trang']==='borrowed')throw new ApiException(409,'COPY_BORROWED','Không sửa bản đang mượn.');}
   $location=Input::id($d['vi_tri_id']??null);
   if(!Input::query($this->db,'SELECT vi_tri_id FROM vi_tri_luu_tru WHERE vi_tri_id=? AND the_loai_id=?',[$location,$s['the_loai_id']])->fetch())throw new ApiException(422,'LOCATION_MISMATCH','Vị trí phải cùng thể loại.');
   $v=[Input::text($d,'ma_ban_sach',40),$book,$s['the_loai_id'],$location,$status,$active];
   if($id){$v[]=$id;Input::query($this->db,'UPDATE ban_sach SET ma_ban_sach=?,sach_id=?,the_loai_id=?,vi_tri_id=?,tinh_trang=?,dang_luu_hanh=? WHERE ban_sach_id=?',$v);}
   else{Input::query($this->db,'INSERT INTO ban_sach(ma_ban_sach,sach_id,the_loai_id,vi_tri_id,tinh_trang,dang_luu_hanh) VALUES (?,?,?,?,?,?)',$v);$id=(int)$this->db->lastInsertId();}
   Input::audit($this->db,'copy.save',(string)$id);return ['ban_sach_id'=>$id];
  });
 }
 public function delete(int $id,bool $copy=false): void {
  Input::transaction($this->db,function()use($id,$copy){
   if($copy){
    $row=Input::query($this->db,'SELECT * FROM ban_sach WHERE ban_sach_id=? FOR UPDATE',[$id])->fetch();
    if(!$row)throw new ApiException(404,'NOT_FOUND','Không có bản sách.');
    if($row['tinh_trang']==='borrowed')throw new ApiException(409,'COPY_BORROWED','Không xóa bản đang mượn.');
    Input::query($this->db,'DELETE FROM ban_sach WHERE ban_sach_id=?',[$id]);
   }else{
    if(!Input::query($this->db,'SELECT sach_id FROM sach WHERE sach_id=? FOR UPDATE',[$id])->fetch())throw new ApiException(404,'NOT_FOUND','Không có sách.');
    if(Input::query($this->db,'SELECT ban_sach_id FROM ban_sach WHERE sach_id=? LIMIT 1',[$id])->fetch())throw new ApiException(409,'COPIES_EXIST','Xử lý các bản sách trước khi xóa đầu sách.');
    Input::query($this->db,'DELETE FROM sach_tac_gia WHERE sach_id=?',[$id]);Input::query($this->db,'DELETE FROM sach WHERE sach_id=?',[$id]);
   }
   Input::audit($this->db,$copy?'copy.delete':'book.delete',(string)$id);
  });
 }
 // Caller must own a transaction spanning eligibility, loan creation and stock.
 public function claimCopy(int $id): bool {
  if(!$this->db->inTransaction())throw new LogicException('claimCopy requires transaction');
  return Input::query($this->db,"UPDATE ban_sach b JOIN sach s USING(sach_id) SET b.tinh_trang='borrowed' WHERE b.ban_sach_id=? AND b.tinh_trang='available' AND b.dang_luu_hanh=1 AND s.dang_luu_hanh=1",[$id])->rowCount()===1;
 }
}
