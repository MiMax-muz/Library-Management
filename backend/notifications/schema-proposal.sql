-- TV4 đề xuất để TV1 duyệt và đưa vào migration của bản nền.
-- KHÔNG được tự động chạy từ module hoặc thay thế database/schema.sql.
-- created_at/read_at lưu UTC; service trả ISO 8601 có hậu tố Z.
CREATE TABLE thong_bao (
    thong_bao_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tai_khoan_id BIGINT UNSIGNED NOT NULL,
    loai VARCHAR(32) NOT NULL,
    ma_nguon VARCHAR(80) NOT NULL,
    ngay_han DATE NULL,
    ma_su_kien CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    tieu_de VARCHAR(180) NOT NULL,
    noi_dung TEXT NOT NULL,
    tham_chieu VARCHAR(120) NULL,
    created_at DATETIME NOT NULL,
    read_at DATETIME NULL,
    CONSTRAINT fk_thong_bao_tai_khoan FOREIGN KEY (tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id),
    UNIQUE KEY uq_thong_bao_su_kien (ma_su_kien),
    KEY idx_thong_bao_nguoi_nhan_ngay (tai_khoan_id, created_at, thong_bao_id),
    KEY idx_thong_bao_nguoi_nhan_doc (tai_khoan_id, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
