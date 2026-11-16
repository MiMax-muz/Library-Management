<?php
declare(strict_types=1);
require_once __DIR__.'/EmailSender.php';
require_once __DIR__.'/../../accounts/AccountService.php';
final class EmailAuthService {
 public function __construct(private PDO $db,private EmailSender $sender){}
 private function throttle(string $action,string $identity): void {
  Input::transaction($this->db,function()use($action,$identity){
   foreach([['ip:'.($_SERVER['REMOTE_ADDR']??'cli'),30],['identity:'.$identity,5]] as [$key,$limit]){
    $bucket=hash('sha256',$action.':'.$key);
    Input::query($this->db,'INSERT IGNORE INTO auth_rate_limits(bucket,hits,window_start) VALUES (?,0,UTC_TIMESTAMP())',[$bucket]);
    $r=Input::query($this->db,'SELECT hits,TIMESTAMPDIFF(SECOND,window_start,UTC_TIMESTAMP()) elapsed FROM auth_rate_limits WHERE bucket=? FOR UPDATE',[$bucket])->fetch();
    if((int)$r['elapsed']>=900)Input::query($this->db,'UPDATE auth_rate_limits SET hits=1,window_start=UTC_TIMESTAMP() WHERE bucket=?',[$bucket]);
    elseif((int)$r['hits']>=$limit)throw new ApiException(429,'TOO_MANY_REQUESTS','Đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau 15 phút.');
    else Input::query($this->db,'UPDATE auth_rate_limits SET hits=hits+1 WHERE bucket=?',[$bucket]);
   }
  });
 }
 private function email(array $d): string {$v=strtolower(Input::text($d,'email',191));if(!filter_var($v,FILTER_VALIDATE_EMAIL))throw new ApiException(422,'INVALID_EMAIL','Email không hợp lệ.');return $v;}
 private function issue(int $id,string $purpose): string {
  $raw=bin2hex(random_bytes(32));$minutes=$purpose==='verify'?30:15;
  Input::query($this->db,'UPDATE account_email_tokens SET used_at=UTC_TIMESTAMP() WHERE tai_khoan_id=? AND purpose=? AND used_at IS NULL',[$id,$purpose]);
  Input::query($this->db,"INSERT INTO account_email_tokens(tai_khoan_id,purpose,token_hash,created_at,expires_at) VALUES (?,?,?,UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL $minutes MINUTE))",[$id,$purpose,hash('sha256',$raw)]);return $raw;
 }
 private function deliver(string $email,string $purpose,string $raw): bool {
  try{$this->sender->send($email,$purpose,$raw);return true;}catch(Throwable $e){error_log('UniLib email delivery failed; transport='.get_class($e));return false;}
 }
 public function register(array $d): array {
  $email=$this->email($d);$this->sender->ready();$this->throttle('register',$email);
  $token=Input::transaction($this->db,function()use($d){$account=(new AccountService($this->db))->create($d);$id=$account['tai_khoan_id'];Input::query($this->db,'UPDATE tai_khoan SET email_verification_required=1 WHERE tai_khoan_id=?',[$id]);return $this->issue($id,'verify');});
  $sent=$this->deliver($email,'verify',$token);
  return ['verification_required'=>true,'message'=>$sent?'Đã tạo tài khoản. Kiểm tra email để xác thực trước khi đăng nhập.':'Tài khoản đã tạo nhưng chưa gửi được email. Hãy chờ 60 giây và chọn gửi lại liên kết.'];
 }
 public function requestLink(array $d,string $purpose): array {
  $email=$this->email($d);$this->sender->ready();$this->throttle($purpose,$email);
  $token=Input::transaction($this->db,function()use($email,$purpose){
   $u=Input::query($this->db,'SELECT * FROM tai_khoan WHERE email=? FOR UPDATE',[$email])->fetch();
   if(!$u||$u['trang_thai']!=='active')return null;
   $pending=(bool)$u['email_verification_required']&&!$u['email_verified_at'];
   if(($purpose==='verify'&&!$pending)||($purpose==='reset'&&$pending))return null;
   $elapsed=Input::query($this->db,'SELECT TIMESTAMPDIFF(SECOND,created_at,UTC_TIMESTAMP()) FROM account_email_tokens WHERE tai_khoan_id=? AND purpose=? ORDER BY id DESC LIMIT 1',[$u['tai_khoan_id'],$purpose])->fetchColumn();
   if($elapsed!==false&&(int)$elapsed<60)return null;
   return $this->issue((int)$u['tai_khoan_id'],$purpose);
  });
  if($token)$this->deliver($email,$purpose,$token);
  return ['message'=>'Nếu email đủ điều kiện, liên kết sẽ được gửi. Kiểm tra cả thư rác; mỗi lần gửi cách nhau ít nhất 60 giây.'];
 }
 public function consume(array $d,string $purpose): array {
  $raw=Input::text($d,'token',64);if(!preg_match('/\A[a-f0-9]{64}\z/',$raw))throw new ApiException(422,'INVALID_TOKEN','Liên kết không hợp lệ.');
  $this->throttle('consume',hash('sha256',$raw));
  $hash=null;if($purpose==='reset'){$password=$d['password']??null;if(!is_string($password)||strlen($password)<8||strlen($password)>72)throw new ApiException(422,'INVALID_PASSWORD','Mật khẩu cần từ 8 đến 72 byte.');if($password!==($d['confirm_password']??null))throw new ApiException(422,'PASSWORD_MISMATCH','Mật khẩu xác nhận không khớp.');$hash=password_hash($password,PASSWORD_DEFAULT);}
  return Input::transaction($this->db,function()use($raw,$purpose,$hash){
   $tokenHash=hash('sha256',$raw);$id=Input::query($this->db,'SELECT tai_khoan_id FROM account_email_tokens WHERE token_hash=? AND purpose=?',[$tokenHash,$purpose])->fetchColumn();
   if(!$id)throw new ApiException(422,'INVALID_TOKEN','Liên kết không hợp lệ, đã dùng hoặc đã hết hạn.');
   $u=Input::query($this->db,'SELECT * FROM tai_khoan WHERE tai_khoan_id=? FOR UPDATE',[$id])->fetch();
   $t=Input::query($this->db,'SELECT id FROM account_email_tokens WHERE token_hash=? AND purpose=? AND used_at IS NULL AND expires_at>UTC_TIMESTAMP() FOR UPDATE',[$tokenHash,$purpose])->fetch();
   if(!$u||$u['trang_thai']!=='active'||!$t||($purpose==='reset'&&$u['email_verification_required']&&!$u['email_verified_at']))throw new ApiException(422,'INVALID_TOKEN','Liên kết không hợp lệ, đã dùng hoặc đã hết hạn.');
   if($purpose==='verify')Input::query($this->db,'UPDATE tai_khoan SET email_verified_at=UTC_TIMESTAMP() WHERE tai_khoan_id=?',[$id]);
   else Input::query($this->db,'UPDATE tai_khoan SET mat_khau_hash=?,auth_version=auth_version+1 WHERE tai_khoan_id=?',[$hash,$id]);
   Input::query($this->db,'UPDATE account_email_tokens SET used_at=UTC_TIMESTAMP() WHERE tai_khoan_id=? AND purpose=? AND used_at IS NULL',[$id,$purpose]);
   return ['message'=>$purpose==='verify'?'Email đã xác thực. Bạn có thể đăng nhập.':'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.'];
  });
 }
}
