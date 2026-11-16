CREATE TABLE IF NOT EXISTS fine_payment (
 payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fine_id BIGINT UNSIGNED NOT NULL,
 amount BIGINT UNSIGNED NOT NULL,
 received_by BIGINT UNSIGNED NOT NULL,
 received_at DATETIME NOT NULL,
 UNIQUE KEY uq_fine_payment_fine (fine_id),
 CONSTRAINT fk_fine_payment_fine FOREIGN KEY (fine_id) REFERENCES fine_record(fine_id),
 CONSTRAINT fk_fine_payment_account FOREIGN KEY (received_by) REFERENCES tai_khoan(tai_khoan_id),
 KEY idx_fine_payment_received (received_at, payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
