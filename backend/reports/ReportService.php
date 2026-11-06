<?php
declare(strict_types=1);

/** Báo cáo nền TV4 tuần 3: chỉ đọc các bảng đã có trong B1. */
final class ReportService {
    public function __construct(private PDO $pdo) {}

    public function baseline(): array {
        Auth::requireRoles($this->pdo, ['admin']);
        $this->pdo->beginTransaction();
        try {
            $books = [];
            $bookIndexes = [];
            $rows = $this->pdo->query('SELECT s.sach_id, s.ma_sach, s.ten_sach, tl.ten_the_loai AS the_loai, s.dang_luu_hanh FROM sach s JOIN the_loai tl ON tl.the_loai_id = s.the_loai_id ORDER BY s.sach_id')->fetchAll();
            foreach ($rows as $row) {
                $bookIndexes[(string)$row['sach_id']] = count($books);
                $books[] = [
                    'ma_sach' => $row['ma_sach'],
                    'ten_sach' => $row['ten_sach'],
                    'the_loai' => $row['the_loai'],
                    'dang_luu_hanh' => (bool)$row['dang_luu_hanh'],
                    'copies' => [],
                ];
            }
            $copies = $this->pdo->query('SELECT sach_id, tinh_trang, dang_luu_hanh FROM ban_sach ORDER BY ban_sach_id')->fetchAll();
            foreach ($copies as $copy) {
                $index = $bookIndexes[(string)$copy['sach_id']];
                $books[$index]['copies'][] = [
                    'tinh_trang' => $copy['tinh_trang'],
                    'dang_luu_hanh' => (bool)$copy['dang_luu_hanh'],
                ];
            }

            $readers = $this->pdo->query('SELECT td.ma_the, d.ho_ten, tk.email, tk.mssv, td.ngay_het_han, td.trang_thai FROM doc_gia d JOIN tai_khoan tk ON tk.tai_khoan_id = d.tai_khoan_id LEFT JOIN the_doc_gia td ON td.doc_gia_id = d.doc_gia_id ORDER BY d.doc_gia_id')->fetchAll();
            $accounts = $this->pdo->query('SELECT email, mssv, vai_tro, trang_thai FROM tai_khoan ORDER BY tai_khoan_id')->fetchAll();
            $this->pdo->commit();
            return ['books' => $books, 'readers' => $readers, 'accounts' => $accounts];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
