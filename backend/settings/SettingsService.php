<?php
declare(strict_types=1);
final class SettingsService {
 public const LIMITS=['borrow_per_request'=>[1,50],'borrow_total'=>[1,100],'borrow_days'=>[1,365],'renewal_limit'=>[0,10],'renewal_days'=>[1,90],'renewal_overdue_days'=>[0,30],'overdue_daily_vnd'=>[0,1000000],'ebook_per_request'=>[1,50],'ebook_days'=>[1,365]];
 public function __construct(private PDO $db){}
 public function values():array{return $this->db->query('SELECT khoa,gia_tri FROM cau_hinh')->fetchAll(PDO::FETCH_KEY_PAIR);}
 public function update(array $d):void{
  Input::transaction($this->db,function()use($d){
   $p=$this->db->query('SELECT khoa,gia_tri FROM cau_hinh FOR UPDATE')->fetchAll(PDO::FETCH_KEY_PAIR);
   foreach($d as $key=>$value){if(!isset(self::LIMITS[$key]))throw new ApiException(422,'INVALID_POLICY','Không hỗ trợ chính sách này.');[$min,$max]=self::LIMITS[$key];$v=filter_var($value,FILTER_VALIDATE_INT);if($v===false||$v<$min||$v>$max)throw new ApiException(422,'INVALID_POLICY',"$key phải từ $min đến $max.");$p[$key]=$v;}
   if((int)$p['borrow_per_request']>(int)$p['borrow_total'])throw new ApiException(422,'INVALID_POLICY','Hạn mức mỗi lần không vượt tổng hạn mức.');
   foreach($d as $k=>$v)Input::query($this->db,'UPDATE cau_hinh SET gia_tri=? WHERE khoa=?',[(string)$v,$k]);
   Input::audit($this->db,'settings.update','borrowing');
  });
 }
}
