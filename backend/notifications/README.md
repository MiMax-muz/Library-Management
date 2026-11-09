# Backend thông báo TV4 — tuần 4

Module đã có route đọc/đánh dấu thông báo và service tạo thông báo từ sự kiện nội bộ. Chưa nối TV3/B3 hoặc job TV1. `schema-proposal.sql` là bản đề xuất để TV1 đưa vào migration chính thức; module không tự tạo bảng.

## API cho tài khoản đang đăng nhập

| Phương thức | Đường dẫn | Kết quả |
|---|---|---|
| GET | `/api/notifications?page=1&status=all&type=all&search=` | `{data: [...], meta: {page, per_page, total, unread_total}}`, 20 bản ghi/trang |
| GET | `/api/notifications/unread-count` | `{data: {unread: n}}` |
| PATCH | `/api/notifications/:id/read` | Một thông báo đã đọc; gọi lặp không đổi `readAt` |
| PATCH | `/api/notifications/read-all` | `{data: {updated: n, unread: n}}` |

`status`: `all`, `unread`, `read`. `type`: `all`, `borrow`, `renewal`, `due`. `search` tìm văn bản trong tiêu đề, nội dung và mã tham chiếu, tối đa 100 ký tự. Mọi truy vấn chỉ dùng `tai_khoan_id` từ phiên máy chủ; ID thuộc người khác trả 404. Phiên thiếu trả 401, tài khoản bị khóa bị vô hiệu. Các thao tác PATCH đi qua kiểm tra CSRF của `backend/public/index.php`.

Mỗi phần tử `data` gồm `id`, `type`, `title`, `message`, `reference`, `createdAt`, `readAt`. Hai thời điểm trả ISO 8601 UTC (`...Z`); `readAt` là `null` khi chưa đọc. Không trả ID người nhận, khóa sự kiện hoặc dữ liệu tài khoản khác.

## Tạo thông báo từ service nguồn

Không có endpoint POST công khai. TV3 hoặc job của TV1 gọi `NotificationService::createFromEvent()` bằng **cùng PDO** trong giao dịch đang điều phối:

```php
$notifications = new NotificationService($pdo);
$notifications->createFromEvent([
    'recipient_id' => $readerAccountId,
    'type' => 'borrow-approved',
    'source_id' => (string)$requestId,
    'title' => 'Yêu cầu mượn đã được duyệt',
    'message' => 'Vui lòng đến quầy để nhận sách.',
    'reference' => 'Yêu cầu YC-1042',
]);
```

Loại hỗ trợ: `borrow-approved`, `borrow-rejected`, `renewal-approved`, `renewal-rejected`, `due-reminder`. Với `due-reminder`, bắt buộc có `due_date` dạng `YYYY-MM-DD`; các loại khác có thể truyền nếu cần phân biệt hạn. Khóa duy nhất được tạo từ loại + `source_id` + `due_date`, nên chạy lại cùng sự kiện trả về bản ghi cũ. Nếu cùng khóa nhắm người nhận khác, service trả 409. Service không tự commit; giao dịch nguồn hoàn tác thì thông báo cũng hoàn tác. Mã `source_id` chấp nhận 1–80 ký tự chữ ASCII, số, `:`, `_`, `-`; TV3/TV1 cần chốt cách ánh xạ ID nguồn trước khi nối sự kiện thật.

Chính sách B1 đã chốt thông báo trong ứng dụng cho duyệt/từ chối yêu cầu mượn hoặc gia hạn và nhắc trước hạn 2 ngày lúc 09:00. Việc xác định đúng sự kiện từ giao dịch B3 và lập lịch job vẫn thuộc chủ module nguồn/TV1; service không tự suy ra trạng thái phiếu hoặc gửi email.

## Bảng và kiểm tra

TV1 cần duyệt `schema-proposal.sql`, tạo migration và đưa vào bản nền trước khi chạy API trên MariaDB. Bảng phải có khóa duy nhất `ma_su_kien`, khóa ngoại tài khoản và chỉ mục người nhận/trạng thái/ngày. Không nhập SQL đề xuất trực tiếp vào database sản phẩm khi chưa chốt B3.

Chạy kiểm tra độc lập:

```powershell
php -d extension=C:/php/ext/php_pdo_sqlite.dll backend/notifications/test-service.php
```

Kiểm tra gồm quyền cá nhân, tài khoản bị khóa, lọc/tìm kiếm/phân trang, đánh dấu đọc lặp, chống trùng sự kiện và hoàn tác theo giao dịch nguồn. Kiểm thử HTTP/MariaDB cần chạy lại sau khi TV1 áp migration.
