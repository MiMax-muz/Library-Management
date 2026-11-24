<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
$config = require __DIR__.'/../backend/bootstrap.php';
$db = Database::connection($config['db']);
$count = Input::transaction($db, function () use ($db) {
    $books = $db->query('SELECT s.*,t.ten_the_loai FROM sach s JOIN the_loai t USING(the_loai_id) FOR UPDATE')->fetchAll();
    $count = 0;
    foreach ($books as $book) {
        $title = $book['ten_sach'];
        $topic = explode(' —', $book['ten_the_loai'])[0];
        $chapters = match ($topic) {
            'Văn học' => ['Cuộc gặp đầu tiên','Những lá thư còn lại','Trở về'],
            'Kinh tế' => ['Bài toán nguồn lực','So sánh các phương án','Đọc kết quả từ một tình huống'],
            'Công nghệ thông tin' => ['Mô hình và thuật ngữ','Xây dựng ví dụ đầu tiên','Kiểm tra và cải tiến'],
            'Khoa học' => ['Quan sát và đặt câu hỏi','Thiết kế thí nghiệm','Giải thích kết quả'],
            default => ['Nhận diện vấn đề','Thử nghiệm một phương pháp','Tự đánh giá và điều chỉnh']
        };
        $base = '978'.str_pad((string)$book['sach_id'],9,'0',STR_PAD_LEFT);
        $sum = 0;
        foreach (str_split($base) as $i=>$digit) $sum += (int)$digit * ($i%2 ? 3 : 1);
        $values = [
            'subtitle'=>"Tủ sách $topic · Ấn bản minh họa", 'edition'=>'Ấn bản thứ nhất',
            'languages'=>'Tiếng Việt', 'publish_place'=>'Hà Nội', 'nam_xuat_ban'=>2024,
            'subjects'=>$topic, 'tags'=>"$title, $topic", 'isbn'=>$base.((10-$sum%10)%10),
            'table_of_contents'=>"1. {$chapters[0]}\n2. {$chapters[1]}\n3. {$chapters[2]}",
            'mo_ta'=>"Trong “{$title}”, người đọc lần lượt khám phá ba phần: ".implode(', ', $chapters).'. Nội dung minh họa của UniLib.'
        ];
        $changes = [];
        foreach ($values as $key=>$value) {
            if (($key==="mo_ta" && str_starts_with((string)$book[$key],"Trong ".chr(226).chr(128).chr(156).", người đọc")) || trim((string)$book[$key]) === '' || ($key==='mo_ta' && str_starts_with((string)$book[$key],'Sách và bìa minh họa do UniLib tạo để kiểm thử.'))) $changes[$key]=$value;
        }
        if (!$changes) continue;
        $fields = implode(',',array_map(fn($key)=>"$key=?",array_keys($changes)));
        Input::query($db,"UPDATE sach SET $fields WHERE sach_id=?",[...array_values($changes),$book['sach_id']]);
        ++$count;
    }
    return $count;
});
echo "Updated: $count\n";
