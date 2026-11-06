# Đề xuất B2 riêng TV4 — báo cáo nền và chính sách, tuần 3

**Trạng thái:** Bản thiết kế của TV4, ngày 05/11/2026. Đây không phải B2 chung đã được TV1/nhóm phê duyệt. TV4 có thể dùng tài liệu này để triển khai phần báo cáo chỉ đọc trên schema hiện có. Khi B2 chung được công bố, các điểm khác biệt phải được chốt lại trước khi bàn giao tích hợp chính thức hoặc cho phép lưu chính sách.

**Căn cứ:** `CHINH_SACH_B1.md`, `database/schema.sql`, `database/seed.sql`, `database/migrations/001_b1.php`, bản `KE_HOACH_THEO_TUAN.md` người dùng cung cấp, và giao diện hiện có tại `frontend/reports/`. Các quy tắc nghiệp vụ đã chốt ở B1 tiếp tục có hiệu lực. Tài liệu TV4 không tự sửa schema, dữ liệu nền hay quy tắc của TV1–TV3.

## 1. Phạm vi tuần 3

TV4 làm báo cáo tức thời cho **sách/bản sách, độc giả/thẻ, tài khoản** và `SettingsService` đọc cấu hình từ `cau_hinh`. Hoàn thiện API tài khoản tuần 2 nếu phát hiện lỗi khi tích hợp. Route sản phẩm hiện đọc số liệu từ API; trang demo độc lập vẫn dùng dữ liệu mẫu B1.

Chưa đưa phiếu yêu cầu mượn, bàn giao, trả, quá hạn, tiền phạt, khoản đã thu, eBook và thông báo vào báo cáo tuần 3. Các nghiệp vụ này thuộc các tuần sau hoặc đang do TV3 phát triển. Không hiển thị “doanh thu” từ khoản phải thu.

## 2. Định nghĩa số liệu

| Chỉ tiêu | Nguồn và cách đếm | Lưu ý |
|---|---|---|
| Đầu sách | Mỗi hàng `sach` tính một đầu sách, kể cả sách ngừng lưu hành. Thể loại lấy qua `sach.the_loai_id`. | Không nhân số đầu sách theo tác giả hay bản sách khi `JOIN`. |
| Tổng bản sách | Mỗi hàng `ban_sach` tính một bản. | Bản mất/hỏng vẫn nằm trong tổng để phản ánh kho đã ghi nhận. |
| Bản khả dụng | Chỉ đếm bản có `ban_sach.tinh_trang = 'available'`, `ban_sach.dang_luu_hanh = TRUE` **và** `sach.dang_luu_hanh = TRUE`. | Bản `available` nhưng ngừng lưu hành không được xem là cho mượn. |
| Bản đang mượn/hỏng/mất | Đếm từng `ban_sach` theo giá trị `tinh_trang` tương ứng. | Đây là tình trạng được lưu, không phải số phiếu mượn hoặc số khoản phạt. |
| Độc giả có hồ sơ | Mỗi hàng `doc_gia` tính một độc giả. Liên kết `tai_khoan` bằng `tai_khoan_id`; liên kết thẻ bằng `LEFT JOIN the_doc_gia`. | Tài khoản sinh viên chưa có `doc_gia` thuộc báo cáo tài khoản, không được tính là độc giả. Độc giả chưa có thẻ vẫn phải xuất hiện. |
| Trạng thái thẻ | Đếm thẻ theo `the_doc_gia.trang_thai` và nhóm riêng “chưa có thẻ”. | `active` là trạng thái lưu trong DB; thẻ đã quá `ngay_het_han` không được gọi là “còn hiệu lực”. Nếu cần chỉ tiêu hiệu lực, thống nhất quy tắc với TV2 và dùng ngày theo `Asia/Ho_Chi_Minh`. |
| Tài khoản | Mỗi hàng `tai_khoan` tính một tài khoản; nhóm theo `vai_tro` và `trang_thai`. | Hồ sơ `doc_gia`/`nhan_vien` không tạo thêm tài khoản. Không trả `mat_khau_hash`. |

Đây là **ảnh chụp hiện tại** của các bảng nền. Tuần 3 không áp bộ lọc khoảng ngày cho toàn bộ ba báo cáo vì `sach` và `doc_gia` chưa có cột thời điểm tạo trong schema hiện tại. `tai_khoan.created_at` chỉ đủ cho một báo cáo tài khoản theo kỳ riêng nếu B2 chung yêu cầu. Các bộ lọc thể loại, lưu hành, tình trạng bản, trạng thái thẻ, vai trò và trạng thái tài khoản lọc trên các giá trị đã lưu. Với dữ liệu lớn, tổng hợp phải do backend tính trên toàn bộ tập phù hợp bộ lọc, không lấy độ dài của một trang kết quả làm tổng số.

Khi trình bày tình trạng kho, cần có nhóm **bản `available` nhưng không lưu hành** hoặc ghi rõ nhóm này không thuộc “khả dụng”; nếu chỉ cộng bốn nhóm khả dụng/đang mượn/hỏng/mất thì tổng có thể nhỏ hơn tổng bản. Không tự đổi `tinh_trang` của bản chỉ để làm số liệu khớp.

## 3. Chính sách và `SettingsService`

Nguồn giá trị đang áp dụng là bảng `cau_hinh`, được tạo bởi `seed.sql` và migration B1. `.env` chỉ chứa cấu hình môi trường như kết nối DB, không phải chính sách nghiệp vụ. Backend đọc giá trị từ DB, kiểm tra kiểu và xử lý thiếu/sai cấu hình thành lỗi rõ ràng; không thay bằng hằng số âm thầm.

| Nhóm khóa | Giá trị B1 hiện tại | Trạng thái thiết kế TV4 |
|---|---|---|
| `borrow_per_request`, `borrow_total`, `borrow_days` | 3, 7, 14 | Form hiện cho xem trước các mức 1–3, 1–7, 1–14. Đây là giới hạn nhập của UI, **chưa phải quyền thay đổi quy tắc B1**. |
| `renewal_limit`, `renewal_days`, `renewal_overdue_days` | 2, 7, 1 | Form xem trước các mức 0–2, 1–7, 0–1; chờ B2 chung duyệt quyền lưu. |
| `ebook_per_request`, `ebook_days` | 3, 14 | Form xem trước các mức 1–3, 1–14; chờ B2 chung duyệt quyền lưu. |
| `notice_days_before_due`, `notice_hour_local` | 2, `09:00` | Form xem trước 0–30 ngày và giờ `HH:mm`; phạm vi này là đề xuất TV4, chờ xác nhận trước khi ghi DB. |
| `overdue_daily_vnd`, `card_duration`, `business_timezone`, `damage_surcharge` | 2000, `12_months`, `Asia/Ho_Chi_Minh`, 0 | Chỉ đọc/hiển thị; không có điều khiển sửa trong form TV4. |

**Chế độ an toàn hiện tại:** `SettingsService` có thể cung cấp `GET` cho admin. Chưa bật `PATCH` ghi DB chỉ dựa trên khoảng nhập của frontend. TV1/nhóm cần xác nhận chính xác danh sách khóa được chỉnh, khoảng giá trị và thời điểm áp dụng. Nếu B2 chung giữ các mức B1 là cố định thì form chỉ đọc/xem trước, không lưu. Nếu B2 chung cho chỉnh, backend dùng danh sách khóa cho phép tường minh và các bước sau:

1. Chỉ `admin` có phiên hợp lệ được xem/sửa; thao tác `PATCH` phải qua CSRF của API chung. Kiểm tra quyền ở backend, không dựa vào route frontend.
2. Chỉ nhận các khóa đã duyệt, đúng kiểu và đúng khoảng; từ chối khóa lạ, giá trị rỗng hoặc vi phạm quan hệ như `borrow_total < borrow_per_request`. `PATCH` chỉ thay các khóa được gửi, không ghi đè toàn bộ bảng.
3. Cập nhật các khóa trong một transaction; lỗi ở một khóa thì hoàn tác cả yêu cầu. Ghi `nhat_ky_thao_tac` với người thao tác và khóa cấu hình đã đổi. Schema hiện tại không có cột lưu giá trị cũ/mới; muốn nhật ký chi tiết phải có thay đổi schema được TV1 duyệt.
4. Trả lại giá trị chuẩn hóa từ DB sau khi lưu; frontend chỉ coi thành công khi API xác nhận. Khi nhiều admin sửa đồng thời, cần chốt cách phát hiện ghi đè (ví dụ version/ETag) trước khi bật sửa thật.

Việc đổi cấu hình chỉ áp dụng theo thời điểm do B2 chung chốt. Các giao dịch đã có snapshot chính sách của mình không được tự tính lại từ giá trị mới.

## 4. Hợp đồng dữ liệu để nối frontend

`frontend/reports/data.js` nhận đường dẫn API qua `createApiReportSource({ reportsPath, settingsPath })`. TV4 dùng tạm `GET /api/reports/baseline` và `GET /api/settings` để tích hợp tuần 3; đường dẫn chính thức vẫn cần đối chiếu với TV1. Hợp đồng của bản tích hợp đầu tiên:

- `GET reportsPath` → `{ "data": { "books": [], "readers": [], "accounts": [] } }`.
- `GET settingsPath` → `{ "data": { "borrow_per_request": 3, "...": "..." } }`, với các khóa B1 mà form đang đọc.
- `PATCH settingsPath` → chưa đăng ký ở backend TV4. Nếu B2 chung cho phép, body chỉ gồm khóa thay đổi và áp dụng quy tắc mục 3.

Mỗi phần tử `books` cần `ma_sach`, `ten_sach`, tên `the_loai`, `dang_luu_hanh`, `copies[]` gồm `tinh_trang`, `dang_luu_hanh`. Mỗi phần tử `readers` cần `ma_the`, `ho_ten`, `email`, `mssv`, `ngay_het_han`, `trang_thai`; độc giả chưa có thẻ dùng `null` cho các trường thẻ. Mỗi phần tử `accounts` cần `email`, `mssv`, `vai_tro`, `trang_thai`. Email/MSSV chỉ được trả cho admin theo nhu cầu báo cáo; không trả hash, số điện thoại hay địa chỉ.

Hợp đồng mảng hiện tại phù hợp dữ liệu thử nhỏ. Trước khi dùng với dữ liệu lớn, TV4 và TV1 cần chốt phân trang/lọc phía server và tổng số độc lập với trang dữ liệu. Frontend đã bổ sung nhãn “Chưa có thẻ” và nhóm bản sẵn có nhưng ngừng lưu hành nêu ở mục 2. Không tự lấy nhiều trang rồi tính tổng ở trình duyệt.

Lỗi xác thực/quyền dùng quy ước API chung: 401 khi chưa đăng nhập, 403 khi không đủ quyền; 422 cho dữ liệu chính sách sai. Trạng thái tải, rỗng, lỗi và thử lại của frontend đã có trong `frontend/reports/`.

## 5. Đối chiếu trước khi bàn giao B2

- Với dữ liệu mẫu từ `seed.sql`, kiểm tra 2 đầu sách, 4 bản, 2 bản khả dụng, 1 bản hỏng, 1 bản mất, 1 hồ sơ độc giả và 4 tài khoản. Dữ liệu fixture frontend chỉ là tham chiếu giao diện, không thay phép kiểm trên DB thật.
- Thêm ca tài khoản sinh viên chưa có hồ sơ, độc giả chưa có thẻ, tài khoản/thẻ bị khóa, bản `available` nhưng sách hoặc bản ngừng lưu hành, và cả ba nguồn trống. Đối chiếu tổng với truy vấn DB, không đếm trùng do `JOIN`.
- Xác nhận route chỉ admin dùng được, phiên admin bị khóa không dùng tiếp, API không lộ hash và `PATCH` không ghi khi CSRF/quyền/validation thất bại.
- Route sản phẩm hiện đã đọc nguồn API và đã kiểm thử HTTP/MariaDB chỉ đọc trên dữ liệu hiện có. TV1/nhóm vẫn cần xác nhận đường dẫn và dạng trả về API, danh sách khóa cấu hình được sửa, cách xử lý cập nhật đồng thời và phụ thuộc bảng/API của TV2 trước khi bàn giao tích hợp chính thức.

Các điểm chưa được xác nhận ở trên là **đầu vào cần chốt của B2 chung**, không phải phần TV4 tự tuyên bố đã hoàn thành.
