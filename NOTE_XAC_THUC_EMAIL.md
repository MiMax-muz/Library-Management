# Note: xác thực email, khôi phục mật khẩu và ảnh hưởng dự án

Cập nhật 10/11/2026. Đây là note riêng theo yêu cầu, đặt cùng cấp README; lịch sử từng đợt vẫn lưu ngoài dự án.

## 1. Luồng đã bổ sung

- Đăng ký: người dùng nhập email của mình → tạo tài khoản User chờ xác thực → gửi link vào email đã nhập → bấm link và xác nhận → được đăng nhập.
- Gửi lại xác thực: nhập email đã đăng ký. Link mới thay link cũ; phải chờ ít nhất 60 giây giữa hai lần gửi.
- Quên mật khẩu: nhập email → nhận link → nhập mật khẩu mới và xác nhận. Chỉ tài khoản đang hoạt động và đủ điều kiện xác thực được đặt lại.
- Link xác thực có hạn 30 phút; link khôi phục 15 phút. Token ngẫu nhiên, database chỉ giữ hash SHA-256, dùng một lần và có khóa transaction chống dùng đồng thời.
- Đổi/đặt lại mật khẩu thu hồi phiên cũ và link reset còn lại. Tài khoản khóa không được dùng link để tự mở khóa.

Email nhập trên form là **người nhận**. Gmail cấu hình ở máy chủ là **người gửi**, dùng chung để thư viện gửi thư cho các tài khoản.

Đăng ký công khai chỉ yêu cầu họ tên, email, mật khẩu và xác nhận mật khẩu; đã bỏ MSSV và số điện thoại theo yêu cầu. Hai cột hiện có cho phép NULL nên không cần migration mới, không xóa dữ liệu tài khoản cũ. Admin vẫn có thể quản lý tài khoản không có MSSV; tài khoản cũ vẫn đăng nhập bằng email hoặc MSSV. Đây là điều chỉnh yêu cầu đăng ký của phần tài khoản trong kế hoạch tuần 1–2.

## 2. Cấu hình Gmail trên máy

Mở `backend/mail.local.php` và điền `username` là Gmail gửi, `password` là mật khẩu ứng dụng. File này bị Git bỏ qua, nằm ngoài document root frontend. Không gửi mật khẩu qua chat hoặc đưa vào Git. Các giá trị khác đã có sẵn: smtp.gmail.com, 587, tls.

Google yêu cầu bật xác minh hai bước để tạo mật khẩu ứng dụng; một số loại tài khoản/chính sách không có lựa chọn này. Không dùng mật khẩu đăng nhập Gmail thông thường. Xem [hướng dẫn mật khẩu ứng dụng của Google](https://support.google.com/mail/answer/185833) và [SMTP Gmail](https://developers.google.com/workspace/gmail/imap/imap-smtp).

`app_url` phải đúng địa chỉ web đang chạy. `http://127.0.0.1:8080` chỉ dùng khi mở email trên chính máy chạy dự án; điện thoại hoặc máy khác cần địa chỉ truy cập được tới máy chủ. Khi triển khai thật dùng HTTPS.

Biến môi trường MAIL_* và APP_* ưu tiên hơn file local, kể cả giá trị rỗng. Nếu đã đặt biến cũ, hãy sửa/xóa biến đó hoặc mở PowerShell mới. File `.env` không được tự động đọc. Tham khảo `.env.example` nếu dùng biến môi trường thay file local.

Sau khi điền cấu hình, khởi động lại web theo README, đăng ký bằng một email có thể nhận thư, kiểm tra hộp thư đến/thư rác. Không có cấu hình SMTP thì đăng ký/gửi lại/quên mật khẩu báo chưa cấu hình; tài khoản cũ vẫn đăng nhập bình thường.

## 3. Thay đổi cơ sở dữ liệu

Migration: `database/migrations/004_email_auth.php`. Không nhập lại schema/seed trên database đang có dữ liệu.

| Bảng/cột | Mục đích |
|---|---|
| `tai_khoan.email_verified_at` | Thời điểm xác thực; NULL nếu chưa có |
| `tai_khoan.email_verification_required` | 1 với đăng ký công khai mới; 0 mặc định để giữ tương thích tài khoản cũ/Admin tạo |
| `tai_khoan.auth_version` | Tăng khi đổi/reset mật khẩu để thu hồi phiên trước đó |
| `account_email_tokens` | FK tài khoản, loại verify/reset, hash token duy nhất, hạn dùng, thời điểm tạo/đã dùng |
| `auth_rate_limits` | Đếm yêu cầu theo email/IP đã băm, cửa sổ 15 phút |

Sau khi đặt DB_* đúng, chạy:

```powershell
& C:/xampp/php/php.exe database/migrations/004_email_auth.php
```

Migration đã chạy trên `ittv_library` cổng 3306 ở đợt trước; chạy lại sẽ SKIP. Máy khác/DB khác phải chạy migration này trước khi dùng mã mới. Không thêm trạng thái pending vào enum tài khoản: tài khoản chờ xác thực vẫn active nhưng bị chặn đăng nhập bởi cờ email. Nhân viên/Admin tạo được coi là đã được quản trị viên cấp quyền và không bị ép xác thực trong phạm vi hiện tại.

Không đổi bảng sách, thẻ hoặc mượn. Không xóa tài khoản, đổi ID, ghi đè mật khẩu cũ hoặc reset dữ liệu. Không tự cấp hồ sơ/thẻ khi xác thực email.

## 4. Ảnh hưởng kế hoạch và phân công

Đây là mở rộng phần tài khoản TV4 của tuần 2, không phải nội dung đã có trong nghiệm thu B1 ban đầu. Tuần 2 có thể ghi thêm đầu ra: xác thực email, gửi lại link, quên/reset mật khẩu và thu hồi phiên. Chưa tự thay đổi lịch hoặc đánh dấu B2 đã nghiệm thu toàn bộ.

- **TV4:** backend/frontend tài khoản, trạng thái chờ xác thực, cấu hình gửi mail, thông báo lỗi và kiểm thử.
- **TV1:** migration, Auth/phiên, route chung, cấu hình triển khai, tài liệu và kiểm tra tương thích tài khoản cũ.
- **TV2:** tiếp tục hồ sơ/thẻ trên tài khoản nền; xác thực email không thay kiểm tra hiệu lực thẻ. Khi nối luồng cá nhân ở tuần 3, chỉ dùng phiên đã được máy chủ xác thực.
- **TV3:** bộ tính phạt tuần 2 không bị phụ thuộc email. Mượn giấy tuần 3 dùng phiên, thẻ và kho đã nghiệm thu.
- Gửi mail xác thực là tiện ích phục vụ tài khoản; không đồng nghĩa đã hoàn thành thông báo nhắc hạn ở tuần 4–5.

Phần mở rộng cần kiểm tra tích hợp cuối tuần theo kế hoạch; không ép thành viên phụ thuộc API chưa bàn giao trong cùng tuần. Công việc tăng thêm gồm thiết lập tài khoản gửi, kiểm tra gửi thật, xử lý lỗi SMTP và bộ ca email. Những điểm còn thiếu khác của tuần 2 vẫn phải xử lý riêng.

## 5. File và API liên quan

`backend/service/mail/EmailAuthService.php`, `backend/service/mail/EmailSender.php`; `backend/accounts/AccountService.php`, `backend/accounts/routes.php`; `backend/shared/Auth.php`; `backend/mail-config.php`, `backend/mail.local.php`; `backend/lib/phpmailer/`; migration 004; `frontend/accounts/live.js`, `frontend/app.js`, `frontend/index.html`; `.env.example` và `.gitignore`.

Phần mail đã chuyển vào `backend/service/mail/`. EmailSender đảm nhiệm gửi thư; EmailAuthService đảm nhiệm tạo/kiểm tra token, đăng ký, gửi lại xác thực và khôi phục mật khẩu. Các bước tương ứng với các file mail của AudioSpace được tích hợp qua route của dự án, không chạy trực tiếp từng file PHP. Việc chuyển thư mục không đổi database hay phạm vi kế hoạch. Cần cấu hình kết nối database, chạy migration 004 và điền tài khoản SMTP trong mail.local.php trước khi gửi thư thật.

API POST: `/api/accounts/register`, `/resend-verification`, `/verify-email`, `/forgot-password`, `/reset-password` (bốn đường dẫn sau cùng dưới tiền tố `/api/accounts`). Mọi POST dùng CSRF chung. Xác thực không thực hiện bằng GET khi chỉ mở link.

## 6. Kiểm thử và giới hạn

Đã PASS trên database riêng/hộp thư mô phỏng: đăng ký → chặn chưa xác thực → xác thực → đăng nhập; reset một lần; hết hạn; gửi lại vô hiệu link cũ; cooldown/rate limit; tài khoản khóa; đổi mật khẩu thu hồi link và phiên; hồi quy B2.

Chưa gửi/nhận SMTP Gmail thật vì chưa có Gmail gửi và mật khẩu ứng dụng do người dùng cấu hình. Không coi email mô phỏng là gửi thư thành công. File thư thử chỉ hoạt động với APP_ENV=test và MAIL_DRIVER=file, không được dùng làm cấu hình vận hành.

## 7. Hoàn tác

Không DROP bảng/cột để quay lại phiên bản cũ. Sao lưu dữ liệu trước mọi thay đổi schema. Nếu quay về mã cũ, giữ các cột/bảng bổ sung; lưu ý mã cũ không biết cờ chờ email nên phải đánh giá việc chặn tài khoản chưa xác thực trước khi rollback. Tắt SMTP không tự hủy token đã phát hành.
