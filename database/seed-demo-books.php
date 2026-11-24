<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$c=require __DIR__.'/../backend/bootstrap.php';$db=Database::connection($c['db']);
if(!Input::query($db,"SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sach' AND COLUMN_NAME='cover_path'")->fetch())throw new RuntimeException('Run migration 006_book_covers.php first.');
$groups=[
 ['DEMO-CNTT','Công nghệ thông tin — mẫu',['Nhập môn lập trình Python','Thiết kế cơ sở dữ liệu','Xây dựng ứng dụng Web','Cấu trúc dữ liệu và giải thuật']],
 ['DEMO-VH','Văn học — mẫu',['Những ngày bên cửa sổ','Chuyến tàu qua mùa hạ','Thư gửi thành phố nhỏ','Khu vườn của ký ức']],
 ['DEMO-KT','Kinh tế — mẫu',['Kinh tế học trong đời sống','Tài chính cá nhân căn bản','Quản trị dự án thực hành','Khởi nghiệp từ ý tưởng']],
 ['DEMO-KH','Khoa học — mẫu',['Khám phá hệ Mặt Trời','Toán học quanh ta','Hành trình của năng lượng','Thế giới tự nhiên kỳ thú']],
 ['DEMO-KN','Kỹ năng — mẫu',['Học tập chủ động','Nghệ thuật làm việc nhóm','Tư duy phản biện căn bản','Quản lý thời gian hiệu quả']]
];
$result=Input::transaction($db,function()use($db,$groups){
 $created=0;$copies=0;
 $author=Input::query($db,'SELECT tac_gia_id FROM tac_gia WHERE ho_ten=? LIMIT 1',['Nhóm biên soạn UniLib (dữ liệu mẫu)'])->fetchColumn();
 if(!$author){Input::query($db,'INSERT INTO tac_gia(ho_ten) VALUES (?)',['Nhóm biên soạn UniLib (dữ liệu mẫu)']);$author=$db->lastInsertId();}
 Input::query($db,'INSERT IGNORE INTO nha_xuat_ban(ten_nha_xuat_ban) VALUES (?)',['UniLib — nhà xuất bản mẫu']);$publisher=Input::query($db,'SELECT nha_xuat_ban_id FROM nha_xuat_ban WHERE ten_nha_xuat_ban=?',['UniLib — nhà xuất bản mẫu'])->fetchColumn();
 $n=0;
 foreach($groups as [$code,$category,$titles]){
  Input::query($db,'INSERT IGNORE INTO the_loai(ma_the_loai,ten_the_loai) VALUES (?,?)',[$code,$category]);$cat=Input::query($db,'SELECT the_loai_id FROM the_loai WHERE ma_the_loai=?',[$code])->fetchColumn();
  Input::query($db,'INSERT IGNORE INTO vi_tri_luu_tru(the_loai_id,ma_vi_tri,mo_ta) VALUES (?,?,?)',[$cat,$code.'-A01','Kệ sách mẫu phục vụ kiểm thử']);$location=Input::query($db,'SELECT vi_tri_id FROM vi_tri_luu_tru WHERE ma_vi_tri=?',[$code.'-A01'])->fetchColumn();
  foreach($titles as $title){$n++;if(!in_array($n,[1,5,9,13,19],true))continue;$bookCode='DEMO-'.str_pad((string)$n,3,'0',STR_PAD_LEFT);$cover='/assets/covers/'.strtolower($bookCode).'.svg';
   if(!is_file(__DIR__.'/../frontend'.$cover))throw new RuntimeException('Missing cover: '.$bookCode);
   $id=Input::query($db,'SELECT sach_id FROM sach WHERE ma_sach=?',[$bookCode])->fetchColumn();
   if($id)continue;
   Input::query($db,'INSERT INTO sach(ma_sach,ten_sach,the_loai_id,nha_xuat_ban_id,nam_xuat_ban,gia_bia,mo_ta,cover_path) VALUES (?,?,?,?,?,?,?,?)',[$bookCode,$title,$cat,$publisher,2024+($n%3),65000+$n*5000,'Sách và bìa minh họa do UniLib tạo để kiểm thử. Không phải thông tin của một ấn bản thương mại. Có 3 bản sách giấy để thử yêu cầu, bàn giao, gia hạn, trả và kiểm kê.',$cover]);$id=(int)$db->lastInsertId();$created++;
   Input::query($db,'INSERT INTO sach_tac_gia(sach_id,tac_gia_id) VALUES (?,?)',[$id,$author]);
   for($i=1;$i<=3;$i++){Input::query($db,'INSERT INTO ban_sach(ma_ban_sach,sach_id,the_loai_id,vi_tri_id) VALUES (?,?,?,?)',[$bookCode.'-B'.$i,$id,$cat,$location]);$copies++;}
  }
 }
 return ['books_created'=>$created,'copies_created'=>$copies];
});
echo json_encode($result,JSON_UNESCAPED_UNICODE),PHP_EOL;
