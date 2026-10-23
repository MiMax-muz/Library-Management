# Quản lý thư viện — Tổng quan hệ thống và phân công thành viên

Bản phân công cho nhóm 4 người, triển khai quản lý thư viện và tìm/đọc sách điện tử bằng HTML/CSS/JavaScript, PHP, MySQL. Repository dùng main và tv1–tv4. Đã hoàn thiện phần nền TV1 trước tuần 1 và tuần 1: khung UniLib, API nền, PDO, phiên/quyền, schema/seed và kiểm tra. Prototype vẫn dùng dữ liệu mô phỏng. Chưa phát hành B1 toàn nhóm vì còn đầu ra TV2–TV4 và các quyết định nghiệp vụ cần thống nhất. Xem mục 10 để chạy và tích hợp.

README có nội dung: **hệ thống có những phần nào, ai chịu trách nhiệm frontend/backend/file chung và phải bàn giao chức năng gì**. Lịch và đầu việc theo thời gian chỉ ghi trong [kế hoạch triển khai](../ITTV-LibraryManagement-docs/KE_HOACH_THEO_TUAN.md); không dùng README làm lịch giao việc hằng tuần.

Yêu cầu được cập nhật ngày 21/10/2026 từ báo cáo UML và tài liệu business rule người dùng cung cấp. [Đặc tả nghiệp vụ](../ITTV-LibraryManagement-docs/DAC_TA_NGHIEP_VU.md) ghi đầy đủ 42 use case, 25 business rule, quyền theo actor, tiêu chí nghiệm thu và các điểm còn cần chốt. Đặc tả nghiệp vụ giữ nguyên; trạng thái triển khai TV1 được cập nhật ngày 23/10/2026 ở mục 10. Giữ phân công 4 thành viên hiện tại, không thay bằng danh sách thành viên trong báo cáo nguồn.

## 1. Nguyên tắc làm việc

**Tuần N chỉ sử dụng code và hợp đồng đã hoàn thành, kiểm tra, chốt trước tuần N. Không lên kế hoạch dựa vào đầu ra của người khác trong cùng tuần.**

- Đầu tuần, cập nhật cùng bản main đã chốt vào nhánh cố định tv1–tv4; không tạo lại nhánh mỗi tuần.
- Trong tuần, mỗi người tự phát triển và kiểm tra module mình. Không cần lấy nhánh đang viết của người khác để hoàn thành phần được giao.
- Cuối tuần, kiểm tra chéo Pull Request vào main; TV1 gộp từng phần đạt và kiểm tra lại sau mỗi lần gộp.
- Khi bản tích hợp đạt tiêu chí nghiệm thu, TV1 đánh dấu bản nền mới và thông báo cho nhóm.
- Nếu phần nền lỗi nghiêm trọng, TV1 sửa và thông báo bản vá; mọi người cập nhật có phối hợp.

Hai chức năng cùng tuần có thể làm trên dữ liệu đã có, nhưng không gọi API mới mà người khác đang viết tuần đó. Dữ liệu mẫu chỉ giúp phát triển giao diện, không được tính là tích hợp hoàn chỉnh. Giữ prototype riêng để tham khảo; dữ liệu thử phải tách khỏi luồng sản phẩm. Bỏ chức năng thử không thuộc phạm vi trước khi bàn giao.

## 2. Phân công cố định

| Thành viên | Phần riêng | Trách nhiệm xuyên tuần |
|---|---|---|
| TV1 — trưởng nhóm | books, ebooks | Sách, bản sách, danh mục, kiểm kê, tìm/đọc PDF; trực tiếp sửa mọi file chung, SQL và tài liệu; tích hợp bản nền |
| TV2 | readers, bookshelf | Hồ sơ/thẻ, tủ sách, yêu thích, dấu trang, tiến độ và lịch sử đọc |
| TV3 | loans, fines | Yêu cầu/duyệt/bàn giao, trả/gia hạn, quá hạn, tính phạt và ghi nhận thu tiền |
| TV4 | accounts, notifications, reports | Tài khoản/nhân viên, thông báo, báo cáo và cấu hình nghiệp vụ trong module |

Mỗi người viết frontend và backend của module mình. Người khác được xem và kiểm tra, nhưng không tự thay đổi module đó. Một module có API dùng bởi module khác vẫn thuộc chủ sở hữu của nó; không vì vậy chuyển toàn bộ module thành shared.

TV1 chịu thêm việc nền tảng nên kế hoạch dành giai đoạn đầu để hoàn thành khung chung trước khi mở rộng quản lý sách. TV2–TV4 hỗ trợ bằng đề xuất rõ, dữ liệu mẫu, kết quả kiểm tra; nếu quá tải, giảm phạm vi hoặc tăng thời gian, không âm thầm chuyển file chung cho người khác.

### Trách nhiệm thiết kế, ưu tiên và kiểm tra

1. **TV2 quản lý độc giả/thẻ; TV3 quản lý giao dịch và API lịch sử mượn/trả.** TV2 chỉ gọi API lịch sử đã bàn giao để hiển thị trong hồ sơ; không tạo bảng lịch sử mượn riêng, không viết lại truy vấn giao dịch. TV3 dùng nghiệp vụ kiểm tra hiệu lực thẻ của TV2, không tự xây cơ chế quản lý thẻ khác. Lịch sử đọc eBook vẫn thuộc bookshelf của TV2, khác với lịch sử mượn/trả sách giấy.
2. **Mỗi người tự thiết kế và tự kiểm tra phần mình.** Bản đề xuất gồm bảng/trường/khóa/ràng buộc, API input/output/quyền/lỗi, dữ liệu mẫu và các ca kiểm tra. TV2–TV4 gửi SQL/migration đề xuất; TV1 là người trực tiếp tạo/sửa file SQL, shared và tài liệu chính thức. Thiết kế dữ liệu không đồng nghĩa được tự sửa file dùng chung.
3. **Ưu tiên xác thực và nghiệp vụ chính trước báo cáo nâng cao.** Đăng nhập/phân quyền, sách/bản sách, độc giả/thẻ, mượn/trả/phạt và tìm/đọc PDF là phần cốt lõi. Báo cáo nền đơn giản có thể làm khi nguồn dữ liệu đã ổn định; báo cáo liên module, xuất tệp và thống kê nâng cao chỉ làm sau khi các giao dịch nguồn đã bàn giao. Không lùi tìm/đọc PDF sang phạm vi tùy chọn.
4. **Tích hợp định kỳ cuối mỗi tuần.** Trong thời gian phát triển, mỗi người tự kiểm tra độc lập. Khi bàn giao chỉ đầu ra đã đạt mới được dùng làm nền cho tuần tiếp theo.

## 3. Cấu trúc frontend và công việc

### Giao diện mẫu đã bổ sung

Bộ giao diện từ `Front end.zip` được lưu trong `frontend/prototype/`, giữ cấu trúc gốc để chạy thử. Chạy ứng dụng theo mục 10, chọn **Giao diện mẫu UniLib**; có thể dùng Live Server để xem riêng prototype. Không mở bằng `file://` vì giao diện dùng JavaScript ES modules.

Tài khoản demo: `student`, `librarian`, `staff`, `admin`; mật khẩu chung `123456`. Dữ liệu được lưu mô phỏng bằng LocalStorage, chưa có xác thực hay kết nối backend thật. Việc bổ sung này không thay đổi trạng thái bản nền B0 thành B1.

TV1 quản lý prototype tham khảo; cả nhóm không cùng sửa `prototype/assets/js/main.js`. TV1 đã tách shell/CSS UniLib vào shared trong tuần 1; các thành viên chuyển màn hình ưu tiên sang module, các màn hình còn lại chuyển ở tuần triển khai chức năng tương ứng. TV1 tiếp nhận bố cục, menu, CSS chung; mỗi thành viên phụ trách phần mình theo bảng dưới. Các module hiện tại là cấu trúc triển khai chính; prototype chạy độc lập.

| Người | Màn hình trong prototype | Module đích |
|---|---|---|
| TV1 | Tra cứu/quản lý sách, danh mục, vị trí, kiểm kê, eBook và tệp/quyền đọc | books, ebooks; khung chung trong shared |
| TV2 | Độc giả, tủ sách, yêu thích, lịch sử đọc | readers, bookshelf |
| TV3 | Yêu cầu/duyệt/bàn giao, trả/gia hạn, lịch sử mượn, quá hạn và phạt | loans, fines |
| TV4 | Đăng nhập, tài khoản, nhân viên, thông báo, dashboard và báo cáo | accounts, notifications, reports |

Các tài liệu trong `prototype/docs/` là tài liệu tham khảo của bộ giao diện, không thay thế đặc tả dự án. Cần đối chiếu 45 use case của bản mẫu với 42 use case chính thức theo nội dung. Luồng demo đang tạo phiếu/trừ tồn khi duyệt; khi triển khai phải tách bước bàn giao theo BR07–BR10. Một số màn hình còn là khung; chuyển `USE_MOCK=false` chưa đủ để hoàn thành tích hợp vì nhiều thao tác vẫn truy cập dữ liệu mẫu trực tiếp.

Chỉ có ba thư mục chính frontend, backend, database. Hướng dẫn dự án nằm trong README. Kế hoạch theo tuần là tài liệu phối hợp riêng.

```text
frontend/
├── index.html                 # TV1: khung trang, vùng menu và màn hình
├── app.js                     # TV1: điều hướng, nạp route, dọn sự kiện khi đổi trang
├── config.js                  # TV1: cấu hình API công khai theo môi trường
├── prototype/                 # TV1: giao diện mẫu tham khảo, chưa nối backend
│   ├── index.html             # Đăng nhập mô phỏng
│   ├── assets/                # CSS, JavaScript và dữ liệu mẫu
│   ├── pages/                 # Màn hình theo bốn vai trò
│   └── docs/                  # API/use case tham khảo của bộ mẫu
├── shared/                    # TV1 trực tiếp sửa
│   ├── api.js                 # Gọi API, xử lý lỗi/phiên hết hạn và phản hồi tệp
│   ├── session.js             # Đọc người dùng hiện tại để hỗ trợ giao diện
│   ├── layout.js              # Menu và bố cục chung
│   ├── ui.js                  # Hộp thoại, thông báo, trạng thái tải
│   └── styles.css             # Kiểu chung của toàn ứng dụng
├── books/                     # TV1: tra cứu, chi tiết, form sách/bản sách, danh mục, kiểm kê
├── ebooks/                    # TV1: danh sách, upload/quản lý PDF, trình đọc và quyền đọc
├── readers/                   # TV2: danh sách độc giả, form hồ sơ, cấp/gia hạn/khóa thẻ
├── bookshelf/                 # TV2: tủ sách, yêu thích, dấu trang, lịch sử, tiếp tục đọc
├── loans/                     # TV3: yêu cầu, duyệt, bàn giao, trả, gia hạn, lịch sử/ quá hạn
├── fines/                     # TV3: khoản phạt, cách tính, xác nhận thu tiền
├── accounts/                  # TV4: đăng ký/đăng nhập, tài khoản, nhân viên, đổi mật khẩu
├── notifications/             # TV4: danh sách, chưa đọc, đánh dấu đã đọc
└── reports/                   # TV4: dashboard, bộ lọc, xuất báo cáo, form chính sách
```

Mỗi folder chức năng chứa routes.js, các file HTML màn hình, file JavaScript gọi API/xử lý thao tác và CSS riêng nếu cần. Không tạo lại menu, cơ chế đăng nhập hoặc API client riêng.

| Module | File/màn hình dự kiến | Công việc cụ thể |
|---|---|---|
| books | catalog.html, book-detail.html, book-manage.html, book-copies.html, inventory.html, books.js | Tìm/lọc, phân trang, thêm/sửa, vị trí và trạng thái bản sách, đối chiếu kiểm kê |
| ebooks | catalog.html, ebook-detail.html, manage.html, reader.html, reader.js, ebooks.js | Tìm eBook, tải/thay PDF, mở nội dung có quyền, chuyển trang/zoom, gọi API tiến độ sau khi API này thuộc bản nền |
| readers | reader-list.html, reader-form.html, library-card.html, profile.html, readers.js | CRUD hồ sơ, hiển thị hạn thẻ, xử lý form và lỗi dữ liệu |
| bookshelf | bookshelf.html, favorites.html, bookmarks.html, reading-history.html, bookshelf.js | Quản lý dữ liệu đọc của cá nhân, cung cấp hàm tiến độ cho trình đọc dùng ở tuần sau |
| loans | borrow-request.html, approval.html, handover.html, return.html, renewals.html, my-loans.html, overdue.html, loans.js | Thực hiện từng bước nghiệp vụ qua API; không tự quyết định tồn kho hoặc quyền mượn |
| fines | fine-list.html, fine-detail.html, payment.html, fines.js | Hiển thị khoản phạt backend tính; xác nhận thu theo quyền |
| accounts | login.html, register.html, change-password.html, account-list.html, employee-list.html, accounts.js | Gửi form tài khoản, dùng API client/phiên chung TV1 cung cấp |
| notifications | notification-list.html, notifications.js | Hiển thị đúng người nhận; cung cấp thành phần cho TV1 nối menu ở đợt tích hợp |
| reports | dashboard.html, report-detail.html, settings.html, reports.js | Lọc/xuất dữ liệu, kiểm tra trạng thái tải/trống/lỗi; không tự tính lại nghiệp vụ nguồn |

Cây thư mục và bảng trên mô tả cấu trúc đích; books/ebooks còn là khung cho tuần 2–3; shared đã có nền tuần 1, các module khác hiện giữ thư mục chờ triển khai. Routes riêng theo hợp đồng TV1 chốt. Tuần 1 thử giao diện độc lập với dữ liệu mẫu; từ tuần 2 dùng khung B1 đã bàn giao. Bỏ chế độ mẫu khỏi luồng sản phẩm khi nối API thật.

### Quy trình chuyển giao diện sang module

1. Đối chiếu màn hình với UC-TV01–UC-TV42 và BR01–BR25; ghi phần tái sử dụng, phần thiếu và phần sai nghiệp vụ.
2. Tách phần hiển thị/xử lý trong `prototype/assets/js/main.js` thành HTML fragment và JavaScript của module. Các trang trong pages chủ yếu là vỏ nạp JavaScript; chỉ chép HTML là chưa đủ.
3. TV1 đưa bố cục/CSS/menu vào shared. Thành viên thử độc lập theo hợp đồng B0 trong tuần 1, không chờ khung TV1 đang viết; không tạo menu/API client riêng cho sản phẩm.
4. Thay thao tác LocalStorage bằng API thật, ánh xạ dữ liệu demo sang ID tài khoản, đầu sách và bản sách trong hợp đồng chính thức. Không import mã từ prototype vào ứng dụng chính.
5. Bàn giao màn hình nguồn/đích, UC, API và ca kiểm tra; ghi rõ demo, đã nối API hay đã nghiệm thu. Kiểm tra cả tải, trống, lỗi, hết phiên và sai quyền.

Phải bổ sung xác thực, hồ sơ/thẻ, upload/đọc PDF thật và các màn hình còn thiếu. Luồng lập phiếu mẫu yêu cầu tối thiểu 7 ngày, trong khi đặc tả quy định tối đa 14 ngày; phải sửa khi triển khai. Backend quyết định quyền, tồn kho, hạn mức, gia hạn và phạt; không lấy kiểm tra mô phỏng làm quy tắc chính thức.

## 4. Cấu trúc backend và công việc

```text
backend/
├── public/index.php           # TV1: điểm vào API, chuyển request đến router
├── bootstrap.php              # TV1: nạp lớp và dependency
├── config.php                 # TV1: đọc biến môi trường, không lưu bí mật trong Git
├── routes.php                 # TV1: nạp route từng module
├── shared/                    # TV1: PDO, phiên/quyền, response, lỗi và nhật ký
├── books/                     # TV1: API sách/kho; truy vấn và cập nhật bản sách
├── ebooks/                    # TV1: tệp, quyền nội dung, phiên bản và mượn điện tử
├── readers/                   # TV2: hồ sơ/thẻ và kiểm tra hiệu lực thẻ
├── bookshelf/                 # TV2: dữ liệu đọc, tiến độ, dấu trang, lịch sử cá nhân
├── loans/                     # TV3: nghiệp vụ yêu cầu–bàn giao–trả–gia hạn
├── fines/                     # TV3: tính/xác nhận phạt, ghi nhận thanh toán chống trùng
├── accounts/                  # TV4: tài khoản/nhân viên, kiểm tra mật khẩu, dùng phiên chung
├── notifications/             # TV4: lưu/đọc thông báo, xử lý sự kiện và mail nếu cấu hình
├── reports/                   # TV4: truy vấn thống kê, xuất tệp và cấu hình nghiệp vụ
├── jobs/                      # TV1: lịch nhắc hạn/hết hạn, chỉ gọi service đã có từ trước
└── storage/ebooks/            # TV1: PDF riêng tư phát sinh khi chạy, không commit
```

Trong mỗi module, đặt routes.php và các lớp tương ứng cạnh nhau, ví dụ:

```text
ebooks/
├── routes.php                 # TV1: khai báo API, không chứa SQL
├── EbookController.php        # Nhận input, gọi service, trả JSON/tệp
├── EbookService.php           # Kiểm tra quyền/tệp/hạn, điều phối nghiệp vụ
└── EbookRepository.php        # Truy vấn bằng PDO, ánh xạ dữ liệu
```

Các module khác dùng Book, Reader, Bookshelf, Loan, Fine, Account, Notification, Report làm tiền tố lớp tương ứng. reports có thể có SettingsController/Service/Repository riêng để không trộn chỉnh chính sách với báo cáo.

- Controller không chứa SQL hoặc tính phạt.
- Service kiểm tra quyền/điều kiện, điều phối giao dịch.
- Repository dùng truy vấn có tham số và cùng PDO do bên gọi cung cấp; không tự commit giao dịch do LoanService điều phối.
- backend/shared/Auth.php do TV1 sửa; AccountService của TV4 gọi giao diện phiên/quyền đã chốt từ tuần trước.
- Không công khai storage hoặc toàn bộ repository. Máy chủ phục vụ frontend và ánh xạ /api đến backend/public; cấu hình do TV1 quản lý.

## 5. SQL, file chung và trách nhiệm TV1

```text
database/
├── schema.sql                 # TV1: bản nền bảng/khóa/chỉ mục
├── seed.sql                   # TV1: dữ liệu thử có liên kết hợp lệ
└── migrations/                # TV1: thay đổi có thứ tự, không sửa lịch sử đã áp dụng
README.md                      # TV1: tài liệu và hợp đồng chính thức
```

TV1 cũng trực tiếp sửa .gitignore, file thư viện/khóa phiên bản, cấu hình công cụ/máy chủ và kế hoạch. TV2–TV4 gửi đặc tả bảng, SQL đề xuất, nhu cầu thư viện và hướng dẫn qua mô tả PR/trao đổi nhóm; không tự sửa file chung trên nhánh riêng.

**Không chờ SQL giữa tuần:** yêu cầu dữ liệu của tuần N phải được gửi và đưa vào bản nền trước tuần N. TV1 chuẩn bị schema cho module tiếp theo trong giai đoạn chốt bản nền. Mã tuần hiện tại không được phụ thuộc migration do người khác đang chuẩn bị cùng tuần.

Những bảng cần có theo nhóm:

| Nhóm | Bảng dự kiến | Người thiết kế nghiệp vụ; TV1 tạo SQL |
|---|---|---|
| Nền tài khoản | tai_khoan, nhan_vien, cau_hinh, nhat_ky_thao_tac | TV4 |
| Hồ sơ | doc_gia, the_doc_gia | TV2 |
| Sách/kho | sach, ban_sach, tac_gia, sach_tac_gia, the_loai, nha_xuat_ban, vi_tri_luu_tru, kiem_ke, chi_tiet_kiem_ke | TV1 |
| Mượn giấy | yeu_cau_muon, chi_tiet_yeu_cau_muon, phieu_muon, chi_tiet_phieu_muon, yeu_cau_gia_han | TV3 |
| Phạt | phieu_phat, chi_tiet_phieu_phat, thanh_toan_phat | TV3 |
| Điện tử | sach_dien_tu, tep_sach_dien_tu, muon_sach_dien_tu | TV1 |
| Cá nhân đọc | tu_sach, tien_do_doc, danh_dau_trang, lich_su_doc | TV2 |
| Thông báo | thong_bao | TV4 |

Bản nền dùng cho một đợt phát triển phải có schema và dữ liệu demo cần thiết, dù API quản lý một số nhóm dữ liệu chưa được viết. Module chỉ làm việc với bảng thuộc phạm vi mình và dữ liệu nền; không gọi service đang được người khác phát triển trong cùng đợt.

## 6. Trách nhiệm bàn giao và cách các phần kết nối

### TV1 — Trưởng nhóm, nền tảng, sách và sách điện tử

- Frontend: khung trang/menu/router/API client; tra cứu sách, quản lý kho và danh mục; tải/quản lý PDF, trình đọc và kết nối tiến độ khi API đã sẵn sàng.
- Backend: khởi động, PDO, phiên/quyền, chuẩn lỗi; API sách/bản sách, kiểm kê, nội dung PDF, quyền và hạn mượn điện tử.
- File chung: trực tiếp sửa SQL, cấu hình, thư viện, jobs, shared, README và kế hoạch; nhận đề xuất từ các thành viên.
- Đầu ra cuối cùng: khung chạy ổn định; tìm/quản lý sách được; đọc PDF có kiểm tra quyền; các phần tích hợp được và có hướng dẫn cài/chạy.
- Không làm thay toàn bộ nghiệp vụ riêng của thành viên khác. PR của TV1 vẫn cần người liên quan kiểm tra.

### TV2 — Độc giả và trải nghiệm đọc cá nhân

- Frontend: hồ sơ, thẻ thư viện, tủ sách, yêu thích, dấu trang, lịch sử và thao tác tiếp tục đọc.
- Backend: kiểm tra hồ sơ/thẻ; dữ liệu cá nhân; lưu/lấy tiến độ theo tài khoản và phiên bản tệp.
- Cung cấp cho TV1 hợp đồng tiến độ/dấu trang để nối trình đọc; cung cấp cho TV3 nghiệp vụ kiểm tra thẻ.
- Đầu ra cuối cùng: độc giả/thẻ được quản lý đúng; dữ liệu đọc không lẫn tài khoản; tiếp tục đọc đúng vị trí/phiên bản. Lịch sử mượn dùng API của TV3 đã bàn giao.
- Gửi đề xuất bảng và kết quả kiểm thử cho TV1; không sửa schema, README hoặc reader.js của TV1.

### TV3 — Mượn, trả và tiền phạt

- Frontend: yêu cầu mượn, duyệt, bàn giao, trả từng bản, gia hạn, lịch sử, quá hạn, tiền phạt và thu tiền.
- Backend: điều kiện mượn, giao dịch phiếu–kho, tính phạt dùng chung, ghi nhận thanh toán chống trùng.
- Sử dụng API/hàm kho và thẻ đã có; sở hữu API lịch sử mượn/trả, truy vấn giao dịch và quyền xem lịch sử; cung cấp dữ liệu cho TV2 hiển thị, TV4 làm báo cáo/thông báo.
- Đầu ra cuối cùng: không tạo phiếu trước bàn giao, không cho mượn trùng bản, trả từng phần đúng, không ghi phạt hoặc thu tiền hai lần.
- Không sửa trực tiếp file kho/shared/SQL; thay đổi cần thiết gửi TV1 và chủ module liên quan.

### TV4 — Tài khoản, nhân viên, thông báo và báo cáo

- Frontend: tài khoản/nhân viên, đăng nhập/đăng ký/đổi mật khẩu; thông báo, dashboard, xuất báo cáo và cấu hình nghiệp vụ.
- Backend: xử lý tài khoản và mật khẩu; dùng cơ chế phiên/quyền của TV1; service thông báo và truy vấn báo cáo.
- Cung cấp service thông báo cho TV1 kết nối jobs; lấy dữ liệu từ nghiệp vụ đã bàn giao, không tự tính lại phạt.
- Đầu ra cuối cùng: tài khoản đúng quyền, thông báo đúng người, số liệu khớp nguồn, phân biệt khoản phải thu và đã thu.
- Không trực tiếp sửa menu/shared/Auth.php/jobs; gửi yêu cầu cho TV1. Thông số nghiệp vụ quản lý qua SettingsService không phải cấu hình máy chủ.

| Kết nối | Bên cung cấp → bên sử dụng | Điều phải chốt |
|---|---|---|
| Nền chung | TV1 → cả nhóm | Router, PDO, phiên/quyền, JSON lỗi, cấu hình và SQL |
| Bản sách và thẻ | TV1 + TV2 → TV3 | ID, trạng thái, điều kiện hợp lệ, cùng PDO trong giao dịch |
| Tệp điện tử | TV1 → TV2 | ebook_id, file_version, số trang, quyền truy cập |
| Tiến độ đọc | TV2 → TV1 | Cách lưu/lấy trang, dấu trang, xử lý thay phiên bản |
| Mượn/trả/phạt | TV3 → TV2/TV4 | Lịch sử cá nhân, số liệu, sự kiện đã hoàn tất |
| Thông báo | TV4 → TV1 | Service tạo/gửi, mã chống trùng và cách thử lại |

Hợp đồng cần có input/output, quyền, trạng thái, lỗi và ví dụ. Chỉ dùng hợp đồng đã thuộc bản nền được kiểm tra; thời điểm bàn giao từng hợp đồng nằm trong file kế hoạch. Không tự đổi trường hoặc chữ ký hàm đã bàn giao.

Đăng ký tài khoản có thể tạo trạng thái chờ hoàn thiện hồ sơ khi ReaderService chưa có trong nền. Khi service đã bàn giao, kết nối tạo/liên kết hồ sơ; mượn sách luôn yêu cầu hồ sơ và thẻ hợp lệ. Công cụ fixture/CLI chỉ dùng cho kiểm thử, không tạo endpoint bỏ qua đăng nhập trong sản phẩm.

## 7. Luồng nghiệp vụ phải giữ

- Sách giấy: yêu cầu → duyệt → bàn giao → tạo phiếu và cập nhật bản sách trong một giao dịch. Duyệt chưa tạo phiếu; trả theo từng bản, không đóng phiếu khi chưa xử lý hết.
- Không cho hai giao dịch cùng mượn một bản; trả/phạt/thu tiền cần chống xử lý lặp.
- Sách hỏng/mất không tự trở lại sẵn có. Không xóa lịch sử giao dịch khi ngừng sử dụng sách/tài khoản.
- eBook: tìm theo thông tin danh mục, mở PDF qua API có quyền; tiến độ gắn tài khoản + phiên bản tệp + trang bắt đầu từ 1.
- Mượn điện tử độc lập với tồn sách giấy. API kiểm tra hạn ở mỗi yêu cầu nội dung, không đợi job mới chặn. Không hứa thu hồi nội dung đã gửi đến trình duyệt.
- PDF là phạm vi đầu tiên; EPUB, OCR/tìm toàn văn toàn kho, thanh toán trực tuyến, hàng chờ đặt trước thuộc mở rộng nếu nhóm bổ sung lịch.
- Sách giấy: tối đa 3 cuốn/lần và 7 cuốn đang mượn; thời hạn tối đa 14 ngày từ lúc lập phiếu khi bàn giao (BR07–BR10). Kiểm tra lại hạn mức và khả dụng khi bàn giao, kể cả các yêu cầu đồng thời.
- Gia hạn: tối đa 2 lần, mỗi lần thêm không quá 7 ngày; chỉ xét khi quá hạn không quá 1 ngày và có thủ thư duyệt (BR13–BR15). Đơn vị đếm gia hạn và cách tính ngày phải chốt theo đặc tả.
- Phạt quá hạn: 2.000 VNĐ/cuốn/ngày, tính theo từng cuốn còn quá hạn. Mất/hỏng do độc giả: giá trị sách cộng phụ phí chưa chốt; hệ thống tính, thủ thư xác nhận. Thanh toán toàn bộ tại quầy, không trả một phần (BR16–BR19).
- eBook công khai đọc ngay; loại giới hạn cần thủ thư duyệt. Tối đa 3 cuốn/lần, thời hạn 14 ngày; nguồn không quy định tổng eBook đang mượn, không áp hạn mức 7 của sách giấy (BR20–BR22).
- Tài khoản có một trong bốn vai trò; mỗi MSSV một tài khoản; quản lý được phân quyền và khóa tài khoản (BR01–BR03). Cách dùng email/MSSV khi đăng nhập cần chốt với luồng mật khẩu trong UML.
- Bản sách có mã duy nhất và vị trí theo mã thể loại; chỉ bản khả dụng còn lưu hành tính vào tồn có thể mượn. Không xóa/ngừng lưu hành sách đang mượn (BR04–BR06, BR23–BR24).
- Thông báo khi mượn được duyệt, gia hạn và sắp đến hạn trả; ngưỡng nhắc hạn, kênh gửi và chống trùng phải chốt trước triển khai (BR25).
- Không tiếp tục coi giới hạn 5 sách, thẻ 6 tháng, loại X/Y và tuổi 18–55 là quy định đã được nguồn mới xác nhận. Giữ module hồ sơ/thẻ nhưng chốt chính sách riêng. Chi tiết “100 tác giả” không tự coi là trần dữ liệu.

## 8. Nguyên tắc quản lý phiên bản

main chứa bản tích hợp; mỗi thành viên làm trên nhánh cố định tv1–tv4 từ cùng bản nền đã kiểm tra. TV1 điều phối PR và merge. Đầu tuần chỉ cập nhật main sau khi TV1 xác nhận bản nền; không push trực tiếp vào main hoặc force-push.

Bản nền phải ghi commit/tag, migration đã áp dụng, hợp đồng có sẵn, giới hạn và cách chạy. Chỉ phần được kiểm tra, tích hợp thành công mới được đưa vào bản nền. “Đã push” hoặc “chạy với dữ liệu giả” không có nghĩa đủ làm đầu vào cho phần khác.

Quy trình cập nhật nhánh, thời gian kiểm tra/gộp và các mốc B0–B6 nằm trong file kế hoạch.

Với mỗi đoạn code cần có phần note để người xem hiểu được code đó dùng cho việc gì.

## 9. Quy ước mã và Clean Code

- UTF-8, LF; PHP/SQL thụt 4 dấu cách, HTML/CSS/JS 2; không đổi định dạng cả dự án trong PR chức năng.
- Lớp PHP/file lớp PascalCase; hàm/biến camelCase; file giao diện nhiều từ dùng kebab-case; bảng/trường JSON nghiệp vụ giữ snake_case như sach_id; hằng số UPPER_SNAKE_CASE.
- Tên code tiếng Anh rõ nghĩa, chú thích/giao diện tiếng Việt. Không trộn nhiều tên cho cùng trường API.
- JavaScript dùng const/let, ES modules; CSS có tiền tố module. Bộ gọi API dùng chung; dữ liệu văn bản không tin cậy hiển thị bằng textContent.
- Service không tạo dependency ẩn hoặc PDO riêng giữa giao dịch; Controller không chứa SQL; Repository không tự quyết định quyền mượn.
- Một trách nhiệm rõ cho mỗi lớp/hàm; tách khi giúp hiểu và kiểm tra tốt hơn, không tạo nhiều lớp chỉ để đủ hình thức.
- Không lặp quy tắc thẻ, phạt, quyền đọc. Không ép mượn giấy và điện tử dùng cùng một quy trình chỉ vì tên giống nhau.
- Cấu hình nghiệp vụ không rải số cố định. Tiền dùng DECIMAL/đơn vị nguyên phù hợp, không cộng bằng float thiếu kiểm soát.
- PDO dùng tham số cho giá trị, danh sách cho phép cho tên cột sắp xếp. Backend kiểm tra quyền và chủ sở hữu; mật khẩu băm, không lưu plaintext.
- Lỗi có mã/thông báo rõ và HTTP status đúng; không trả stack trace, đường dẫn tệp hoặc bí mật cho người dùng. Phản hồi PDF là nội dung tệp, không bọc JSON.
- Chú thích giải thích lý do hoặc hợp đồng quan trọng; bỏ code chết, debug và bản sao _old/_new.
- PR gọn, có cách thử lại, không kèm thay đổi file chung của TV2–TV4. Commit ví dụ feat(ebooks): thêm đọc PDF có quyền.

Mẫu ghi chú đầu file riêng:

```javascript
/* Phụ trách: TV2 — tiến độ đọc.
 * Bản nền yêu cầu: base-w3, hợp đồng eBook đã chốt.
 * Giữ thống nhất: ebook_id, file_version, page.
 * Không dùng API của nhánh đang phát triển trong cùng tuần.
 * Cần sửa shared/schema: gửi yêu cầu cho TV1 cho mốc nền kế tiếp.
 */
```

## 10. Bàn giao TV1 — trước tuần 1 và tuần 1 (23/10/2026)

### Phần đã hoàn thành

- Giữ nguyên prototype tham khảo; tách CSS, shell, sidebar, topbar, hero và panel UniLib sang frontend/shared. Không import JavaScript hay dữ liệu LocalStorage của prototype vào ứng dụng chính.
- Trang đầu vào nạp module qua hash router; có trạng thái tải/lỗi/không tìm thấy, hủy request và cleanup khi đổi trang. Menu hỗ trợ máy tính, tablet và điện thoại.
- API client cùng nguồn xử lý JSON, 204, Blob, lỗi mạng/HTTP, CSRF và hết phiên. shared/ui cung cấp trạng thái, toast và dialog.
- PHP có router tham số, 404/405, response thống nhất, PDO dùng chung, lỗi nội bộ không lộ chi tiết, session cookie HttpOnly/SameSite và CSRF cho request thay đổi dữ liệu.
- Auth đọc lại trạng thái/vai trò từ DB mỗi request có xác thực, xoay session ID khi đăng nhập/đăng xuất. AccountService tuần 2 của TV4 gọi Auth sau khi kiểm tra mật khẩu; hiện không có API đăng nhập giả.
- Schema/seed gồm 13 bảng nền cho tài khoản, nhân viên, độc giả/thẻ, danh mục, sách/bản sách, cấu hình và nhật ký. Chưa tạo bảng mượn/trả/eBook của tuần sau. Bộ tính phạt tuần 2 nhận tham số, chưa cần bảng phiếu phạt.

Đây là phần code TV1 tuần 1 đã kiểm tra, **chưa phải B1 toàn nhóm đã nghiệm thu hoặc đã merge**. Chưa triển khai CRUD books tuần 2, PDF tuần 3, các module riêng TV2–TV4. Thư mục books/ebooks giữ khung hiện có, không cung cấp endpoint giả trả thành công.

### Cài mới và chạy

Đã kiểm tra bằng PHP 8.2.12 (pdo_mysql), MariaDB 10.4.32 của XAMPP, Node 24 cho kiểm tra HTTP. Chưa kiểm tra riêng trên Oracle MySQL. Các lệnh dưới chạy ở thư mục gốc repository bằng PowerShell; đổi đường dẫn chương trình nếu máy không dùng XAMPP.

1. Bật MySQL trong XAMPP. Tạo database mới, không dùng database có dữ liệu sản phẩm. Chạy từng lệnh, chỉ chạy tiếp nếu lệnh trước thành công:

~~~powershell
& C:/xampp/mysql/bin/mysql.exe -u root -e "CREATE DATABASE ittv_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
& C:/xampp/mysql/bin/mysql.exe -u root ittv_library -e "source database/schema.sql"
& C:/xampp/mysql/bin/mysql.exe -u root ittv_library -e "source database/seed.sql"
~~~

Nếu root có mật khẩu, thêm -p để nhập tương tác. schema.sql/seed.sql chỉ nhập một lần trên DB trống; không chạy lại hoặc xóa DB đang dùng. Các lần nâng cấp sau dùng migration do TV1 chốt.

2. Đặt biến môi trường trong cửa sổ chạy PHP rồi khởi động:

~~~powershell
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3306'
$env:DB_NAME = 'ittv_library'
$env:DB_USER = 'root'
# Đặt DB_PASSWORD theo cấu hình máy; mặc định rỗng cho XAMPP cục bộ.
$env:SESSION_SECURE = '0'
& C:/xampp/php/php.exe -S 127.0.0.1:8080 -t frontend backend/dev-router.php
~~~

3. Mở [UniLib cục bộ](http://127.0.0.1:8080). Trang chủ hiển thị kết quả kết nối API và DB thật. Live Server chỉ phù hợp xem prototype; không thực thi PHP. File .env.example là danh sách biến, PHP không tự nạp .env.

Document root phải là frontend, không phải gốc repository. dev-router.php chỉ dùng phát triển cục bộ; khi triển khai dùng web server ánh xạ /api về backend/public/index.php, HTTPS với SESSION_SECURE=1 và loại prototype khỏi nội dung được phục vụ.

Tài khoản seed có các vai trò student/librarian/staff/admin, email miền example.test và hash cố ý vô hiệu để không tạo mật khẩu mặc định. Chúng dùng làm dữ liệu nền cho service; TV4 tạo hash bằng password_hash khi thử xác thực. Tài khoản demo 123456 chỉ thuộc prototype, không đăng nhập được ứng dụng chính.

### Hợp đồng nền đã thực thi

| API | Kết quả |
|---|---|
| GET /api/health | 200: data.status = ok |
| GET /api/health/db | 200 nếu kết nối và bảng tai_khoan tồn tại; 503 DATABASE_NOT_READY nếu chưa sẵn sàng |
| GET /api/session | data.user (null nếu khách), data.csrf_token; không trả hash mật khẩu |

JSON thành công có data và error:null; danh sách có thể thêm meta. JSON lỗi có data:null, error gồm code/message/details. Response::json(data, status, meta) hỗ trợ 204 không body. API chưa tồn tại trả 404; sai method trả 405 và Allow. Request thay đổi dữ liệu cần cookie phiên và X-CSRF-Token lấy từ /api/session. Token được đổi sau Auth::login/logout, vì vậy TV4 gọi refreshSession sau đăng nhập/đăng xuất. Không tự retry POST khi lỗi CSRF.

Backend module xuất callable từ routes.php: function (Router $router, array $config): void. Router::add(method, '/api/module/:id', handler) truyền mảng params vào handler. Controller tự đọc/kiểm tra input; service gọi Auth::requireRoles(PDO, roles), không tin vai trò từ trình duyệt. PDO lấy bằng Database::connection($config['db']); repository dùng cùng PDO và không tự commit. Nhóm vai trò thống nhất student, librarian, staff, admin theo UniLib.

Frontend module xuất routes, mỗi route có path, title (nếu hiện menu), icon, roles (tùy chọn) và mount(context). Context gồm container, params, signal. Mount có thể async và trả hàm cleanup; dùng signal cho fetch/listener, kiểm tra signal.aborted trước khi ghi DOM sau await. shared/home.js là ví dụ chạy thật. TV1 thêm import và đăng ký route của module khi tích hợp; thành viên không sửa app.js hoặc shared. Đường dẫn hash chưa đăng ký trả trang không tìm thấy.

Gọi request('/path', {method, body, signal}) từ shared/api.js; body là object JSON hoặc FormData. responseType:'blob' cho nội dung tệp. currentUser/canAccess chỉ phục vụ hiển thị; backend kiểm tra quyền thật. Khi 401, client phát session-expired và xóa trạng thái người dùng.

### Dữ liệu và quyết định nền

- ID là BIGINT UNSIGNED; khóa ngoại giữ lịch sử, không cascade xóa. Email/MSSV, mã đầu sách/bản sách/thẻ là duy nhất; MSSV cho nhân viên có thể null.
- Bốn trạng thái bản sách: available, borrowed, damaged, lost. Tồn khả dụng lọc available và dang_luu_hanh=1; API tuần 2 còn phải kiểm tra đầu sách đang lưu hành và cấm xóa/ngừng bản đang mượn. Schema không thay thế nghiệp vụ transaction.
- Vị trí theo mã thể loại: MA_THE_LOAI-KE (ví dụ CNTT-A01). Khóa ngoại kép ngăn đặt bản sách vào vị trí khác thể loại. Tuần 2 không đổi thể loại đầu sách đã có bản; muốn chuyển phải có thao tác đồng bộ riêng, không tắt foreign key.
- Quyết định kỹ thuật TV1 cho tuần 2: staff và admin đều được tạo/quản lý sách theo UC-TV04/29; các vai trò khác chỉ tra cứu khi API được triển khai. Quyền này chưa được coi là endpoint tuần 1.
- Seed lưu các giới hạn đã xác nhận: 3/lần, 7 tổng, 14 ngày, gia hạn 2 lần × tối đa 7 ngày, quá hạn tối đa 1 ngày để xét, 2.000 VNĐ/cuốn/ngày, eBook 3/lần và 14 ngày.
- Phụ phí, nguồn giá bồi thường, cách làm tròn ngày và thời hạn thẻ để NULL chờ chốt. Ngày trên thẻ seed là fixture, không phải chính sách. Email/MSSV đều có trường, nhưng cách đăng nhập do TV4 đề xuất trước nghiệm thu B1. Múi giờ và cách tính ngày phải được chốt trước bộ tính phạt, không suy ra từ timezone máy.

### Kiểm tra và kết quả

Ngày 23/10/2026 đã kiểm tra:

- Cú pháp PHP và JavaScript hợp lệ.
- Nhập schema/seed vào MariaDB thử riêng; API báo kết nối DB thành công.
- 19 kiểm tra nền: route tham số, 404/405, CSRF, khách/sai quyền, xoay session, khóa tài khoản làm mất phiên, trùng email/MSSV/mã bản, vị trí sai thể loại, hạn thẻ, trạng thái bản và tồn khả dụng.
- HTTP: health, DB, phiên khách, CSRF sai/đúng, method, route thiếu và không lộ backend/SQL/.env qua web. API client đã kiểm tra 204, Blob, 401, lỗi HTTP/mạng, hủy request và tự lấy CSRF.
- Trình duyệt Edge: trang chủ, kiểm tra lại kết nối, route không tồn tại, quay về, lỗi DB, menu mobile; desktop 1440px, tablet 800px, mobile 390px. Không có lỗi JavaScript và không tràn ngang.

Chạy lại kiểm tra PHP trên DB thử mới có tên kết thúc _test, đã nhập schema/seed:

~~~powershell
$env:DB_NAME = 'ittv_week1_test'
& C:/xampp/php/php.exe backend/tests/week1.php
$env:TEST_URL = 'http://127.0.0.1:8080'
node backend/tests/http.mjs
node backend/tests/api-client.mjs
~~~

Máy chủ thử phải dùng cùng DB thử; đặt DB_PORT tương ứng trước khi chạy cả PHP server và test. week1.php rollback toàn bộ thay đổi kiểm thử. browser.cjs cần Playwright trong môi trường kiểm thử và trình duyệt đã cài; có thể đặt BROWSER_PATH. Ảnh kiểm tra lưu dưới backend/storage và không commit. HTTP test không sửa dữ liệu.

B1 toàn nhóm chỉ được phát hành sau khi nhận màn hình/thiết kế TV2–TV4, thống nhất chính sách còn mở, kiểm tra chéo PR và tích hợp. Commit/push trên tv1 là bàn giao code để kiểm tra; chưa tạo tag B1 hoặc tích hợp vào main.
