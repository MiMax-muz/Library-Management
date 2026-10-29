# Kế hoạch theo tuần — Làm độc lập theo bản nền tuần trước

### Mốc thời gian áp dụng cho mọi tuần

| Thời điểm tương đối | Công việc và hạn bàn giao |
|---|---|
| Trước Ngày 1 | TV1 chốt nền đầu vào, SQL và API; nếu nền chưa đạt thì chưa bắt đầu việc phụ thuộc |
| Ngày 1–2 | Mỗi người thực hiện nhóm việc đầu trong bảng tiến độ của tuần |
| Ngày 3–4 | Hoàn thiện nhóm việc tiếp theo, chỉ dùng nền trước và code tự làm |
| Ngày 5 | Tự kiểm tra; cuối ngày nộp Pull request, cách thử và giới hạn; gửi nhu cầu SQL/API cho tuần sau |
| Ngày 6 | Kiểm tra chéo phần đã bàn giao; TV1 tích hợp từng Pull request và sửa file chung |
| Ngày 7 | Chạy luồng tích hợp/cài mới/nâng cấp; TV1 chốt nền mới hoặc ghi phần phải lùi |

Ngày 1–5 không yêu cầu chờ hoặc duyệt code đang viết của nhau. Ngày 6–7 là giai đoạn phối hợp được dự trù riêng. Các thời điểm là kế hoạch dự kiến, không phải công việc đã hoàn thành.

## 1. Quy tắc và bước chuẩn bị B0

**Trong tuần N, không chờ hoặc gọi phần người khác đang viết ở tuần N. Chỉ dùng đầu ra đã kiểm tra của tuần trước hoặc tự hoàn thành phần thuộc module mình.**

Trước tuần 1, TV1 ghi cấu trúc folder, quyền sửa file, tên trường dự kiến và định dạng route/API; mọi người thống nhất nhu cầu chức năng. Tạo repository tối thiểu B0 để mỗi người có cùng điểm xuất phát. Chưa cần khung ứng dụng hoạt động ở B0.

Cuối mỗi tuần có khoảng thời gian tích hợp riêng: bàn giao → kiểm tra chéo → TV1 sửa file chung/SQL cần thiết → gộp vào develop → chạy kiểm tra → phát hành nền mới nếu đạt. Không coi thời gian kiểm tra tích hợp là không cần thiết chỉ vì các thành viên làm độc lập trong tuần.

Mọi file chung do TV1 sửa: khởi động/route tổng/shared/cấu hình, database, jobs, thư viện/công cụ, README và kế hoạch. TV2–TV4 gửi đề xuất qua Pull request/trao đổi, không tự sửa file chung. TV4 vẫn sở hữu accounts/notifications/reports, TV2 sở hữu readers/bookshelf, TV3 sở hữu loans/fines; TV1 sở hữu books/ebooks.

### Quy định thực hiện 4 điểm đã thống nhất

- **Ranh giới TV2/TV3:** TV2 viết hồ sơ/thẻ và giao diện lịch sử trong readers; TV3 viết API và truy vấn lịch sử mượn/trả trong loans. TV3 hoàn tất và bàn giao API lịch sử vào B4; TV2 dùng B4 để hiển thị trong tuần 5. Lịch sử đọc điện tử thuộc bookshelf của TV2.
- **Thiết kế và tự kiểm tra:** mỗi thành viên chuẩn bị bảng/trường/khóa, hợp đồng API, dữ liệu mẫu và ca kiểm tra cho phần mình. Chậm nhất Ngày 5 tuần trước, gửi thiết kế đầu vào cần cho tuần sau; Ngày 6–7 TV1 xem xét, trực tiếp tạo/cập nhật SQL và file chung, rồi kiểm tra để chốt nền. Đề xuất chưa được đưa vào nền không được coi là đã sẵn sàng.
- **Ưu tiên:** tuần 1–2 ưu tiên khung, xác thực, sách và thẻ; tuần 3–4 ưu tiên mượn/trả/phạt và đọc PDF. TV4 chỉ làm báo cáo nền đơn giản khi phần tài khoản đã đạt; báo cáo liên module/xuất tệp nằm ở tuần 5–6 khi dữ liệu nguồn đã có. Nếu thiếu thời gian, lùi báo cáo nâng cao trước, không bỏ xác thực hay chức năng đọc PDF đã nhận.
- **Kiểm tra định kỳ:** Ngày 1–5 mỗi người tự kiểm tra; Ngày 6 kiểm tra chéo; Ngày 7 kiểm tra bản tích hợp và điều kiện nền. Không yêu cầu thành viên chờ đầu ra đang viết của nhau trong Ngày 1–5, cũng không dồn kiểm thử đến tuần cuối.

## 2. Ma trận đầu ra nền

| Mốc đã kiểm tra | Nội dung bắt buộc để tuần sau dùng |
|---|---|
| B0 | Folder, quy ước tên/API/route và phạm vi sửa; chưa cần app chạy |
| B1 — cuối tuần 1 | Khung chạy, API client/router/PDO/phiên, schema + dữ liệu thử cho tuần 2, giao diện độc lập của từng người |
| B2 — cuối tuần 2 | API sách/bản sách, kiểm tra thẻ/hồ sơ, đăng nhập/tài khoản, bộ tính phạt độc lập; schema cho eBook và mượn giấy |
| B3 — cuối tuần 3 | PDF có quyền và metadata phiên bản; yêu cầu/duyệt/bàn giao; hồ sơ/thẻ hoàn chỉnh; cấu hình và báo cáo dữ liệu nền |
| B4 — cuối tuần 4 | API tủ sách/tiến độ, trả/gia hạn/phạt và API lịch sử mượn/trả có phân quyền, quyền mượn eBook, service thông báo; đủ dữ liệu cho tuần 5 |
| B5 — cuối tuần 5 | Trình đọc nối tiến độ, lịch sử cá nhân, thu tiền, nhắc hạn và báo cáo tích hợp |
| B6 — cuối tuần 6 | Bản đã kiểm tra toàn bộ, hướng dẫn cài/chạy và giới hạn được ghi nhận |

B là mốc logic; có thể dùng tag base-w1…base-w6. Mốc không phát hành chỉ vì đã hết tuần. Nếu B3 thiếu metadata eBook, không giao TV2 việc phụ thuộc metadata đó ở tuần 4; đổi việc độc lập hoặc lùi lịch.

## 3. Tuần 1 — Khung và màn hình độc lập

**Đầu vào duy nhất: B0. Không cần code mới của nhau trong tuần.**

| Người | Frontend | Backend/dữ liệu | Tự kiểm tra và bàn giao |
|---|---|---|---|
| TV1 | index.html, app.js, config.js, shared; mẫu cách nạp module | Khung PHP, PDO, response/lỗi, cơ chế phiên/quyền dùng chung; schema/seed đủ cho tuần 2 | Trang đầu vào, API kiểm tra, kết nối DB; cấu hình môi trường và hướng dẫn chạy |
| TV2 | readers: danh sách, form hồ sơ, thẻ bằng dữ liệu mẫu module | Soạn trường dữ liệu/điều kiện thẻ gửi TV1; chưa cần API đang viết của ai | Trường bắt buộc, danh sách trống, lỗi nhập; gửi giao diện độc lập và nhu cầu SQL |
| TV3 | loans/fines: màn hình yêu cầu, duyệt, bàn giao, khoản phạt bằng dữ liệu mẫu | Mô tả trạng thái/điều kiện và bảng đề xuất | Thử luồng màn hình, trạng thái và dữ liệu sai; không coi là mượn thật |
| TV4 | accounts: đăng nhập/đăng ký, tài khoản/nhân viên bằng dữ liệu mẫu | Soạn hợp đồng xác thực/tài khoản, đề xuất bảng và dữ liệu thử | Thử form và lỗi; không cần phiên thật từ TV1 để tự thử UI |

## 4. Tuần 2 — Các module nền không gọi nhau

**Đầu vào: B1.**

| Người | Frontend | Backend | Không phụ thuộc cùng tuần |
|---|---|---|---|
| TV1 | books: tìm/lọc, chi tiết, form sách/bản sách/danh mục | API CRUD và tra cứu; thao tác kho và cập nhật có điều kiện cho tuần 3 | Dùng schema/fixture B1, không chờ tài khoản hoặc thẻ mới |
| TV2 | Nối readers với API của chính mình | Hồ sơ/thẻ, kiểm tra hiệu lực; dùng tài khoản mẫu B1 | Không gọi AccountService TV4 đang viết |
| TV3 | Hoàn thiện fines với công cụ xem trước phép tính; loans tiếp tục chuẩn bị giao diện | Bộ tính phạt độc lập nhận ngày/vi phạm/chính sách, chưa tạo phiếu/thu tiền; tự kiểm tra biên ngày/tiền | Không chờ API sách hoặc thẻ; chưa làm bàn giao thật |
| TV4 | Nối accounts với API của chính mình | Đăng nhập/đăng xuất, mật khẩu, tài khoản/nhân viên; dùng cơ chế phiên B1 | Không gọi ReaderService mới; đăng ký tạo tài khoản chờ hồ sơ |

## 5. Tuần 3 — Dùng sách/thẻ/tài khoản có sẵn

**Đầu vào: B2.**

| Người | Frontend | Backend | Phụ thuộc đã có |
|---|---|---|---|
| TV1 | ebooks: tìm, chi tiết, upload và đọc PDF cơ bản | Metadata, phiên bản tệp, quyền đọc trực tiếp và truyền nội dung riêng tư | Sách và xác thực B2; chưa nối tiến độ của TV2 |
| TV2 | Hoàn thiện hồ sơ cá nhân và thẻ | Liên kết hồ sơ với tài khoản, kiểm tra quyền dữ liệu; làm sạch trường hợp biên | Tài khoản B2; chưa làm tủ sách dựa vào eBook mới |
| TV3 | loans: gửi yêu cầu, duyệt/từ chối và bàn giao | Kiểm tra thẻ/hạn mức, tạo phiếu lúc giao, cập nhật kho cùng transaction | Sách/thẻ/xác thực B2; không chờ thông báo mới |
| TV4 | reports: báo cáo sách/độc giả/tài khoản; form chính sách | Truy vấn dữ liệu nền và SettingsService; hoàn thiện accounts nếu cần | Bảng/API B2; chưa báo cáo phiếu đang được TV3 xây |

## 6. Tuần 4 — Tủ sách, trả sách và thông báo độc lập

**Đầu vào: B3.**

| Người | Frontend | Backend | Phụ thuộc đã có |
|---|---|---|---|
| TV1 | ebooks: mượn điện tử, hiển thị hạn/quyền; books: kiểm kê | Quyền mượn có hạn, thu hồi, kiểm kê; chưa tích hợp tiến độ đang viết | Metadata/PDF B3 |
| TV2 | bookshelf: tủ sách, yêu thích, dấu trang, trang kiểm thử tiến độ | API lưu/lấy tiến độ và lịch sử đọc gắn phiên bản; tự thử bằng API/màn hình của mình | Metadata/tệp B3; không cần TV1 sửa reader.js tuần này |
| TV3 | loans: trả/gia hạn/quá hạn; fines: xác nhận phạt | Trả từng bản, gia hạn, mất/hỏng; dùng bộ tính B2 để ghi phạt chống trùng; hoàn thiện API lịch sử mượn/trả có lọc và phân quyền | Phiếu/bàn giao B3 |
| TV4 | notifications và báo cáo yêu cầu/bàn giao | Service tạo/xem/đánh dấu thông báo; thống kê từ phiếu đã có; thử thông báo bằng fixture sự kiện B3 | Yêu cầu/phiếu B3; chưa gọi trả/phạt mới |

## 7. Tuần 5 — Nối các chức năng đã hoàn thành

**Đầu vào: B4.**

| Người | Frontend | Backend/file liên quan | Phụ thuộc đã có |
|---|---|---|---|
| TV1 | Nối reader.js với tiến độ/dấu trang; tích hợp menu chung | Tạo jobs nhắc hạn/hết hạn bằng service nguồn; điều phối shared | Tiến độ TV2, trả/phạt TV3, thông báo TV4 đều thuộc B4 |
| TV2 | Lịch sử mượn/trả cá nhân; hoàn thiện tủ sách | Dùng API lịch sử của TV3 trong B4; không viết lại truy vấn/bảng giao dịch; kiểm tra chủ dữ liệu/phiên bản; cung cấp dữ liệu đọc cho báo cáo | Không cần thay đổi reader.js của TV1 tuần này |
| TV3 | fines/payment.html: thu tiền và lịch sử thanh toán | Ghi nhận thu tiền chống trùng; tự kiểm tra các luồng hoàn tác/đồng thời | Khoản phạt B4 |
| TV4 | Báo cáo trả/quá hạn/phạt/đọc điện tử, xuất tệp | Truy vấn dữ liệu B4; hoàn thiện thông báo | Báo cáo tiền phạt đã ghi nhận, chưa yêu cầu báo cáo khoản thu mới tuần 5 |

## 8. Tuần 6 — Ổn định và bàn giao

**Đầu vào: B5.**

| Người | Phần riêng thực hiện | Kết quả |
|---|---|---|
| TV1 | Sửa books/ebooks và file chung; chốt SQL/cấu hình/tài liệu; kiểm tra nâng cấp và cài mới | Bản triển khai, hướng dẫn, dữ liệu demo và danh sách giới hạn |
| TV2 | Sửa readers/bookshelf; kiểm tra mất quyền, sai phiên bản, hồ sơ/thẻ; gửi nhận xét hướng dẫn | Module cá nhân ổn định và kết quả tự kiểm tra |
| TV3 | Sửa loans/fines; thử giao/trả/thu lặp, tranh chấp bản cuối, lỗi lưu dữ liệu | Bằng chứng bảo toàn dữ liệu và các lỗi đã sửa |
| TV4 | Sửa accounts/notifications/reports; thêm báo cáo đã thu nếu cần từ dữ liệu B5 | Báo cáo khớp nguồn, đúng quyền, không trộn số phải thu và đã thu |

## 9. Nhánh và lịch tích hợp
### Các nhánh cố định

| Nhánh | Người phụ trách | Mục đích |
|---|---|---|
| main | TV1 quản lý | Nhánh chính, chứa code đã kiểm tra và tích hợp |
| tv1 | TV1 — trưởng nhóm | File chung, SQL, cấu hình, tài liệu, sách và eBook |
| tv2 | TV2 | Độc giả, thẻ thư viện và tủ sách |
| tv3 | TV3 | Mượn, trả, gia hạn và tiền phạt |
| tv4 | TV4 | Tài khoản, nhân viên, thông báo và báo cáo |

### Làm việc trong tuần

1. Đầu tuần, TV1 xác nhận bản main ổn định dùng làm nền.
2. Mỗi thành viên cập nhật bản main đã chốt vào nhánh của mình.
3. Trong tuần, viết code, tự kiểm tra, commit và push trên nhánh cố định.
4. Không lấy code đang viết của người khác làm đầu vào trong cùng tuần.
5. Chỉ TV1 trực tiếp sửa file dùng chung; thành viên khác gửi đề xuất.
6. Cuối tuần, mở Pull Request từ nhánh thành viên vào main.

### Kiểm tra và gộp cuối tuần

1. Mỗi người bàn giao phần đã tự kiểm tra, ghi bản nền sử dụng, file thay đổi, cách chạy và lỗi còn lại.
2. Người liên quan xem pull request. pull request của TV1 cũng cần thành viên khác xem lại.
3. TV1 sắp xếp thứ tự gộp theo phụ thuộc. Nếu cần SQL/cấu hình để chức năng chạy, TV1 chuẩn bị thay đổi tương thích trước; không gộp chức năng đang thiếu điều kiện chạy.
4. Trước mỗi lần gộp, cập nhật main hiện tại vào nhánh sắp được gộp, xử lý xung đột và kiểm tra lại phần bị ảnh hưởng.
5. Khi pull request đạt yêu cầu, TV1 gộp vào main bằng Create a merge commit.
6. Sau mỗi lần gộp, chạy lại chức năng vừa thêm và các luồng liên quan. Nếu có lỗi, ưu tiên sửa hoặc hoàn tác thay đổi gây lỗi trước khi gộp tiếp.
7. Sau khi tích hợp các phần đạt yêu cầu, cả nhóm kiểm tra luồng tổng thể và cơ sở dữ liệu.
8. TV1 cập nhật README và tạo tag như base-w1, base-w2 để đánh dấu bản nền cuối tuần đã kiểm tra.

Trong giai đoạn tích hợp cuối tuần, các thành viên không tự lấy trạng thái main đang thay đổi làm nền mới. Chờ TV1 xác nhận bản đã kiểm tra.

### Bắt đầu tuần tiếp theo

- Mỗi người cập nhật bản main đã chốt vào nhánh cố định của mình.
- Tiếp tục làm trên đúng nhánh của mình; không cần tạo lại nhánh.
- Nếu phần tuần trước chưa được gộp, ghi rõ và xử lý trước khi đưa thêm thay đổi vào pull request tiếp theo.
- Nếu bản tích hợp chưa đạt, chưa tạo tag nền mới; sửa lỗi hoặc điều chỉnh công việc phụ thuộc.

### Quy định tránh xung đột

- Không push trực tiếp vào main; mọi thay đổi đi qua pull request.
- Không force-push main hoặc tự ý viết lại lịch sử các nhánh.
- Thống nhất dùng Create a merge commit; không dùng squash/rebase merge tùy tiện với các nhánh được tái sử dụng.
- Không bật tự động xóa nhánh thành viên sau khi gộp.
- TV1 tách thay đổi file chung và nghiệp vụ sách/eBook thành các commit rõ mục đích.
- Khi pull request đang được kiểm tra, chỉ bổ sung sửa lỗi hoặc thay đổi thuộc phần bàn giao, chưa đẩy việc tuần sau vào cùng pull request.
- Tag chỉ đánh dấu phiên bản, không phải nhánh mới và không tự ngăn lỗi.
- Git gộp thành công không đồng nghĩa ứng dụng chạy đúng; luôn kiểm tra sau khi gộp.

