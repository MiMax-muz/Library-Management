CREATE TABLE IF NOT EXISTS thong_bao (
 thong_bao_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tai_khoan_id BIGINT UNSIGNED NOT NULL, loai VARCHAR(32) NOT NULL,
 ma_nguon VARCHAR(80) NOT NULL, ngay_han DATE NULL,
 ma_su_kien CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 tieu_de VARCHAR(180) NOT NULL, noi_dung TEXT NOT NULL, tham_chieu VARCHAR(120) NULL,
 created_at DATETIME NOT NULL, read_at DATETIME NULL,
 FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id),
 UNIQUE KEY(ma_su_kien), INDEX(tai_khoan_id,created_at,thong_bao_id), INDEX(tai_khoan_id,read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS renewal_request (
 renewal_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 loan_item_id BIGINT UNSIGNED NOT NULL, requested_at DATETIME NOT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 reviewed_by BIGINT UNSIGNED NULL, reviewed_at DATETIME NULL,
 pending_item BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status='pending' THEN loan_item_id ELSE NULL END) STORED,
 UNIQUE(pending_item), FOREIGN KEY(loan_item_id) REFERENCES loan_item(loan_item_id),
 FOREIGN KEY(reviewed_by) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS fine_record (
 fine_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, loan_item_id BIGINT UNSIGNED NOT NULL UNIQUE,
 violation VARCHAR(20) NOT NULL, calculation_json LONGTEXT NOT NULL,
 total BIGINT UNSIGNED NOT NULL, evidence VARCHAR(1000) NULL,
 confirmed_by BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 FOREIGN KEY(loan_item_id) REFERENCES loan_item(loan_item_id), FOREIGN KEY(confirmed_by) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ebook_access (
 access_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ebook_id BIGINT UNSIGNED NOT NULL,
 tai_khoan_id BIGINT UNSIGNED NOT NULL, granted_at DATETIME NOT NULL, expires_at DATETIME NOT NULL,
 revoked_at DATETIME NULL, UNIQUE(ebook_id,tai_khoan_id),
 FOREIGN KEY(ebook_id) REFERENCES ebook(ebook_id), FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ebook_request (
 request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ebook_id BIGINT UNSIGNED NOT NULL,
 tai_khoan_id BIGINT UNSIGNED NOT NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 requested_at DATETIME NOT NULL, reviewed_at DATETIME NULL, reviewed_by BIGINT UNSIGNED NULL,
 pending_ebook BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status='pending' THEN ebook_id ELSE NULL END) STORED,
 UNIQUE(tai_khoan_id,pending_ebook), FOREIGN KEY(ebook_id) REFERENCES ebook(ebook_id),
 FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id), FOREIGN KEY(reviewed_by) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS bookshelf_entry (
 tai_khoan_id BIGINT UNSIGNED NOT NULL, ebook_id BIGINT UNSIGNED NOT NULL,
 favorite BOOLEAN NOT NULL DEFAULT FALSE, updated_at DATETIME NOT NULL,
 PRIMARY KEY(tai_khoan_id,ebook_id), FOREIGN KEY(ebook_id) REFERENCES ebook(ebook_id), FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS reading_progress (
 tai_khoan_id BIGINT UNSIGNED NOT NULL, ebook_file_id BIGINT UNSIGNED NOT NULL,
 page_number INT UNSIGNED NOT NULL, percent DECIMAL(5,2) NOT NULL, updated_at DATETIME NOT NULL,
 PRIMARY KEY(tai_khoan_id,ebook_file_id), FOREIGN KEY(ebook_file_id) REFERENCES ebook_file(ebook_file_id), FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS reading_bookmark (
 bookmark_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tai_khoan_id BIGINT UNSIGNED NOT NULL,
 ebook_file_id BIGINT UNSIGNED NOT NULL, page_number INT UNSIGNED NOT NULL, note VARCHAR(500) NOT NULL,
 UNIQUE(tai_khoan_id,ebook_file_id,page_number), FOREIGN KEY(ebook_file_id) REFERENCES ebook_file(ebook_file_id), FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS reading_history (
 history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tai_khoan_id BIGINT UNSIGNED NOT NULL,
 ebook_file_id BIGINT UNSIGNED NOT NULL, page_number INT UNSIGNED NOT NULL, percent DECIMAL(5,2) NOT NULL,
 created_at DATETIME NOT NULL, INDEX(tai_khoan_id,created_at),
 FOREIGN KEY(ebook_file_id) REFERENCES ebook_file(ebook_file_id), FOREIGN KEY(tai_khoan_id) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS inventory_check (
 inventory_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ban_sach_id BIGINT UNSIGNED NOT NULL,
 observed_status VARCHAR(20) NOT NULL, note VARCHAR(1000) NOT NULL,
 checked_by BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 FOREIGN KEY(ban_sach_id) REFERENCES ban_sach(ban_sach_id), FOREIGN KEY(checked_by) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
