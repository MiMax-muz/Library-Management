-- TV1: fixture phát triển, chỉ nhập một lần trên schema trống.
-- Tài khoản có vai trò thật để thử service; hash vô hiệu không dùng để đăng nhập. TV4 tự tạo mật khẩu bằng password_hash khi thử module.
-- Thời hạn thẻ là fixture, không xác lập chính sách cấp thẻ mặc định.
SET NAMES utf8mb4;
START TRANSACTION;
INSERT INTO tai_khoan (tai_khoan_id,email,mssv,mat_khau_hash,vai_tro,trang_thai) VALUES
(1,'student@example.test','SV001','!fixture-disabled','student','active'),
(2,'librarian@example.test',NULL,'!fixture-disabled','librarian','active'),
(3,'staff@example.test',NULL,'!fixture-disabled','staff','active'),
(4,'admin@example.test',NULL,'!fixture-disabled','admin','active');
INSERT INTO nhan_vien (tai_khoan_id,ma_nhan_vien,ho_ten) VALUES (2,'NV001','Thủ thư mẫu'),(3,'NV002','Nhân viên mẫu'),(4,'NV003','Quản lý mẫu');
INSERT INTO doc_gia (doc_gia_id,tai_khoan_id,ho_ten) VALUES (1,1,'Sinh viên mẫu');
INSERT INTO the_doc_gia (doc_gia_id,ma_the,ngay_cap,ngay_het_han) VALUES (1,'THE001','2026-10-01','2027-10-01');
INSERT INTO cau_hinh (khoa,gia_tri,mo_ta) VALUES
('borrow_per_request','3','Số cuốn giấy tối đa/lần'),
('borrow_total','7','Tổng cuốn giấy đang mượn tối đa'),
('borrow_days','14','Hạn mượn giấy tối đa'),
('renewal_limit','2','Số lần gia hạn tối đa'),
('renewal_days','7','Số ngày thêm tối đa/lần'),
('renewal_overdue_days','1','Quá hạn tối đa được xét gia hạn'),
('overdue_daily_vnd','2000','Phạt VNĐ/cuốn/ngày'),
('ebook_per_request','3','eBook tối đa/lần'),
('ebook_days','14','Thời hạn quyền eBook'),
('damage_surcharge',NULL,'Chờ chốt phụ phí mất/hỏng'),
('compensation_value_source',NULL,'Chờ chốt nguồn giá trị bồi thường'),
('overdue_day_method',NULL,'Chờ chốt cách tính/làm tròn ngày'),
('card_duration',NULL,'Chờ chốt thời hạn thẻ; không mặc định 6 tháng');
INSERT INTO the_loai (the_loai_id,ma_the_loai,ten_the_loai) VALUES (1,'CNTT','Công nghệ thông tin'),(2,'VH','Văn học');
INSERT INTO tac_gia (tac_gia_id,ho_ten) VALUES (1,'Nhóm tác giả mẫu'),(2,'Tác giả văn học mẫu');
INSERT INTO nha_xuat_ban (nha_xuat_ban_id,ten_nha_xuat_ban) VALUES (1,'Nhà xuất bản mẫu');
INSERT INTO vi_tri_luu_tru (vi_tri_id,the_loai_id,ma_vi_tri,mo_ta) VALUES (1,1,'CNTT-A01','Kệ A01'),(2,2,'VH-B01','Kệ B01');
INSERT INTO sach (sach_id,ma_sach,ten_sach,the_loai_id,nha_xuat_ban_id,gia_bia) VALUES (1,'S001','Nhập môn lập trình',1,1,100000),(2,'S002','Tuyển tập văn học',2,1,80000);
INSERT INTO sach_tac_gia VALUES (1,1),(2,2);
INSERT INTO ban_sach (ma_ban_sach,sach_id,the_loai_id,vi_tri_id,tinh_trang) VALUES ('S001-01',1,1,1,'available'),('S001-02',1,1,1,'damaged'),('S002-01',2,2,2,'available'),('S002-02',2,2,2,'lost');
COMMIT;
