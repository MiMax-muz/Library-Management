<?php
declare(strict_types=1);
return static function (Router $r, array $c): void {
    $r->add('GET', '/api/reports', function () use ($c): void {
        $p = Database::connection($c['db']);
        Auth::requireRoles($p, ['admin', 'librarian']);
        $from = $_GET['from'] ?? '2000-01-01'; $to = $_GET['to'] ?? '2099-12-31';
        if (!is_string($from) || !is_string($to)) throw new ApiException(422, 'INVALID_DATE', 'Ngày không hợp lệ.');
        Input::date($from); Input::date($to);
        if ($from > $to) throw new ApiException(422, 'INVALID_RANGE', 'Ngày bắt đầu phải trước ngày kết thúc.');
        $range = [$from, $to];
        $today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        Response::json([
            'books' => $p->query('SELECT tinh_trang status,COUNT(*) total FROM ban_sach GROUP BY tinh_trang')->fetchAll(),
            'readers' => $p->query('SELECT COUNT(*) total FROM doc_gia')->fetch(),
            'cards' => $p->query('SELECT trang_thai status,COUNT(*) total FROM the_doc_gia GROUP BY trang_thai')->fetchAll(),
            'accounts' => $p->query('SELECT vai_tro role,trang_thai status,COUNT(*) total FROM tai_khoan GROUP BY vai_tro,trang_thai')->fetchAll(),
            'requests' => Input::query($p, 'SELECT status,COUNT(*) total FROM borrow_request WHERE DATE(DATE_ADD(requested_at,INTERVAL 7 HOUR)) BETWEEN ? AND ? GROUP BY status', $range)->fetchAll(),
            'handover' => Input::query($p, 'SELECT COUNT(DISTINCT l.loan_id) loans,COUNT(i.loan_item_id) copies FROM loan l LEFT JOIN loan_item i USING(loan_id) WHERE DATE(DATE_ADD(l.handed_over_at,INTERVAL 7 HOUR)) BETWEEN ? AND ?', $range)->fetch(),
            'returns' => Input::query($p, 'SELECT i.status,COUNT(*) total FROM loan_item i WHERE i.returned_at IS NOT NULL AND DATE(DATE_ADD(i.returned_at,INTERVAL 7 HOUR)) BETWEEN ? AND ? GROUP BY i.status', $range)->fetchAll(),
            'overdue' => Input::query($p, "SELECT COUNT(*) total FROM loan_item WHERE status='borrowed' AND due_date<?", [$today])->fetch(),
            'fines' => Input::query($p, 'SELECT COUNT(*) fine_count,COALESCE(SUM(f.total),0) due_total,COALESCE(SUM(CASE WHEN p.payment_id IS NULL THEN f.total ELSE 0 END),0) outstanding_total,COALESCE(SUM(CASE WHEN DATE(DATE_ADD(p.received_at,INTERVAL 7 HOUR)) BETWEEN ? AND ? THEN p.amount ELSE 0 END),0) collected_total,COUNT(CASE WHEN p.payment_id IS NOT NULL AND DATE(DATE_ADD(p.received_at,INTERVAL 7 HOUR)) BETWEEN ? AND ? THEN 1 END) paid_count FROM fine_record f LEFT JOIN fine_payment p USING(fine_id) WHERE DATE(DATE_ADD(f.created_at,INTERVAL 7 HOUR)) BETWEEN ? AND ?', [$from,$to,$from,$to,$from,$to])->fetch(),
            'fine_types' => Input::query($p, 'SELECT f.violation,COUNT(*) count,COALESCE(SUM(f.total),0) due_total,COALESCE(SUM(CASE WHEN DATE(DATE_ADD(p.received_at,INTERVAL 7 HOUR)) BETWEEN ? AND ? THEN p.amount ELSE 0 END),0) collected_total FROM fine_record f LEFT JOIN fine_payment p USING(fine_id) WHERE DATE(DATE_ADD(f.created_at,INTERVAL 7 HOUR)) BETWEEN ? AND ? GROUP BY f.violation ORDER BY f.violation', [$from,$to,$from,$to])->fetchAll(),
            'ebook_reads' => Input::query($p, 'SELECT COUNT(*) total,COUNT(DISTINCT h.tai_khoan_id) readers FROM reading_history h WHERE DATE(DATE_ADD(h.created_at,INTERVAL 7 HOUR)) BETWEEN ? AND ?', $range)->fetch(),
        ]);
    });
};
