-- DDL rerunnable after interruption. No existing rows are removed.
CREATE TABLE IF NOT EXISTS ebook (
 ebook_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sach_id BIGINT UNSIGNED NOT NULL,
 access_mode ENUM('public','restricted') NOT NULL DEFAULT 'restricted',
 active BOOLEAN NOT NULL DEFAULT TRUE,
 FOREIGN KEY(sach_id) REFERENCES sach(sach_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ebook_file (
 ebook_file_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ebook_id BIGINT UNSIGNED NOT NULL,
 file_version INT UNSIGNED NOT NULL,
 storage_key VARCHAR(191) NOT NULL UNIQUE,
 mime_type VARCHAR(80) NOT NULL DEFAULT 'application/pdf',
 size_bytes BIGINT UNSIGNED NOT NULL,
 sha256 CHAR(64) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(ebook_id,file_version),
 FOREIGN KEY(ebook_id) REFERENCES ebook(ebook_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS borrow_request (
 request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 doc_gia_id BIGINT UNSIGNED NOT NULL,
 status ENUM('pending','approved','rejected','handed_over','cancelled') NOT NULL DEFAULT 'pending',
 requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 reviewed_by BIGINT UNSIGNED NULL,
 reviewed_at DATETIME NULL,
 FOREIGN KEY(doc_gia_id) REFERENCES doc_gia(doc_gia_id),
 FOREIGN KEY(reviewed_by) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS borrow_request_item (
 request_id BIGINT UNSIGNED NOT NULL,
 ban_sach_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(request_id,ban_sach_id),
 FOREIGN KEY(request_id) REFERENCES borrow_request(request_id),
 FOREIGN KEY(ban_sach_id) REFERENCES ban_sach(ban_sach_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS loan (
 loan_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_id BIGINT UNSIGNED NOT NULL UNIQUE,
 handed_over_by BIGINT UNSIGNED NOT NULL,
 handed_over_at DATETIME NOT NULL,
 closed_at DATETIME NULL,
 FOREIGN KEY(request_id) REFERENCES borrow_request(request_id),
 FOREIGN KEY(handed_over_by) REFERENCES tai_khoan(tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS loan_item (
 loan_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 loan_id BIGINT UNSIGNED NOT NULL,
 ban_sach_id BIGINT UNSIGNED NOT NULL,
 due_date DATE NOT NULL,
 returned_at DATETIME NULL,
 renewal_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('borrowed','returned','lost','damaged') NOT NULL DEFAULT 'borrowed',
 active_copy_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status='borrowed' THEN ban_sach_id ELSE NULL END) STORED,
 UNIQUE(active_copy_id),
 UNIQUE(loan_id,ban_sach_id),
 FOREIGN KEY(loan_id) REFERENCES loan(loan_id),
 FOREIGN KEY(ban_sach_id) REFERENCES ban_sach(ban_sach_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
