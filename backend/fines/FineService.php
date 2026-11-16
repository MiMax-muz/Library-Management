<?php
declare(strict_types=1);
final class FineService {
 public static function calculate(array $d,array $p): array {
  $due=Input::date(Input::text($d,'due_date',10));$end=Input::date(Input::text($d,'return_date',10));
  $days=max(0,(int)$due->diff($end)->format('%r%a'));$count=Input::id($d['count']??1);
  $rate=filter_var($p['overdue_daily_vnd']??null,FILTER_VALIDATE_INT);
  if($rate===false||$rate<0||$rate>1000000000||$count>1000)throw new ApiException(422,'INVALID_POLICY','Đơn giá/số cuốn không hợp lệ.');
  $v=$d['violation']??'overdue';$comp=0;
  if(!in_array($v,['overdue','lost','damaged','repair'],true))throw new ApiException(422,'INVALID_VIOLATION','Vi phạm không hợp lệ.');
  if($v!=='overdue'){
   $price=filter_var($d['cover_price']??null,FILTER_VALIDATE_INT);
   if($price===false||$price<0||$price>999999999999)throw new ApiException(422,'PRICE_REQUIRED','Cần giá bìa hợp lệ.');
   $comp=$price;
   if($v==='repair'){
    $repair=filter_var($d['repair_cost']??null,FILTER_VALIDATE_INT);
    if($repair===false||$repair<0||$repair>$price)throw new ApiException(422,'INVALID_REPAIR','Chi phí sửa phải từ 0 đến giá bìa.');
    Input::text($d,'repair_evidence',1000);$comp=$repair;
   }
  }
  $late=$days*$count*$rate;$comp*=$count;
  return ['days'=>$days,'count'=>$count,'overdue'=>$late,'compensation'=>$comp,'total'=>$late+$comp,'policy'=>$p,'preview_only'=>true];
 }
}
