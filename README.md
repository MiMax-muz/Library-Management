# ITTV — Hệ thống quản lý thư viện

Ứng dụng quản lý thư viện và sách điện tử, xây dựng bằng HTML/CSS/JavaScript, PHP và MySQL/MariaDB. README này mô tả kiến trúc, cấu trúc mã, cách xây dựng và vận hành toàn dự án. Phân công theo tuần nằm trong [kế hoạch triển khai](../ITTV-LibraryManagement-changes/2026-11-10-07-toi-gian-runtime/archive/docs/KE_HOACH_THEO_TUAN.md).

## 1. Phạm vi và trạng thái

Hệ thống hướng tới quản lý tài khoản, độc giả/thẻ, sách/bản sách, mượn/trả/gia hạn, tiền phạt, sách điện tử, tủ sách cá nhân, thông báo và báo cáo.

Bản cục bộ hiện có các chức năng tuần 2–4 nối PHP/MariaDB thật:
- Đăng ký User có xác thực email, gửi lại liên kết, quên/đặt lại mật khẩu qua email; đăng nhập email/MSSV, đăng xuất, đổi mật khẩu; quản lý tài khoản và nhân viên.
- Tra cứu sách theo tên/mã/ISBN, lọc thể loại; quản lý đầu sách, bản sách, tác giả, nhà xuất bản và vị trí.
- Quản lý hồ sơ độc giả; cấp/gia hạn/khóa/mở thẻ và kiểm tra hiệu lực.
- Xem trước tiền phạt quá hạn, mất/hỏng và chi phí sửa.

- Hồ sơ cá nhân và xem hiệu lực thẻ; phân quyền dữ liệu theo tài khoản đăng nhập.
- Gửi yêu cầu mượn giấy, duyệt/từ chối/hủy, bàn giao cùng cập nhật kho; trả từng bản, yêu cầu/duyệt gia hạn, lọc quá hạn và lịch sử; xác nhận phạt cùng thao tác trả/mất/hỏng, giữ snapshot và chống ghi trùng.
- Metadata eBook, tải PDF và lưu phiên bản ngoài thư mục public; đọc công khai hoặc theo quyền được duyệt; quyền điện tử có hạn và thu hồi.
- Tủ sách, yêu thích, dấu trang, lưu tiến độ và lịch sử đọc riêng theo tài khoản/phiên bản. Tiến độ hiện nhập thủ công trên màn hình Tủ sách; đồng bộ tự động với trình đọc thuộc tuần 5.
- Kiểm kê từng bản sách và nhật ký; báo cáo sách/độc giả/tài khoản/yêu cầu/bàn giao; Admin sửa chính sách.
- Thông báo thật khi duyệt/từ chối mượn và gia hạn, đếm chưa đọc, lọc/tìm, đánh dấu đã đọc. Tái sử dụng backend và giao diện của `huy-kun112-mm` từ commit `019a89d` và `5a76e4e`, thay nguồn mẫu bằng API và đưa SQL vào migration chính thức.

Tuần 5 đã bổ sung thu đủ tiền phạt tại quầy, nhắc hạn/quá hạn và thông báo quyền eBook hết hạn qua job chạy theo lịch, trình đọc khôi phục/lưu tiến độ và dấu trang, lịch sử mượn cá nhân, báo cáo tích hợp và xuất CSV. Trình đọc lưu trang do người dùng chọn; trình xem PDF gốc của trình duyệt không cung cấp sự kiện vị trí đọc đáng tin cậy để tự ghi khi cuộn. Job cần được lập lịch trên máy chủ; nó không tự cài tác vụ Windows. Tuần 6 vẫn cần kiểm tra tích hợp và ổn định trước bàn giao. Không tự merge/push/tag Git.

## 2. Kiến trúc và cách xây dựng

Trình duyệt nạp `frontend/index.html`, sau đó `app.js` khôi phục phiên và chọn màn hình theo hash URL. Các màn hình dùng chung layout, form và API client; dữ liệu nghiệp vụ gửi tới `/api`.

PHP nhận yêu cầu tại `backend/public/index.php`, kiểm tra CSRF, định tuyến và phân quyền, gọi service nghiệp vụ. Service dùng PDO và câu SQL có tham số để truy cập database; thao tác nhiều bước dùng transaction. Phản hồi JSON có `data` và `error`.

Khi phát triển một chức năng:
1. Đối chiếu đặc tả và chính sách; xác định dữ liệu, quyền, đầu vào/đầu ra và lỗi.
2. Bổ sung migration khi cần, không sửa/xóa dữ liệu cũ bằng cách nhập lại schema.
3. Viết route/service và kiểm tra quyền phía máy chủ; dùng transaction cho cập nhật liên quan.
4. Viết màn hình trong module tương ứng, sử dụng API client chung và trạng thái tải/trống/lỗi.
5. Kiểm thử đầu vào sai, quyền, thao tác lặp và tranh chấp dữ liệu khi liên quan.
6. Ghi nhật ký thay đổi, bàn giao theo kế hoạch và kiểm tra trước khi tích hợp.

## 3. Cấu trúc thư mục

| Đường dẫn | Vai trò |
|---|---|
| `frontend/index.html`, `app.js` | Điểm vào và điều hướng ứng dụng |
| `frontend/shared/` | Layout, CSS, phiên, API client, form và bảng dùng chung |
| `frontend/books/` | Tra cứu và quản lý sách/kho |
| `frontend/readers/live.js` | Hồ sơ và thẻ dùng API thật |
| `frontend/accounts/live.js` | Đăng nhập, đăng ký, tài khoản/nhân viên dùng API thật |
| `frontend/loans/`, `backend/loans/` | Yêu cầu, duyệt, bàn giao, trả, gia hạn và lịch sử |
| `frontend/ebooks/`, `backend/ebooks/` | eBook, phiên bản PDF, yêu cầu/quyền đọc và nội dung riêng tư |
| `frontend/bookshelf/`, `backend/bookshelf/` | Tủ sách, yêu thích, dấu trang, tiến độ và lịch sử đọc |
| `frontend/profile/`, `backend/profile/` | Hồ sơ và thẻ của tài khoản đang đăng nhập |
| `frontend/notifications/`, `backend/notifications/` | Thông báo từ sự kiện thật; tái sử dụng đóng góp TV4 |
| `frontend/inventory/`, `backend/inventory/` | Kiểm kê và nhật ký |
| `frontend/reports/`, `backend/reports/`, `backend/settings/` | Báo cáo nền/yêu cầu/bàn giao và chính sách |
| `backend/service/mail/` | Gửi email, tạo và kiểm tra liên kết xác thực/khôi phục mật khẩu |
| `frontend/fines/preview.js` | Công cụ tính phạt dự kiến |
| `backend/public/index.php`, `routes.php` | Điểm nhận API, đăng ký route và xử lý lỗi |
| `backend/shared/` | PDO, Auth, Router, Input, Response và log lỗi |
| `backend/books`, `readers`, `accounts`, `fines` | API/service các module đã triển khai |
| `backend/storage/` | Dữ liệu, log và ảnh kiểm thử cục bộ; không đưa vào Git |
| `database/schema.sql`, `seed.sql` | Khởi tạo database trống và dữ liệu mẫu |
| `database/migrations/` | Nâng cấp dữ liệu theo phiên bản |
| `database/create-admin.php` | Công cụ CLI tạo Admin lần đầu, không ghi đè tài khoản |

Các file demo/model/test và tài liệu phát triển được lưu ngoài dự án; hệ thống chính chỉ chứa các module đang hoạt động.

## 4. Dữ liệu và quy tắc nghiệp vụ

Schema nền có tài khoản, nhân viên, độc giả/thẻ, cấu hình, nhật ký, thể loại, tác giả, nhà xuất bản, vị trí, đầu sách, liên kết tác giả và bản sách. Migration 001 bổ sung trường và chính sách; 002 chuẩn hóa ba vai trò; 003 thêm eBook/phiên bản tệp và yêu cầu/phiếu mượn giấy.

- Vai trò: `user`, `librarian`, `admin`. Đăng ký công khai luôn là User; chỉ Admin quản lý tài khoản/nhân viên.
- Máy chủ quyết định quyền; ẩn nút trên giao diện không thay kiểm tra quyền API.
- Mật khẩu dùng password_hash/password_verify; phiên và CSRF do máy chủ cấp. Seed không có mật khẩu đăng nhập hợp lệ.
- Tồn khả dụng chỉ gồm bản available còn lưu hành và đầu sách còn lưu hành. Không xóa/ngừng lưu hành sách đang mượn.
- Vị trí phải cùng thể loại; không đổi thể loại đầu sách đã có bản.
- Thẻ mặc định 12 tháng lịch; ngày thiếu được kẹp về cuối tháng. Gia hạn từ max(hôm nay, hạn hiện tại).
- Phạt quá hạn theo ngày lịch Asia/Ho_Chi_Minh; mặc định 2.000đ/cuốn/ngày. Công cụ hiện tại chỉ xem trước, không thu tiền.

Quy tắc chi tiết: [đặc tả nghiệp vụ](../ITTV-LibraryManagement-changes/2026-11-10-07-toi-gian-runtime/archive/docs/DAC_TA_NGHIEP_VU.md) và [chính sách đã chốt](../ITTV-LibraryManagement-changes/2026-11-10-07-toi-gian-runtime/archive/CHINH_SACH_B1.md). Tên chính sách có B1 nhưng nội dung vẫn được áp dụng.

## 5. Cài đặt và chạy thủ công

Môi trường đã kiểm tra: PHP 8.2.12 với PDO MySQL/mbstring, MariaDB 10.4.32 và Node.js cho kiểm thử. Cần MySQL/MariaDB đang chạy; có thể bật MySQL từ XAMPP Control Panel.

### Cài mới

Tạo database trống `ittv_library` với charset utf8mb4. Trong phpMyAdmin hoặc mysql CLI, chọn database này rồi nhập theo thứ tự:
1. `database/schema.sql`
2. `database/seed.sql`

Không nhập lại hai file này trên database đã có dữ liệu.

Mở PowerShell tại gốc dự án, cấu hình theo database thực tế:

```powershell
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3306'
$env:DB_NAME='ittv_library'
$env:DB_USER='root'
$env:DB_PASSWORD=''
$env:SESSION_SECURE='0'
& C:/xampp/php/php.exe database/migrations/001_b1.php
& C:/xampp/php/php.exe database/migrations/002_three_roles.php
& C:/xampp/php/php.exe database/migrations/003_b2.php
& C:/xampp/php/php.exe database/migrations/004_email_auth.php
& C:/xampp/php/php.exe database/migrations/005_b34.php
& C:/xampp/php/php.exe database/migrations/006_book_covers.php
& C:/xampp/php/php.exe database/migrations/007_week5_payments.php
& C:/xampp/php/php.exe database/migrations/008_book_details.php
```

PHP không tự đọc `.env`; `.env.example` chỉ là danh sách biến cần đặt. Kiểm tra từng migration thành công trước khi tiếp tục. Với database cũ, chỉ chạy migration, không nhập lại schema/seed. Nếu dùng database/cổng cũ từ bản chạy trước, đặt đúng DB_NAME/DB_PORT, không đổi tên hay xóa database để cài lại.

### Tạo Admin lần đầu

```powershell
$env:ADMIN_EMAIL='admin.local@example.test'
$env:ADMIN_PASSWORD='ThayBangMatKhauRieng123!'
& C:/xampp/php/php.exe database/create-admin.php
Remove-Item Env:ADMIN_PASSWORD
```

Thay mật khẩu ví dụ bằng mật khẩu riêng. Công cụ từ chối email đã tồn tại, không đặt lại mật khẩu tài khoản cũ. User có thể tự đăng ký trên giao diện; tài khoản mới chưa có hồ sơ/thẻ nên chưa đủ điều kiện mượn.

### Chạy web

Trong cửa sổ đã đặt DB_*:

```powershell
& C:/xampp/php/php.exe -d upload_max_filesize=20M -d post_max_size=24M -S 127.0.0.1:8080 -t frontend backend/dev-router.php
```

Mở http://127.0.0.1:8080. Document root phải là `frontend`, không phải toàn repository. Lệnh này phục vụ phát triển cục bộ; cấu hình triển khai thực tế cần HTTPS, cookie secure, web server và bảo vệ storage/config.

### Nhắc hạn tự động và thu tiền phạt

Sau migration 007, Admin/thủ thư có thể ghi nhận **toàn bộ** khoản phạt đã xác nhận tại quầy; hệ thống khóa khoản phạt khi thu và chỉ lưu một lần, không hỗ trợ trả một phần hay cổng thanh toán. Lịch sử thu và số còn phải thu được hiển thị trong mục Quản lý vi phạm.

Job gửi nhắc sách giấy/quyền đọc eBook trước hạn 2 ngày, báo sách đang quá hạn và báo quyền eBook đã hết hạn được chạy thủ công bằng:

```powershell
& C:/xampp/php/php.exe backend/jobs/daily-reminders.php
```

Để tự động chạy lúc 09:00 mỗi ngày giờ Việt Nam, hãy tạo tác vụ trong Windows Task Scheduler chạy lệnh trên với thư mục bắt đầu là gốc dự án và cùng các biến `DB_*` của ứng dụng. Chạy lại job không tạo thông báo trùng.

## 6. Kiểm thử và tài liệu ngoài dự án

Bộ kiểm thử, đặc tả, kế hoạch, chính sách và mã tham khảo được giữ tại [kho lưu trữ bên ngoài](../ITTV-LibraryManagement-changes/2026-11-10-07-toi-gian-runtime/archive/). Kết quả từng đợt nằm trong ../ITTV-LibraryManagement-changes/.

Kiểm thử database chỉ dùng DB riêng có tên kết thúc _test. Khi phục hồi bộ test PHP để chạy lại, giữ cấu trúc đường dẫn tương đối hoặc điều chỉnh đường dẫn bootstrap về dự án. Không chạy test dữ liệu trên database vận hành. Các bản test GUI B1 mô tả giao diện cũ; test b2-browser.cjs kiểm tra giao diện hiện tại.

## 7. Tổ chức công việc và tài liệu

Phân công TV1–TV4, đầu vào/đầu ra từng tuần, review và điều kiện nghiệm thu nằm trong [kế hoạch tuần](../ITTV-LibraryManagement-changes/2026-11-10-07-toi-gian-runtime/archive/docs/KE_HOACH_THEO_TUAN.md). Dự án dùng main và các nhánh tv1–tv4; main chứa bản được kiểm tra/tích hợp. Không dùng tính năng người khác đang viết cùng tuần làm đầu vào đã sẵn sàng.

`.gitignore` bảo vệ dữ liệu riêng khỏi Git; `.env.example` liệt kê cấu hình cần đặt. `.git` giữ lịch sử mã nguồn, không xóa khi dọn hệ thống. Hướng dẫn cho trợ lý và cấu hình soạn thảo cũ đã chuyển ra kho lưu trữ.

Nhật ký mỗi đợt nằm ngoài dự án tại `../ITTV-LibraryManagement-changes/`. Tài liệu bàn giao cũ được lưu tại đó. README hiện tại là tài liệu tổng quan duy nhất ở gốc dự án; lịch sử thay đổi không trộn vào hướng dẫn vận hành.

Mỗi đợt sửa tiếp theo cần ghi nhật ký ngoài dự án theo thư mục YYYY-MM-DD-NN-ten-ngan, kèm nội dung thay đổi và kết quả kiểm thử thực tế.

## 8. Email xác thực và khôi phục mật khẩu

Form đăng ký chỉ yêu cầu họ tên, email, mật khẩu và xác nhận mật khẩu; không yêu cầu MSSV hay số điện thoại. Tài khoản tự đăng ký mới phải xác thực email trước khi đăng nhập. Tài khoản đã có trước migration 004 và tài khoản do Admin tạo giữ quyền đăng nhập hiện tại. Xác thực email và hiệu lực thẻ là hai điều kiện riêng.

- Link xác thực có hạn 30 phút; link đặt lại mật khẩu có hạn 15 phút. Chỉ dùng thành công một lần; gửi lại thay link cũ.
- Gửi lại cách nhau ít nhất 60 giây; giới hạn theo email và IP trong 15 phút. Phản hồi quên mật khẩu không tiết lộ email có tồn tại hay không.
- Đổi/đặt lại mật khẩu thu hồi phiên cũ và các link đặt lại chưa dùng. Tài khoản bị khóa không thể dùng link để mở khóa.
- Email được gửi qua SMTP bằng PHPMailer (mã thư viện và LICENSE nằm trong backend/lib/phpmailer, lấy từ bộ nguồn AudioSpace do người dùng cung cấp). Cơ chế token được điều chỉnh cho schema và phiên UniLib; không thay sang JWT.

Đặt các biến dưới đây trong cùng PowerShell chạy PHP, dùng cấu hình do nhà cung cấp email cấp:

```powershell
$env:APP_URL='http://127.0.0.1:8080'
$env:MAIL_DRIVER='smtp'
$env:MAIL_HOST='dia-chi-may-chu-smtp'
$env:MAIL_PORT='587'
$env:MAIL_ENCRYPTION='tls'
$env:MAIL_USERNAME='email-gui-cua-ban'
$env:MAIL_FROM='email-gui-cua-ban'
$env:MAIL_PASSWORD='mat-khau-ung-dung-hoac-smtp'
```

Không commit mật khẩu SMTP. APP_URL phải là địa chỉ người nhận truy cập được; localhost chỉ phù hợp khi mở email trên chính máy chạy dự án. Khi cấu hình thay đổi, khởi động lại PHP từ cửa sổ đã đặt biến. Không tự lấy thông tin SMTP của AudioSpace. Nếu chưa cấu hình SMTP, đăng ký/gửi lại/quên mật khẩu báo chưa cấu hình; các tài khoản hiện có vẫn đăng nhập bình thường.

Để thử mà không gửi thư thật, chỉ trên môi trường thử đặt APP_ENV=test và MAIL_DRIVER=file. Thư mô phỏng lưu riêng ở backend/storage/mail-test, không phục vụ qua web và không đưa vào Git. Đây không phải bằng chứng SMTP đã gửi thành công.

Chi tiết cấu hình Gmail, thay đổi database và ảnh hưởng kế hoạch: [NOTE_XAC_THUC_EMAIL.md](NOTE_XAC_THUC_EMAIL.md). Có thể điền Gmail/mật khẩu ứng dụng trực tiếp trong backend/mail.local.php; file này không được đưa vào Git. Biến môi trường ưu tiên hơn cấu hình local.

## 11. Thử chức năng tuần 3–4 trên giao diện đang chạy

Database đã có dữ liệu chỉ cần chạy các migration còn thiếu, không nhập lại schema/seed. Migration `005_b34.php` tạo các bảng gia hạn, phạt, quyền/yêu cầu eBook, tủ sách/tiến độ/dấu trang/lịch sử, kiểm kê và thông báo. Không đổi mật khẩu hoặc xóa tài khoản. File PDF lưu trong `backend/storage/ebooks`, được đọc qua API kiểm tra quyền; không phục vụ trực tiếp thư mục backend.

Dùng hai cửa sổ trình duyệt riêng (hoặc một cửa sổ ẩn danh) để đăng nhập User và Admin/nhân viên:

1. User mở **Hồ sơ / thẻ của tôi**, nhập và lưu hồ sơ. Admin mở **Độc giả / thẻ**, chọn hồ sơ và cấp thẻ. User tải lại để xem hiệu lực.
2. User mở **Mượn / trả sách**, chọn đầu sách → Xem bản sách → Chọn mượn → Gửi yêu cầu. Yêu cầu chưa duyệt chưa giữ kho; máy chủ kiểm tra lại khi bàn giao.
3. Admin mở cùng mục, chọn **Xử lý / chi tiết** → Duyệt yêu cầu → mở lại → Xác nhận bàn giao. User xem phiếu và hạn trả.
4. User chọn phiếu → **Yêu cầu gia hạn**. Admin chọn phiếu → Duyệt/Từ chối gia hạn. Hạn chỉ thay đổi khi duyệt; kiểm tra số lần và ngày quá hạn trên máy chủ.
5. Admin chọn phiếu → tình trạng trả. Với hỏng sửa được, nhập chi phí và căn cứ. **Xác nhận trả / phạt** cập nhật kho và ghi số tiền trong cùng transaction; không có thao tác thu tiền. Xem khoản đã ghi ở **Tính phạt dự kiến** phía dưới công cụ xem trước. Bản mất/hỏng không tự quay lại khả dụng.
6. Admin mở **Sách điện tử** → Thêm eBook → chọn đầu sách và quyền → Chi tiết → tải PDF. Tải lần nữa tạo phiên bản mới. PDF tối đa 20 MB; máy chủ PHP phải có giới hạn tải lên tương ứng với lệnh chạy ở trên.
7. eBook công khai có thể đọc ở nút **Đọc eBook công khai** trên màn hình đăng nhập. Với eBook giới hạn: User yêu cầu mượn; Admin duyệt trong danh sách yêu cầu điện tử. User đọc khi quyền còn hiệu lực; Admin có thể thu hồi ở danh sách quyền.
8. User thêm eBook vào yêu thích rồi mở **Tủ sách / tiến độ**, chọn phiên bản để lưu trang/% và dấu trang. Phiên bản mới có tiến độ riêng. Mất quyền đọc chặn đọc PDF và cập nhật tiến độ, nhưng giữ lịch sử đã có.
9. Mở **Thông báo** sau khi duyệt/từ chối để kiểm tra nội dung cá nhân, bộ lọc và đánh dấu đọc. Không có dữ liệu mẫu trộn vào hộp thông báo thật.
10. Admin/nhân viên mở **Kiểm kê** để ghi nhận tình trạng/căn cứ. Bản đang mượn không thể chuyển thành khả dụng bằng kiểm kê. **Báo cáo / chính sách** cho phép xem tổng hợp; chỉ Admin được sửa chính sách. Bộ lọc ngày áp dụng cho yêu cầu/bàn giao, các số liệu nền phản ánh hiện tại.

Giới hạn quyền: User không được mở trang quản lý, duyệt/giao/trả sách thay nhân viên, sửa chính sách hoặc xem dữ liệu cá nhân người khác. Menu hiện theo quyền; các kiểm tra tương ứng vẫn thực hiện trên máy chủ.

Kiểm thử đợt tuần 3–4 thực hiện trên database riêng: nghiệp vụ chống bàn giao/ghi phạt trùng, hoàn tác khi bản cuối không còn, gia hạn và biên quá hạn; HTTP kiểm tra quyền PDF/phiên bản/thu hồi, quyền hồ sơ/thông báo và đầu vào; trình duyệt chạy luồng yêu cầu → duyệt → bàn giao → gia hạn → trả, lưu hồ sơ/tiến độ và mở menu User/Admin. Bằng chứng nằm trong nhật ký ngoài dự án, không phải màn hình thử tách khỏi hệ thống.

## 12. Bộ 5 đầu sách mẫu có bìa

Bộ demo gồm DEMO-001, DEMO-005, DEMO-009, DEMO-013 và DEMO-019. Mỗi đầu sách có 3 bản giấy. Sau khi tạo sách mẫu, chạy `php database/seed-book-details.php` để điền thông tin thư mục minh họa.

Tìm `DEMO-` trong Sách/kho hoặc Tra cứu sách để xem bộ mẫu. Bìa lưu cục bộ trong `frontend/assets/covers/`; cột `sach.cover_path` do migration 006 bổ sung. Sách cũ không có bìa vẫn hiển thị bình thường. Không có PDF toàn văn đi kèm; thử eBook bằng cách chọn một đầu sách mẫu và tải PDF của bạn qua Sách điện tử.

Để tạo bộ mẫu trên máy khác, sau khi chạy migration 006:

```powershell
& C:/xampp/php/php.exe database/seed-demo-books.php
```

Công cụ chỉ thêm đầu sách mẫu chưa tồn tại, không ghi đè thông tin/trạng thái đã dùng để kiểm thử và không tạo trùng khi chạy lại. Không tự tạo hồ sơ, thẻ hay phiếu mượn; hãy thực hiện các bước đó trên giao diện.
