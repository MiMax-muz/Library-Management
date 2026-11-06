> **Cập nhật TV4 tuần 3 — 05/11/2026:** đã dựng màn hình báo cáo nền và form chính sách trong `frontend/reports`, có trang xem trước độc lập. Route sản phẩm đọc API báo cáo và chính sách từ database qua `backend/reports`; trang demo vẫn dùng fixture B1. B2 toàn nhóm chưa có trong thư mục này, nên chưa cho phép lưu chính sách.
>
> **Cập nhật TV4 tuần 2 — 02/11/2026:** đã triển khai API và giao diện tài khoản/nhân viên dùng phiên PHP thật. Ca service chạy độc lập đã qua; chưa chạy nghiệm thu HTTP/MariaDB vì máy chưa có DB thử. B2 toàn nhóm chưa được công bố. Phần tuần 1 bên dưới là lịch sử B1; chính sách áp dụng theo [CHINH_SACH_B1](CHINH_SACH_B1.md).
# TV4 — Nhật ký bàn giao theo tuần

Ngày 01/11/2026 là **tuần 1**. Bản đầu vào origin/tv4 `2f58aed8fb52dadfaa9766a303ab59f59c827ca1` được lấy vào thư mục tv4 riêng. Giữ phần code thành viên đã viết và sửa để chạy độc lập; tài liệu này bổ sung README chính.

## Tuần 1 — đã triển khai và kiểm tra

- Sửa import ../core/routes.js không tồn tại bằng nhãn vai trò nội bộ module; không kéo code prototype vào sản phẩm.
- Bổ sung trang thử, route/mount theo hợp đồng, adapter dữ liệu trong bộ nhớ, xử lý sự kiện đăng nhập/đăng ký/đăng xuất, menu tài khoản/nhân viên và tự đổi mật khẩu.
- Giữ và nối form quản lý tài khoản/nhân viên hiện có; sửa event cleanup bằng AbortSignal, giới hạn event trong container. Bỏ redirect ../../index.html và đọc phiên LocalStorage của prototype.
- Sửa lỗi input name=id che thuộc tính form.id khiến lưu tài khoản gửi form mặc định và tải lại trang; dùng getAttribute('id').
- Đăng ký/tạo sinh viên có MSSV, kiểm tra trùng; đăng nhập mẫu bằng email hoặc MSSV, vẫn hỗ trợ tên bốn tài khoản fixture. Form tài khoản giữ một vai trò, trạng thái hoạt động/khóa. Khóa tài khoản khiến đăng nhập mẫu bị từ chối.
- Đổi mật khẩu kiểm tra mật khẩu hiện tại, tối thiểu 8 ký tự và nhập lại. Credential SHA-256 có salt chỉ phục vụ demo; backend tuần 2 bắt buộc password_hash/password_verify, không dùng cơ chế browser này để xác thực sản phẩm.
- Bổ sung trạng thái nhân viên hợp lệ; đầu ra HTML escape dữ liệu. Route /accounts-demo là demo tuần 1, không thay session backend của TV1.

### Chạy và kiểm tra

Từ gốc nhánh: `C:/xampp/php/php.exe -S 127.0.0.1:8084 -t frontend`; mở `http://127.0.0.1:8084/accounts/demo.html` hoặc Live Server. Bốn tài khoản mẫu student/librarian/staff/admin dùng 123456. Dữ liệu trong bộ nhớ, tải lại đặt lại; không cần DB.

Chạy `node frontend/accounts/week1.test.mjs`: đã qua import module, đăng ký, MSSV trùng, đăng nhập email/MSSV, sai mật khẩu, đổi mật khẩu, khóa tài khoản, dữ liệu trống và escape HTML. Edge đã thử đăng nhập admin, thêm tài khoản, thêm nhân viên, đăng xuất, mở đăng ký; desktop 1280px/mobile 390px không tràn ngang, không lỗi JavaScript. Ca backend/quyền máy chủ chưa thuộc kiểm tra này.

### Dữ liệu và đề xuất cho TV1

| Dữ liệu | Thiết kế |
|---|---|
| Tài khoản | tai_khoan_id, email UNIQUE, mssv UNIQUE nullable cho nhân viên, mat_khau_hash, vai_tro, trang_thai |
| Nhân viên | nhan_vien_id, tai_khoan_id UNIQUE FK, ma_nhan_vien UNIQUE, ho_ten |
| Trường demo chưa có đủ trong nền | Điện thoại/chức vụ/trạng thái làm việc nhân viên cần TV1 chốt nơi lưu; không tự thêm SQL |
| Họ tên sinh viên | Phục vụ đăng ký thử; hồ sơ doc_gia thuộc TV2, chưa tự tạo hồ sơ thật |

Đề xuất đăng nhập chính thức bằng email hoặc MSSV và mật khẩu. Username của demo là khóa hiển thị, không yêu cầu tự thêm cột username vào nền. Sinh viên cần MSSV, nhân viên có thể null. Nhãn Hoạt động/Khóa ánh xạ active/locked. Các đề xuất cần TV1 xác nhận trước dùng làm hợp đồng B1; chưa tự thay đổi đặc tả.

### API đề xuất ở thời điểm tuần 1 (đã triển khai trong phần tuần 2 bên dưới)

| API | Input/quyền | Kết quả |
|---|---|---|
| POST /api/accounts/register | email, mssv, password; không cho client chọn role | 201 tài khoản student, chờ hồ sơ |
| POST /api/accounts/login | identifier (email/MSSV), password | Auth::login sau password_verify; trả thông tin an toàn |
| POST /api/accounts/logout | phiên/CSRF | Auth::logout; frontend refreshSession |
| PATCH /api/accounts/me/password | current_password, new_password; chính chủ | password_hash mới, xử lý phiên theo hợp đồng |
| GET /api/accounts | admin, phân trang | data[], meta; không trả hash |
| PATCH /api/accounts/:id | admin; vai_tro/trang_thai | tài khoản mới; Auth đọc lại quyền/trạng thái |
| GET/POST/PATCH /api/employees[/:id] | admin | dữ liệu nhân viên liên kết tài khoản |

Envelope data/error, CSRF cho ghi kể cả login/register theo nền. Lỗi 401/403 phiên/quyền, 404 thiếu ID, 409 email/MSSV/mã nhân viên trùng, 422 input. Ví dụ login `{ "identifier": "SV001", "password": "..." }`; không ghi mật khẩu vào log. Gọi refreshSession sau login/logout để nhận token mới. Không gọi ReaderService mới trong tuần 2; trạng thái chờ hồ sơ thể hiện qua chưa có bản ghi doc_gia, cần thống nhất nếu muốn cột trạng thái riêng.

## Kiểm tra chéo và sửa tiếp — 02/11/2026, vẫn thuộc tuần 1

- Đã rà soát mã và chạy lại ca kiểm tra thay nhóm theo yêu cầu người dùng.
- Sửa lỗi thao tác đăng ký/lưu tài khoản/đổi mật khẩu đang băm mật khẩu vẫn ghi dữ liệu sau khi rời màn hình. Truyền AbortSignal vào thao tác và kiểm tra lại trước khi ghi.
- Mỗi lượt hiển thị có tín hiệu hủy riêng; chuyển màn hình hoặc đóng dialog hủy thao tác đang chờ. Kết quả đăng nhập cũ không được ghi đè màn hình mới.
- Không mount khi signal đã hủy; cleanup hủy cả listener và thao tác đang chờ, dọn dialog.
- PASS test hủy giữa chừng: đăng ký, lưu tài khoản và đổi mật khẩu đều không đổi dữ liệu. PASS Edge desktop/mobile.
- PASS thử router nền: trì hoãn băm mật khẩu, rời accounts-demo sang readers rồi cho hoàn tất băm; readers không bị đổi màn hình và tài khoản bị hủy không đăng nhập được.
- Session/health của môi trường thử là fixture; không thay nghiệm thu Auth/PDO/CSRF tuần 2 và chưa merge main.

## Tuần 2 — đã triển khai, chờ kiểm thử HTTP/MariaDB

- `backend/accounts/AccountService.php` và `routes.php`: đăng ký sinh viên, đăng nhập email/MSSV, đăng xuất, đổi mật khẩu, danh sách/tạo/sửa tài khoản, danh sách/tạo/sửa nhân viên. Mật khẩu dùng `password_hash`/`password_verify`; kết quả API không trả hash. Ghi thay đổi nhân viên và tài khoản liên kết trong cùng giao dịch; ngừng làm việc khóa tài khoản. Có thể tạo tài khoản nhân viên mới cùng hồ sơ hoặc liên kết tài khoản nhân viên đã tạo trước bằng `tai_khoan_id`.
- Quyền admin kiểm tra tại backend cho quản lý tài khoản/nhân viên; độc giả chỉ tự đổi mật khẩu. Tự khóa hoặc tự bỏ quyền quản lý bị từ chối. Tài khoản bị khóa không đăng nhập và phiên hiện có bị vô hiệu ở lần gọi API kế tiếp. Đăng ký không nhận vai trò từ client; tài khoản sinh viên mới active nhưng chưa có `doc_gia`/thẻ.
- `frontend/accounts/live-login.js`, `live.js` và route sản phẩm `/accounts`, `/employees`, `/account/password` gọi API chung và `refreshSession`. Trang đầu, menu và đăng xuất dùng phiên máy chủ. Trang `/accounts/demo.html` và `/week1/accounts` vẫn giữ dữ liệu mẫu B1 để tham khảo.
- `backend/accounts/bootstrap-admin.php` chỉ chạy CLI, đặt mật khẩu ban đầu một lần cho tài khoản `admin@example.test` trong seed vì seed cố ý chứa hash vô hiệu. Không ghi đè tài khoản đã có mật khẩu.
- Có thể chạy toàn bộ bằng `docker compose up --build -d` sau khi sao chép `compose.env.example` thành `.env` và thay ba mật khẩu; xem hướng dẫn Docker ở đầu `README.md`. Compose tự nhập schema/seed trên volume mới, chạy migration và bootstrap admin.

### Chuẩn bị và chạy

Sau khi cài `schema.sql`, `seed.sql`, `001_b1.php` trên DB riêng và cấu hình `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`:

```powershell
$env:ADMIN_BOOTSTRAP_PASSWORD = 'mat-khau-quan-ly-it-nhat-8-ky-tu'
php backend/accounts/bootstrap-admin.php
Remove-Item Env:ADMIN_BOOTSTRAP_PASSWORD
php -S 127.0.0.1:8080 -t frontend backend/dev-router.php
```

Mở `http://127.0.0.1:8080`, đăng nhập bằng `admin@example.test` và mật khẩu vừa đặt. Tài khoản khác trong seed vẫn có hash vô hiệu; tạo tài khoản mới từ màn quản lý hoặc đăng ký sinh viên để thử đăng nhập.

### Kiểm tra đã chạy

- `php -l` cho ba file PHP của module: PASS; `node --check` cho file JavaScript mới: PASS.
- `node frontend/accounts/week1.test.mjs`: PASS, demo B1 không bị hỏng.
- `php -d extension=C:/php/ext/php_pdo_sqlite.dll backend/accounts/test-service.php`: PASS 28 ca service trên SQLite trong bộ nhớ, gồm chuẩn hóa/trùng email-MSSV, vai trò, mật khẩu, khóa phiên, quản lý tài khoản, giao dịch nhân viên và liên kết tài khoản. Bộ thử loại bỏ `FOR UPDATE` riêng cho SQLite; đây không thay kiểm thử MariaDB.
- `backend/accounts/http-test.mjs` là ca kiểm thử HTTP/CSRF/phiên/API trên DB thật. Chỉ cho chạy khi `TEST_URL` là 127.0.0.1 và `DB_NAME` kết thúc `_test`. Chưa chạy do hiện không có DB thử/MariaDB. Khi có DB thử đã bootstrap admin, đặt `TEST_URL`, `DB_NAME`, `TEST_ADMIN_PASSWORD` rồi chạy `node backend/accounts/http-test.mjs`.

## Tuần 3 — báo cáo nền và chính sách đọc từ database

Thiết kế B2 riêng cho phạm vi TV4: [CHINH_SACH_B2_TV4.md](CHINH_SACH_B2_TV4.md). Đây là bản đề xuất để triển khai/đối chiếu báo cáo và SettingsService; không thay B2 chung hoặc các giá trị đã chốt ở B1.

- `backend/reports/ReportService.php` cung cấp báo cáo chỉ đọc từ `sach`, `ban_sach`, `doc_gia`, `the_doc_gia`, `tai_khoan`; `SettingsService.php` đọc và kiểm tra khóa B1 từ `cau_hinh`. API `GET /api/reports/baseline` và `GET /api/settings` chỉ dành cho admin. Chưa đăng ký `PATCH /api/settings` vì B2 TV4 chưa cho phép ghi.
- `frontend/reports/` có màn hình báo cáo sách và tình trạng bản sách, độc giả/thẻ, tài khoản; tìm kiếm, lọc thể loại/lưu hành/tình trạng bản, vai trò/trạng thái và thanh tổng hợp từ cùng dữ liệu. Route sản phẩm `#/reports` và `#/settings` đọc API thật khi DB chạy; chỉ admin được vào.
- Báo cáo và form chính sách có trạng thái đang tải, lỗi với nút thử lại, dữ liệu nguồn trống và bộ lọc không có kết quả. Form tại `#/settings` kiểm tra giới hạn nhập, xem trước thay đổi và khôi phục giá trị hiện tại. Chế độ demo không lưu vào database; khi B2 chung cho phép ghi và nguồn API có `saveSettings`, frontend mới hiện nút lưu.
- `frontend/reports/data.js` tách nguồn dữ liệu khỏi màn hình. `createApiReportSource({ reportsPath, settingsPath })` dùng API client chung: GET reports trả `{ data: { books, readers, accounts } }`, GET settings trả `{ data: policies }`. `saveSettings` chỉ được tạo khi có quyền ghi được B2 chung phê duyệt. Trang demo vẫn dùng dữ liệu mẫu B1 có nhãn rõ ràng.
- Trang `frontend/reports/demo.html` xem trước không cần DB. Chạy `php -S 127.0.0.1:8084 -t frontend`, mở `http://127.0.0.1:8084/reports/demo.html?view=reports` hoặc `?view=settings`. Menu thử trạng thái cho phép xem có dữ liệu, trống, lỗi một lần và lỗi liên tục.
- Kiểm tra cú pháp JavaScript, bảng/bộ lọc, chuyển tab, tải/trống/lỗi/thử lại, form và màn hình 390px của bản demo ban đầu: PASS bằng Edge headless. Backend tuần 3: `php -d extension=C:/php/ext/php_pdo_sqlite.dll backend/reports/test-service.php` PASS 19 kiểm tra service/route, gồm quyền, dữ liệu thiếu thẻ/hồ sơ, sách ngừng lưu hành, cấu hình thiếu/sai và PATCH bị từ chối; `php -l` và `node --check` PASS. Ngày 06/11/2026 đã thử HTTP trên Docker/MariaDB hiện có: chưa đăng nhập nhận 401 ở hai API; admin đăng nhập thành công, GET reports/settings nhận 200 (2 đầu sách, 1 độc giả, 5 tài khoản, 14 khóa chính sách); PATCH settings nhận 405 và giá trị không đổi. Truy vấn MariaDB độc lập xác nhận 2 đầu sách, 4 bản, 2 bản khả dụng, 1 độc giả, 5 tài khoản. Chưa chạy kiểm thử giao diện sản phẩm trên trình duyệt với phiên admin và chưa kiểm thử quyền 403 trên HTTP thật.

## Tuần 4 — chưa thực hiện

Notifications và báo cáo yêu cầu/bàn giao; thử service sự kiện độc lập từ B3.

## Tuần 5 — chưa thực hiện

Báo cáo trả/quá hạn/phạt/đọc, xuất tệp và thông báo từ B4; chưa dùng khoản thu TV3 đang viết cùng tuần.

## Tuần 6 — chưa thực hiện

Hồi quy, bổ sung báo cáo đã thu từ B5, kiểm tra số liệu và quyền; bàn giao.

## Điều kiện chuyển tuần

Đã có giao diện/thiết kế tuần 1 để review. B1 chung vẫn cần TV1 chốt đề xuất email/MSSV, các trường nhân viên, tích hợp PR và kiểm tra chung. Không coi đăng nhập demo là xác thực backend hoàn thành.
