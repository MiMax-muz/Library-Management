<?php
require_once __DIR__.'/../readers/ReaderService.php';
return static function(Router $r,array $c):void{
 $ctx=function()use($c){$p=Database::connection($c['db']);return [$p,Auth::requireRoles($p,['user'])];};
 $r->add('GET','/api/profile',function()use($ctx){[$p,$u]=$ctx();$d=Input::query($p,'SELECT d.*,t.ma_the,t.ngay_cap,t.ngay_het_han,t.trang_thai card_status FROM doc_gia d LEFT JOIN the_doc_gia t USING(doc_gia_id) WHERE d.tai_khoan_id=?',[$u['tai_khoan_id']])->fetch();$name=Input::query($p,'SELECT ho_ten FROM tai_khoan WHERE tai_khoan_id=?',[$u['tai_khoan_id']])->fetchColumn();Response::json(['profile'=>$d?:['ho_ten'=>$name],'eligibility'=>$d?(new ReaderService($p))->eligibility((int)$d['doc_gia_id']):['eligible'=>false,'reason'=>'NO_PROFILE']]);});
 $r->add('PUT','/api/profile',function()use($ctx){[$p,$u]=$ctx();$d=Input::body();$d['tai_khoan_id']=$u['tai_khoan_id'];$result=Input::transaction($p,function()use($p,$u,$d){Input::query($p,'SELECT tai_khoan_id FROM tai_khoan WHERE tai_khoan_id=? FOR UPDATE',[$u['tai_khoan_id']]);$id=Input::query($p,'SELECT doc_gia_id FROM doc_gia WHERE tai_khoan_id=?',[$u['tai_khoan_id']])->fetchColumn();return (new ReaderService($p))->save($d,$id?(int)$id:null);});Response::json($result);});
};
