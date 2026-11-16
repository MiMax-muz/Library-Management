<?php
declare(strict_types=1);
require_once __DIR__.'/BookService.php';
return static function(Router $r,array $c): void {
 $db=function(bool $write=false)use($c){$p=Database::connection($c['db']);Auth::requireRoles($p,$write?['admin','librarian']:[]);return $p;};
 $r->add('GET','/api/books',function()use($db){Response::json((new BookService($db()))->listing($_GET));});
 $r->add('GET','/api/books/:id',function($a)use($db){Response::json((new BookService($db()))->detail(Input::id($a['id'])));});
 $r->add('POST','/api/books',function()use($db){Response::json((new BookService($db(true)))->save(Input::body()),201);});
 $r->add('PUT','/api/books/:id',function($a)use($db){Response::json((new BookService($db(true)))->save(Input::body(),Input::id($a['id'])));});
 $r->add('DELETE','/api/books/:id',function($a)use($db){(new BookService($db(true)))->delete(Input::id($a['id']));Response::json(null,204);});
 $r->add('POST','/api/books/:id/copies',function($a)use($db){Response::json((new BookService($db(true)))->copy(Input::id($a['id']),Input::body()),201);});
 $r->add('PUT','/api/books/:id/copies/:copy',function($a)use($db){Response::json((new BookService($db(true)))->copy(Input::id($a['id']),Input::body(),Input::id($a['copy'])));});
 $r->add('DELETE','/api/copies/:id',function($a)use($db){(new BookService($db(true)))->delete(Input::id($a['id']),true);Response::json(null,204);});
 $tables=['categories'=>['the_loai','the_loai_id',['ma_the_loai','ten_the_loai']],'authors'=>['tac_gia','tac_gia_id',['ho_ten']],'publishers'=>['nha_xuat_ban','nha_xuat_ban_id',['ten_nha_xuat_ban']],'locations'=>['vi_tri_luu_tru','vi_tri_id',['the_loai_id','ma_vi_tri','mo_ta']]];
 foreach($tables as $route=>[$table,$key,$fields]){
  $r->add('GET','/api/'.$route,function()use($db,$table){Response::json($db()->query('SELECT * FROM '.$table)->fetchAll());});
  $save=function(?int $id=null)use($db,$table,$key,$fields){$p=$db(true);$d=Input::body();$values=[];foreach($fields as $f)$values[]=$f==='the_loai_id'?Input::id($d[$f]??null):Input::text($d,$f,$f==='ma_the_loai'?20:($f==='ma_vi_tri'?40:100));
   if($table==='vi_tri_luu_tru'){$code=Input::query($p,'SELECT ma_the_loai FROM the_loai WHERE the_loai_id=?',[$values[0]])->fetchColumn();if(!$code||!str_starts_with($values[1],$code.'-')||strlen($values[1])<=strlen($code)+1)throw new ApiException(422,'INVALID_LOCATION','Mã vị trí phải có dạng MA_THE_LOAI-KE.');}
   if($id){
    $old=Input::query($p,'SELECT * FROM '.$table.' WHERE '.$key.'=?',[$id])->fetch();
    if(!$old)throw new ApiException(404,'NOT_FOUND','Không có mục danh mục.');
    if($table==='the_loai' && $old['ma_the_loai']!==$values[0] && Input::query($p,'SELECT vi_tri_id FROM vi_tri_luu_tru WHERE the_loai_id=? LIMIT 1',[$id])->fetch())throw new ApiException(409,'CATEGORY_IN_USE','Không đổi mã thể loại đã có vị trí.');
    $values[]=$id;Input::query($p,'UPDATE '.$table.' SET '.implode(',',array_map(fn($f)=>$f.'=?',$fields)).' WHERE '.$key.'=?',$values);
   }
   else Input::query($p,'INSERT INTO '.$table.'('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')',$values);
   Response::json([$key=>$id??(int)$p->lastInsertId()],$id?200:201);
  };
  $r->add('POST','/api/'.$route,fn()=>$save());
  $r->add('PUT','/api/'.$route.'/:id',fn($a)=>$save(Input::id($a['id'])));
  $r->add('DELETE','/api/'.$route.'/:id',function($a)use($db,$table,$key){Input::query($db(true),'DELETE FROM '.$table.' WHERE '.$key.'=?',[Input::id($a['id'])]);Response::json(null,204);});
 }
};
