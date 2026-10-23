-- TV1: nền tuần 1 cho tài khoản, độc giả/thẻ, sách/kho và bộ tính phạt tuần 2.
-- Chạy trên database trống đã chọn. Không DROP hoặc sửa dữ liệu hiện có.
SET NAMES utf8mb4;
CREATE TABLE tai_khoan (
 tai_khoan_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(191) NOT NULL UNIQUE,
 mssv VARCHAR(32) NULL UNIQUE,
 mat_khau_hash VARCHAR(255) NOT NULL,
 vai_tro ENUM('student','librarian','staff','admin') NOT NULL,
 trang_thai ENUM('active','locked') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE nhan_vien (
 nhan_vien_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tai_khoan_id BIGINT UNSIGNED NOT NULL UNIQUE,
 ma_nhan_vien VARCHAR(32) NOT NULL UNIQUE,
 ho_ten VARCHAR(150) NOT NULL,
 FOREIGN KEY (tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE doc_gia (
 doc_gia_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tai_khoan_id BIGINT UNSIGNED NOT NULL UNIQUE,
 ho_ten VARCHAR(150) NOT NULL,
 ngay_sinh DATE NULL,
 dien_thoai VARCHAR(20) NULL,
 dia_chi VARCHAR(255) NULL,
 FOREIGN KEY (tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE the_doc_gia (
 the_doc_gia_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 doc_gia_id BIGINT UNSIGNED NOT NULL UNIQUE,
 ma_the VARCHAR(32) NOT NULL UNIQUE,
 ngay_cap DATE NOT NULL,
 ngay_het_han DATE NOT NULL,
 trang_thai ENUM('active','locked') NOT NULL DEFAULT 'active',
 FOREIGN KEY (doc_gia_id) REFERENCES doc_gia(doc_gia_id),
 CHECK (ngay_het_han >= ngay_cap)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE cau_hinh (
 khoa VARCHAR(80) PRIMARY KEY,
 gia_tri VARCHAR(255) NULL,
 mo_ta VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE nhat_ky_thao_tac (
 nhat_ky_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tai_khoan_id BIGINT UNSIGNED NULL,
 hanh_dong VARCHAR(100) NOT NULL,
 doi_tuong VARCHAR(100) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE the_loai (
 the_loai_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ma_the_loai VARCHAR(20) NOT NULL UNIQUE,
 ten_the_loai VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE tac_gia (
 tac_gia_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ho_ten VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE nha_xuat_ban (
 nha_xuat_ban_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ten_nha_xuat_ban VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE vi_tri_luu_tru (
 vi_tri_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 the_loai_id BIGINT UNSIGNED NOT NULL,
 ma_vi_tri VARCHAR(40) NOT NULL UNIQUE,
 mo_ta VARCHAR(255) NULL,
 UNIQUE (vi_tri_id, the_loai_id),
 FOREIGN KEY (the_loai_id) REFERENCES the_loai(the_loai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE sach (
 sach_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ma_sach VARCHAR(32) NOT NULL UNIQUE,
 isbn VARCHAR(20) NULL UNIQUE,
 ten_sach VARCHAR(255) NOT NULL,
 the_loai_id BIGINT UNSIGNED NOT NULL,
 nha_xuat_ban_id BIGINT UNSIGNED NULL,
 nam_xuat_ban SMALLINT UNSIGNED NULL,
 gia_bia DECIMAL(12,0) NULL,
 mo_ta TEXT NULL,
 dang_luu_hanh BOOLEAN NOT NULL DEFAULT TRUE,
 UNIQUE (sach_id, the_loai_id),
 FOREIGN KEY (the_loai_id) REFERENCES the_loai(the_loai_id),
 FOREIGN KEY (nha_xuat_ban_id) REFERENCES nha_xuat_ban(nha_xuat_ban_id),
 CHECK (gia_bia IS NULL OR gia_bia >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE sach_tac_gia (
 sach_id BIGINT UNSIGNED NOT NULL,
 tac_gia_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY (sach_id, tac_gia_id),
 FOREIGN KEY (sach_id) REFERENCES sach(sach_id),
 FOREIGN KEY (tac_gia_id) REFERENCES tac_gia(tac_gia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE ban_sach (
 ban_sach_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ma_ban_sach VARCHAR(40) NOT NULL UNIQUE,
 sach_id BIGINT UNSIGNED NOT NULL,
 the_loai_id BIGINT UNSIGNED NOT NULL,
 vi_tri_id BIGINT UNSIGNED NOT NULL,
 tinh_trang ENUM('available','borrowed','damaged','lost') NOT NULL DEFAULT 'available',
 dang_luu_hanh BOOLEAN NOT NULL DEFAULT TRUE,
 FOREIGN KEY (sach_id, the_loai_id) REFERENCES sach(sach_id, the_loai_id),
 FOREIGN KEY (vi_tri_id, the_loai_id) REFERENCES vi_tri_luu_tru(vi_tri_id, the_loai_id),
 INDEX idx_kho (sach_id, tinh_trang, dang_luu_hanh),
 CHECK (tinh_trang <> 'borrowed' OR dang_luu_hanh = TRUE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
