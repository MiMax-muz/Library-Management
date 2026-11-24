<?php
declare(strict_types=1);
require_once __DIR__.'/../readers/ReaderService.php';
final class AccountService {
    public function __construct(private PDO $db) {}
    public function login(array $data): array {
        $identifier=Input::text($data,'identifier',191);
        $row=Input::query($this->db,'SELECT * FROM tai_khoan WHERE email=? OR mssv=?',[strtolower($identifier),strtoupper($identifier)])->fetch();
        if (!$row || !password_verify((string)($data['password'] ?? ''), $row['mat_khau_hash']) || $row['trang_thai']!=='active') throw new ApiException(401,'LOGIN_FAILED','Thông tin đăng nhập không hợp lệ.');
        if($row['email_verification_required'] && !$row['email_verified_at'])throw new ApiException(403,'EMAIL_NOT_VERIFIED','Hãy xác thực email trước khi đăng nhập. Có thể chọn gửi lại liên kết tại màn hình đăng nhập.');
        if ($row['vai_tro']==='user') Input::transaction($this->db,fn()=>(new ReaderService($this->db))->ensureCard((int)$row['tai_khoan_id']));
        Auth::login((int)$row['tai_khoan_id'],(int)$row['auth_version']); return ['user'=>Auth::user($this->db),'csrf_token'=>Auth::csrfToken()];
    }
    public function create(array $data, bool $admin=false): array {
        $email=strtolower(Input::text($data,'email',191));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new ApiException(422,'INVALID_EMAIL','Email không hợp lệ.');
        $password=$this->password($data['password'] ?? null);
        $role=$admin ? ($data['vai_tro'] ?? 'user') : 'user';
        if(!in_array($role,['user','librarian','admin'],true)) throw new ApiException(422,'INVALID_ROLE','Vai trò không hợp lệ.');
        $mssv=$admin && $role==='user' && trim((string)($data['mssv'] ?? ''))!=='' ? strtoupper(Input::text($data,'mssv',32)) : null;
        $name=Input::text($data,'ho_ten',150);
        Input::query($this->db,'INSERT INTO tai_khoan(email,mssv,mat_khau_hash,vai_tro,ho_ten,dien_thoai) VALUES (?,?,?,?,?,?)',[$email,$mssv,password_hash($password,PASSWORD_DEFAULT),$role,$name,$admin ? ($data['dien_thoai'] ?? null) : null]);
        return ['tai_khoan_id'=>(int)$this->db->lastInsertId()];
    }
    private function password(mixed $v): string {
        if(!is_string($v) || strlen($v)<8 || strlen($v)>72) throw new ApiException(422,'INVALID_PASSWORD','Mật khẩu cần 8–72 byte.');
        return $v;
    }
    public function changePassword(array $data, array $user): void {
        $hash=Input::query($this->db,'SELECT mat_khau_hash FROM tai_khoan WHERE tai_khoan_id=?',[$user['tai_khoan_id']])->fetchColumn();
        if(!password_verify((string)($data['old_password'] ?? ''),$hash)) throw new ApiException(422,'PASSWORD_MISMATCH','Mật khẩu hiện tại không đúng.');
        $newHash=password_hash($this->password($data['password'] ?? null),PASSWORD_DEFAULT);
        $version=Input::transaction($this->db,function()use($hash,$newHash,$user){
            $q=Input::query($this->db,'UPDATE tai_khoan SET mat_khau_hash=?,auth_version=auth_version+1 WHERE tai_khoan_id=? AND mat_khau_hash=?',[$newHash,$user['tai_khoan_id'],$hash]);
            if($q->rowCount()!==1)throw new ApiException(409,'PASSWORD_CHANGED','Mật khẩu đã thay đổi. Hãy đăng nhập lại.');
            Input::query($this->db,"UPDATE account_email_tokens SET used_at=UTC_TIMESTAMP() WHERE tai_khoan_id=? AND purpose='reset' AND used_at IS NULL",[$user['tai_khoan_id']]);
            return (int)Input::query($this->db,'SELECT auth_version FROM tai_khoan WHERE tai_khoan_id=?',[$user['tai_khoan_id']])->fetchColumn();
        });
        Auth::login((int)$user['tai_khoan_id'],$version);
    }
    public function update(int $id,array $data,array $actor): void {
        Input::transaction($this->db,function() use($id,$data,$actor) {
            $rows=Input::query($this->db,'SELECT tai_khoan_id,vai_tro,trang_thai FROM tai_khoan ORDER BY tai_khoan_id FOR UPDATE')->fetchAll();
            $target=null; $admins=0;
            foreach($rows as $r) { if((int)$r['tai_khoan_id']===$id)$target=$r; if($r['vai_tro']==='admin' && $r['trang_thai']==='active')$admins++; }
            if(!$target) throw new ApiException(404,'NOT_FOUND','Không có tài khoản.');
            $role=$data['vai_tro'] ?? $target['vai_tro']; $status=$data['trang_thai'] ?? $target['trang_thai'];
            if(!in_array($role,['user','librarian','admin'],true)||!in_array($status,['active','locked'],true)) throw new ApiException(422,'INVALID_INPUT','Vai trò/trạng thái không hợp lệ.');
            if($target['vai_tro']==='admin' && $target['trang_thai']==='active' && ($role!=='admin'||$status!=='active') && $admins<=1) throw new ApiException(409,'LAST_ADMIN','Phải giữ một Admin hoạt động.');
            Input::query($this->db,'UPDATE tai_khoan SET vai_tro=?,trang_thai=? WHERE tai_khoan_id=?',[$role,$status,$id]);
            if(array_key_exists('ho_ten',$data))Input::query($this->db,'UPDATE tai_khoan SET ho_ten=? WHERE tai_khoan_id=?',[Input::text($data,'ho_ten',150),$id]);
            if(array_key_exists('dien_thoai',$data)){
                $phone=$data['dien_thoai'];if(!is_string($phone)||($phone!==''&&!preg_match('/^\+?[0-9 ()-]{7,20}$/D',$phone)))throw new ApiException(422,'INVALID_PHONE','Số điện thoại không hợp lệ.');
                Input::query($this->db,'UPDATE tai_khoan SET dien_thoai=? WHERE tai_khoan_id=?',[$phone?:null,$id]);
            }
            Input::query($this->db,"UPDATE nhan_vien SET trang_thai=? WHERE tai_khoan_id=?",[$status==='locked'||$role==='user'?'inactive':'active',$id]);
            Input::audit($this->db,'account.update',(string)$id);
        });
    }
    public function employee(array $data): void {
        Input::transaction($this->db,function() use($data) {
            $id=Input::id($data['tai_khoan_id'] ?? null);
            $a=Input::query($this->db,'SELECT * FROM tai_khoan WHERE tai_khoan_id=? FOR UPDATE',[$id])->fetch();
            if(!$a || $a['vai_tro']==='user') throw new ApiException(422,'INVALID_EMPLOYEE','Cần tài khoản nhân viên hoặc Admin.');
            $status=$data['trang_thai'] ?? 'active';
            if(!in_array($status,['active','inactive'],true)) throw new ApiException(422,'INVALID_STATUS','Trạng thái không hợp lệ.');
            // Admin account changes go through the last-admin guard.
            if($a['vai_tro']==='admin' && $status==='inactive') throw new ApiException(409,'ADMIN_ACCOUNT','Khóa tài khoản Admin qua quản lý tài khoản trước khi ngừng hồ sơ.');
            $code=Input::text($data,'ma_nhan_vien',32);
            $collision=Input::query($this->db,'SELECT tai_khoan_id FROM nhan_vien WHERE ma_nhan_vien=? FOR UPDATE',[$code])->fetchColumn();
            if($collision && (int)$collision!==$id)throw new ApiException(409,'EMPLOYEE_CODE_EXISTS','Mã nhân viên đã được sử dụng.');
            Input::query($this->db,'INSERT INTO nhan_vien(tai_khoan_id,ma_nhan_vien,ho_ten,dien_thoai,chuc_vu,trang_thai) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ma_nhan_vien=VALUES(ma_nhan_vien),ho_ten=VALUES(ho_ten),dien_thoai=VALUES(dien_thoai),chuc_vu=VALUES(chuc_vu),trang_thai=VALUES(trang_thai)',[$id,$code,Input::text($data,'ho_ten',150),$data['dien_thoai']??null,$data['chuc_vu']??null,$status]);
            if($status==='inactive') Input::query($this->db,"UPDATE tai_khoan SET trang_thai='locked' WHERE tai_khoan_id=?",[$id]);
            Input::audit($this->db,'employee.save',(string)$id);
        });
    }
}
