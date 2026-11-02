<?php
declare(strict_types=1);

/** TV4 — tài khoản và nhân viên B2. Mọi quyền và ràng buộc được kiểm tra ở máy chủ. */
final class AccountService {
    public function __construct(private PDO $pdo) {}

    public static function body(): array {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '');
        if (!$data instanceof stdClass) throw new ApiException(422, 'INVALID_INPUT', 'Cần gửi một đối tượng JSON hợp lệ.');
        return (array)$data;
    }

    private static function value(array $data, string $key): string {
        if (!isset($data[$key])) return '';
        if (!is_string($data[$key])) throw new ApiException(422, 'INVALID_INPUT', "Trường $key không hợp lệ.");
        return trim($data[$key]);
    }

    private static function secret(array $data, string $key): string {
        if (!isset($data[$key])) return '';
        if (!is_string($data[$key])) throw new ApiException(422, 'INVALID_INPUT', "Trường $key không hợp lệ.");
        return $data[$key];
    }

    private static function email(string $value): string {
        $email = mb_strtolower(trim($value), 'UTF-8');
        if (strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL))
            throw new ApiException(422, 'INVALID_EMAIL', 'Email không hợp lệ.');
        return $email;
    }

    private static function mssv(string $value, bool $required): ?string {
        $mssv = strtoupper(trim($value));
        if ($mssv === '' && !$required) return null;
        if (!preg_match('/^[A-Z0-9_-]{1,32}$/D', $mssv))
            throw new ApiException(422, 'INVALID_MSSV', 'MSSV không hợp lệ.');
        return $mssv;
    }

    private static function name(string $value): string {
        $name = trim($value);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150)
            throw new ApiException(422, 'INVALID_NAME', 'Họ tên phải có từ 2 đến 150 ký tự.');
        return $name;
    }

    private static function phone(string $value): ?string {
        $phone = trim($value);
        if ($phone === '') return null;
        if (preg_match('/^\+84\d{9}$/D', $phone)) $phone = '0' . substr($phone, 3);
        if (!preg_match('/^0\d{9}$/D', $phone))
            throw new ApiException(422, 'INVALID_PHONE', 'Số điện thoại phải có dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.');
        return $phone;
    }

    private static function password(string $value): string {
        if (strlen($value) < 8 || strlen($value) > 1024)
            throw new ApiException(422, 'INVALID_PASSWORD', 'Mật khẩu phải có ít nhất 8 ký tự.');
        return password_hash($value, PASSWORD_DEFAULT);
    }

    private static function role(string $role): string {
        if (!in_array($role, ['student', 'librarian', 'staff', 'admin'], true))
            throw new ApiException(422, 'INVALID_ROLE', 'Vai trò không hợp lệ.');
        return $role;
    }

    private static function status(string $status): string {
        if (!in_array($status, ['active', 'locked'], true))
            throw new ApiException(422, 'INVALID_STATUS', 'Trạng thái tài khoản không hợp lệ.');
        return $status;
    }

    private static function employeeStatus(string $status): string {
        if (!in_array($status, ['active', 'inactive'], true))
            throw new ApiException(422, 'INVALID_STATUS', 'Trạng thái nhân viên không hợp lệ.');
        return $status;
    }

    private static function employeeCode(string $code): string {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z0-9_-]{3,32}$/D', $code))
            throw new ApiException(422, 'INVALID_CODE', 'Mã nhân viên phải có 3–32 ký tự chữ, số, _ hoặc -.');
        return $code;
    }

    private static function position(string $value): string {
        $value = trim($value);
        if (mb_strlen($value) < 2 || mb_strlen($value) > 80)
            throw new ApiException(422, 'INVALID_POSITION', 'Chức vụ phải có từ 2 đến 80 ký tự.');
        return $value;
    }

    private static function id(string $value): int {
        if (!preg_match('/^[1-9][0-9]*$/D', $value) || filter_var($value, FILTER_VALIDATE_INT) === false)
            throw new ApiException(404, 'NOT_FOUND', 'Không tìm thấy bản ghi.');
        return (int)$value;
    }

    private function account(int $id, bool $lock = false): array {
        $q = $this->pdo->prepare('SELECT t.*, EXISTS(SELECT 1 FROM doc_gia d WHERE d.tai_khoan_id=t.tai_khoan_id) AS has_profile, EXISTS(SELECT 1 FROM nhan_vien n WHERE n.tai_khoan_id=t.tai_khoan_id) AS has_employee FROM tai_khoan t WHERE t.tai_khoan_id=?' . ($lock ? ' FOR UPDATE' : ''));
        $q->execute([$id]);
        $row = $q->fetch();
        if (!$row) throw new ApiException(404, 'NOT_FOUND', 'Không tìm thấy tài khoản.');
        return $row;
    }

    private static function safeAccount(array $row): array {
        return [
            'tai_khoan_id' => (int)$row['tai_khoan_id'], 'email' => $row['email'],
            'mssv' => $row['mssv'], 'ho_ten' => $row['ho_ten'], 'dien_thoai' => $row['dien_thoai'],
            'vai_tro' => $row['vai_tro'], 'trang_thai' => $row['trang_thai'],
            'has_profile' => (bool)$row['has_profile'], 'has_employee' => (bool)$row['has_employee'],
        ];
    }

    private static function dbError(PDOException $e): never {
        if ($e->getCode() === '23000') throw new ApiException(409, 'DUPLICATE', 'Email, MSSV hoặc mã nhân viên đã tồn tại.');
        throw $e;
    }

    private function insertAccount(string $email, ?string $mssv, string $hash, string $role, string $status, string $name, ?string $phone): int {
        $q = $this->pdo->prepare('INSERT INTO tai_khoan(email,mssv,mat_khau_hash,vai_tro,trang_thai,ho_ten,dien_thoai) VALUES (?,?,?,?,?,?,?)');
        $q->execute([$email, $mssv, $hash, $role, $status, $name, $phone]);
        return (int)$this->pdo->lastInsertId();
    }

    public function register(array $data): array {
        if (isset($data['vai_tro']) || isset($data['trang_thai']) || isset($data['tai_khoan_id']))
            throw new ApiException(422, 'INVALID_INPUT', 'Không thể chọn vai trò hoặc trạng thái khi đăng ký.');
        $email = self::email(self::value($data, 'email'));
        $mssv = self::mssv(self::value($data, 'mssv'), true);
        $name = self::name(self::value($data, 'ho_ten'));
        $phone = self::phone(self::value($data, 'dien_thoai'));
        $hash = self::password(self::secret($data, 'password'));
        try { $id = $this->insertAccount($email, $mssv, $hash, 'student', 'active', $name, $phone); }
        catch (PDOException $e) { self::dbError($e); }
        return self::safeAccount($this->account($id));
    }

    public function login(array $data): array {
        $identifier = self::value($data, 'identifier');
        $password = self::secret($data, 'password');
        if ($identifier === '' || $password === '') throw new ApiException(422, 'INVALID_INPUT', 'Nhập email/MSSV và mật khẩu.');
        $q = $this->pdo->prepare('SELECT * FROM tai_khoan WHERE email=? OR mssv=? LIMIT 1');
        $q->execute([mb_strtolower($identifier, 'UTF-8'), strtoupper($identifier)]);
        $row = $q->fetch();
        if (!$row || $row['trang_thai'] !== 'active' || !password_verify($password, $row['mat_khau_hash']))
            throw new ApiException(401, 'INVALID_CREDENTIALS', 'Thông tin đăng nhập không đúng hoặc tài khoản bị khóa.');
        Auth::login((int)$row['tai_khoan_id']);
        return ['user' => self::safeAccount($this->account((int)$row['tai_khoan_id'])), 'csrf_token' => Auth::csrfToken()];
    }

    public function logout(): void { Auth::requireRoles($this->pdo); Auth::logout(); }

    public function changePassword(array $data): array {
        $user = Auth::requireRoles($this->pdo);
        $current = self::secret($data, 'current_password');
        $new = self::secret($data, 'new_password');
        $row = $this->account((int)$user['tai_khoan_id']);
        if (!password_verify($current, $row['mat_khau_hash']))
            throw new ApiException(422, 'INVALID_PASSWORD', 'Mật khẩu hiện tại không đúng.');
        $hash = self::password($new);
        $q = $this->pdo->prepare('UPDATE tai_khoan SET mat_khau_hash=? WHERE tai_khoan_id=?');
        $q->execute([$hash, $user['tai_khoan_id']]);
        Auth::login((int)$user['tai_khoan_id']);
        return ['ok' => true, 'csrf_token' => Auth::csrfToken()];
    }

    public function listAccounts(): array {
        Auth::requireRoles($this->pdo, ['admin']);
        $page = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
        if ($page === false) throw new ApiException(422, 'INVALID_PAGE', 'Trang không hợp lệ.');
        $perPage = 20;
        $total = (int)$this->pdo->query('SELECT COUNT(*) FROM tai_khoan')->fetchColumn();
        $q = $this->pdo->prepare('SELECT t.*, EXISTS(SELECT 1 FROM doc_gia d WHERE d.tai_khoan_id=t.tai_khoan_id) AS has_profile, EXISTS(SELECT 1 FROM nhan_vien n WHERE n.tai_khoan_id=t.tai_khoan_id) AS has_employee FROM tai_khoan t ORDER BY t.tai_khoan_id DESC LIMIT ? OFFSET ?');
        $q->bindValue(1, $perPage, PDO::PARAM_INT); $q->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
        $q->execute();
        return ['items' => array_map(self::safeAccount(...), $q->fetchAll()), 'page' => $page, 'per_page' => $perPage, 'total' => $total];
    }

    public function createAccount(array $data): array {
        Auth::requireRoles($this->pdo, ['admin']);
        $role = self::role(self::value($data, 'vai_tro'));
        $status = self::status(self::value($data, 'trang_thai') ?: 'active');
        $email = self::email(self::value($data, 'email'));
        $mssv = self::mssv(self::value($data, 'mssv'), $role === 'student');
        if ($role !== 'student' && $mssv !== null) throw new ApiException(422, 'INVALID_MSSV', 'MSSV chỉ dùng cho sinh viên.');
        $name = self::name(self::value($data, 'ho_ten'));
        $phone = self::phone(self::value($data, 'dien_thoai'));
        $hash = self::password(self::secret($data, 'password'));
        try { $id = $this->insertAccount($email, $mssv, $hash, $role, $status, $name, $phone); }
        catch (PDOException $e) { self::dbError($e); }
        return self::safeAccount($this->account($id));
    }

    public function updateAccount(string $key, array $data): array {
        $admin = Auth::requireRoles($this->pdo, ['admin']);
        $id = self::id($key);
        $this->pdo->beginTransaction();
        try {
            $row = $this->account($id, true);
            $role = array_key_exists('vai_tro', $data) ? self::role(self::value($data, 'vai_tro')) : $row['vai_tro'];
            $status = array_key_exists('trang_thai', $data) ? self::status(self::value($data, 'trang_thai')) : $row['trang_thai'];
            $email = array_key_exists('email', $data) ? self::email(self::value($data, 'email')) : $row['email'];
            $mssv = array_key_exists('mssv', $data) ? self::mssv(self::value($data, 'mssv'), $role === 'student') : $row['mssv'];
            if ($role === 'student' && $mssv === null) throw new ApiException(422, 'INVALID_MSSV', 'Sinh viên cần MSSV.');
            if ($role !== 'student' && $mssv !== null) throw new ApiException(422, 'INVALID_MSSV', 'MSSV chỉ dùng cho sinh viên.');
            $name = array_key_exists('ho_ten', $data) ? self::name(self::value($data, 'ho_ten')) : $row['ho_ten'];
            $phone = array_key_exists('dien_thoai', $data) ? self::phone(self::value($data, 'dien_thoai')) : $row['dien_thoai'];
            if ((int)$admin['tai_khoan_id'] === $id && ($role !== 'admin' || $status !== 'active'))
                throw new ApiException(409, 'SELF_LOCK', 'Không thể tự khóa hoặc bỏ quyền quản lý của chính mình.');
            if (($row['has_profile'] && $role !== 'student') || ($row['has_employee'] && $role === 'student'))
                throw new ApiException(409, 'PROFILE_CONFLICT', 'Vai trò không phù hợp với hồ sơ liên kết.');
            if ($status === 'active' && $row['has_employee']) {
                $q = $this->pdo->prepare('SELECT trang_thai FROM nhan_vien WHERE tai_khoan_id=?'); $q->execute([$id]);
                if ($q->fetchColumn() === 'inactive') throw new ApiException(409, 'EMPLOYEE_INACTIVE', 'Nhân viên đã ngừng hoạt động.');
            }
            $hash = array_key_exists('password', $data) && self::secret($data, 'password') !== '' ? self::password(self::secret($data, 'password')) : $row['mat_khau_hash'];
            $q = $this->pdo->prepare('UPDATE tai_khoan SET email=?,mssv=?,mat_khau_hash=?,vai_tro=?,trang_thai=?,ho_ten=?,dien_thoai=? WHERE tai_khoan_id=?');
            $q->execute([$email,$mssv,$hash,$role,$status,$name,$phone,$id]);
            if ($row['has_employee']) {
                $q = $this->pdo->prepare('UPDATE nhan_vien SET ho_ten=?,dien_thoai=? WHERE tai_khoan_id=?');
                $q->execute([$name,$phone,$id]);
            }
            $this->pdo->commit();
        } catch (PDOException $e) { $this->pdo->rollBack(); self::dbError($e); }
          catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
        return self::safeAccount($this->account($id));
    }

    private static function safeEmployee(array $row): array {
        return [
            'nhan_vien_id' => (int)$row['nhan_vien_id'], 'tai_khoan_id' => (int)$row['tai_khoan_id'],
            'ma_nhan_vien' => $row['ma_nhan_vien'], 'ho_ten' => $row['ho_ten'],
            'email' => $row['email'], 'dien_thoai' => $row['dien_thoai'], 'chuc_vu' => $row['chuc_vu'],
            'trang_thai' => $row['trang_thai'], 'vai_tro' => $row['vai_tro'], 'account_status' => $row['account_status'],
        ];
    }

    private function employee(int $id, bool $lock = false): array {
        $q = $this->pdo->prepare('SELECT n.*,t.email,t.vai_tro,t.trang_thai AS account_status FROM nhan_vien n JOIN tai_khoan t ON t.tai_khoan_id=n.tai_khoan_id WHERE n.nhan_vien_id=?' . ($lock ? ' FOR UPDATE' : ''));
        $q->execute([$id]); $row = $q->fetch();
        if (!$row) throw new ApiException(404, 'NOT_FOUND', 'Không tìm thấy nhân viên.');
        return $row;
    }

    public function listEmployees(): array {
        Auth::requireRoles($this->pdo, ['admin']);
        $q = $this->pdo->query('SELECT n.*,t.email,t.vai_tro,t.trang_thai AS account_status FROM nhan_vien n JOIN tai_khoan t ON t.tai_khoan_id=n.tai_khoan_id ORDER BY n.nhan_vien_id DESC');
        return array_map(self::safeEmployee(...), $q->fetchAll());
    }

    public function createEmployee(array $data): array {
        $admin = Auth::requireRoles($this->pdo, ['admin']);
        $role = self::role(self::value($data, 'vai_tro'));
        if ($role === 'student') throw new ApiException(422, 'INVALID_ROLE', 'Nhân viên cần vai trò thủ thư, nhân viên hoặc quản lý.');
        $status = self::employeeStatus(self::value($data, 'trang_thai') ?: 'active');
        $email = self::email(self::value($data, 'email'));
        $name = self::name(self::value($data, 'ho_ten'));
        $phone = self::phone(self::value($data, 'dien_thoai'));
        $code = self::employeeCode(self::value($data, 'ma_nhan_vien'));
        $position = self::position(self::value($data, 'chuc_vu'));
        $existingId = self::value($data, 'tai_khoan_id');
        $hash = $existingId === '' ? self::password(self::secret($data, 'password')) : null;
        if ($existingId !== '' && self::secret($data, 'password') !== '')
            throw new ApiException(422, 'INVALID_INPUT', 'Không đặt mật khẩu mới khi liên kết tài khoản có sẵn.');
        $this->pdo->beginTransaction();
        try {
            if ($existingId === '') {
                $accountId = $this->insertAccount($email, null, $hash, $role, $status === 'active' ? 'active' : 'locked', $name, $phone);
            } else {
                $accountId = self::id($existingId);
                $account = $this->account($accountId, true);
                if ($account['has_employee'] || $account['has_profile'] || $account['vai_tro'] === 'student')
                    throw new ApiException(409, 'PROFILE_CONFLICT', 'Tài khoản đã liên kết hồ sơ hoặc không thuộc nhân viên.');
                if ((int)$admin['tai_khoan_id'] === $accountId && ($status === 'inactive' || $role !== 'admin'))
                    throw new ApiException(409, 'SELF_LOCK', 'Không thể tự khóa hoặc bỏ quyền quản lý.');
                $q = $this->pdo->prepare('UPDATE tai_khoan SET email=?,ho_ten=?,dien_thoai=?,vai_tro=?,trang_thai=? WHERE tai_khoan_id=?');
                $q->execute([$email,$name,$phone,$role,$status === 'active' ? 'active' : 'locked',$accountId]);
            }
            $q = $this->pdo->prepare('INSERT INTO nhan_vien(tai_khoan_id,ma_nhan_vien,ho_ten,dien_thoai,chuc_vu,trang_thai) VALUES (?,?,?,?,?,?)');
            $q->execute([$accountId,$code,$name,$phone,$position,$status]);
            $id = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();
        } catch (PDOException $e) { $this->pdo->rollBack(); self::dbError($e); }
          catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
        return self::safeEmployee($this->employee($id));
    }

    public function updateEmployee(string $key, array $data): array {
        $admin = Auth::requireRoles($this->pdo, ['admin']);
        $id = self::id($key);
        $this->pdo->beginTransaction();
        try {
            $row = $this->employee($id, true);
            $status = array_key_exists('trang_thai', $data) ? self::employeeStatus(self::value($data, 'trang_thai')) : $row['trang_thai'];
            if ((int)$admin['tai_khoan_id'] === (int)$row['tai_khoan_id'] && $status === 'inactive')
                throw new ApiException(409, 'SELF_LOCK', 'Không thể tự ngừng hoạt động.');
            $code = array_key_exists('ma_nhan_vien', $data) ? self::employeeCode(self::value($data, 'ma_nhan_vien')) : $row['ma_nhan_vien'];
            $name = array_key_exists('ho_ten', $data) ? self::name(self::value($data, 'ho_ten')) : $row['ho_ten'];
            $phone = array_key_exists('dien_thoai', $data) ? self::phone(self::value($data, 'dien_thoai')) : $row['dien_thoai'];
            $position = array_key_exists('chuc_vu', $data) ? self::position(self::value($data, 'chuc_vu')) : $row['chuc_vu'];
            $email = array_key_exists('email', $data) ? self::email(self::value($data, 'email')) : $row['email'];
            $role = array_key_exists('vai_tro', $data) ? self::role(self::value($data, 'vai_tro')) : $row['vai_tro'];
            if ($role === 'student') throw new ApiException(422, 'INVALID_ROLE', 'Nhân viên không thể mang vai trò sinh viên.');
            if ((int)$admin['tai_khoan_id'] === (int)$row['tai_khoan_id'] && $role !== 'admin')
                throw new ApiException(409, 'SELF_LOCK', 'Không thể tự bỏ quyền quản lý của chính mình.');
            $q = $this->pdo->prepare('UPDATE nhan_vien SET ma_nhan_vien=?,ho_ten=?,dien_thoai=?,chuc_vu=?,trang_thai=? WHERE nhan_vien_id=?');
            $q->execute([$code,$name,$phone,$position,$status,$id]);
            $accountStatus = $status === 'inactive' ? 'locked' : ($row['trang_thai'] === 'inactive' ? 'active' : $row['account_status']);
            $q = $this->pdo->prepare('UPDATE tai_khoan SET email=?,ho_ten=?,dien_thoai=?,vai_tro=?,trang_thai=? WHERE tai_khoan_id=?');
            $q->execute([$email,$name,$phone,$role,$accountStatus,$row['tai_khoan_id']]);
            $this->pdo->commit();
        } catch (PDOException $e) { $this->pdo->rollBack(); self::dbError($e); }
          catch (Throwable $e) { $this->pdo->rollBack(); throw $e; }
        return self::safeEmployee($this->employee($id));
    }
}
