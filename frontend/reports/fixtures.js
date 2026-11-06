// Dữ liệu B1 để duyệt giao diện tuần 3. Không đọc hoặc ghi database.
export const demoBooks = [
  { ma_sach: 'S001', ten_sach: 'Nhập môn lập trình', the_loai: 'Công nghệ thông tin', dang_luu_hanh: true, copies: [{ tinh_trang: 'available', dang_luu_hanh: true }, { tinh_trang: 'damaged', dang_luu_hanh: true }] },
  { ma_sach: 'S002', ten_sach: 'Tuyển tập văn học', the_loai: 'Văn học', dang_luu_hanh: true, copies: [{ tinh_trang: 'available', dang_luu_hanh: true }, { tinh_trang: 'lost', dang_luu_hanh: true }] },
];

export const demoReaders = [
  { ma_the: 'THE001', ho_ten: 'Sinh viên mẫu', email: 'student@example.test', mssv: 'SV001', ngay_het_han: '2027-10-01', trang_thai: 'active' },
];

export const demoAccounts = [
  { email: 'student@example.test', mssv: 'SV001', vai_tro: 'student', trang_thai: 'active' },
  { email: 'librarian@example.test', mssv: '', vai_tro: 'librarian', trang_thai: 'active' },
  { email: 'staff@example.test', mssv: '', vai_tro: 'staff', trang_thai: 'active' },
  { email: 'admin@example.test', mssv: '', vai_tro: 'admin', trang_thai: 'active' },
];

// Giá trị seed.sql sau migration 001_b1.php, dùng riêng cho bản xem trước.
export const demoPolicies = Object.freeze({
  borrow_per_request: 3,
  borrow_total: 7,
  borrow_days: 14,
  renewal_limit: 2,
  renewal_days: 7,
  renewal_overdue_days: 1,
  overdue_daily_vnd: 2000,
  card_duration: '12_months',
  ebook_per_request: 3,
  ebook_days: 14,
  notice_days_before_due: 2,
  notice_hour_local: '09:00',
  business_timezone: 'Asia/Ho_Chi_Minh',
  damage_surcharge: 0,
});
