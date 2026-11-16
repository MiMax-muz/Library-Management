<?php
declare(strict_types=1);
require_once __DIR__.'/../../lib/phpmailer/Exception.php';
require_once __DIR__.'/../../lib/phpmailer/PHPMailer.php';
require_once __DIR__.'/../../lib/phpmailer/SMTP.php';
final class EmailSender {
 public function __construct(private array $config){}
 public function ready(): void {
  $url=$this->config['app_url'];$parts=parse_url($url);
  if(!$parts || !in_array($parts['scheme']??'',['http','https'],true) || empty($parts['host']) || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']))throw new ApiException(503,'MAIL_NOT_CONFIGURED','Chưa cấu hình địa chỉ ứng dụng gửi email.');
  if($this->config['driver']==='file' && $this->config['environment']==='test')return;
  if($this->config['driver']==='smtp' && strtolower($this->config['host'])==='smtp.gmail.com' && (!filter_var($this->config['username'],FILTER_VALIDATE_EMAIL)||trim($this->config['password'])===''))throw new ApiException(503,'MAIL_NOT_CONFIGURED','Hệ thống chưa thiết lập tài khoản Gmail gửi thư và mật khẩu ứng dụng. Vui lòng liên hệ quản trị viên.');
  if($this->config['driver']!=='smtp'||!$this->config['host']||!filter_var($this->config['from'],FILTER_VALIDATE_EMAIL)||!in_array($this->config['encryption'],['tls','smtps'],true))throw new ApiException(503,'MAIL_NOT_CONFIGURED','Chưa cấu hình máy chủ gửi email.');
 }
 public function send(string $email,string $purpose,string $token): void {
  $this->ready();$verify=$purpose==='verify';$minutes=$verify?30:15;
  $url=rtrim($this->config['app_url'],'/').'/?auth='.$purpose.'&token='.rawurlencode($token);
  $title=$verify?'Xác thực email UniLib':'Đặt lại mật khẩu UniLib';
  $plain="$title\nMở liên kết: $url\nLiên kết dùng một lần, có hiệu lực $minutes phút. Nếu không yêu cầu, hãy bỏ qua email này.";
  if($this->config['driver']==='file'){
   $dir=__DIR__.'/../../storage/mail-test';if(!is_dir($dir)&&!mkdir($dir,0700,true))throw new RuntimeException('Cannot create test mail directory');
   if(file_put_contents($dir.'/'.bin2hex(random_bytes(12)).'.json',json_encode(['to'=>$email,'purpose'=>$purpose,'url'=>$url,'body'=>$plain],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX)===false)throw new RuntimeException('Cannot write test mail');return;
  }
  $mail=new PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host=$this->config['host'];$mail->Port=$this->config['port'];$mail->SMTPAuth=$this->config['username']!=='';$mail->Username=$this->config['username'];$mail->Password=$this->config['password'];$mail->SMTPSecure=$this->config['encryption'];$mail->CharSet='UTF-8';$mail->Timeout=15;
  $mail->setFrom($this->config['from'],'UniLib');$mail->addAddress($email);$mail->Subject=$title;$mail->isHTML(true);$safe=htmlspecialchars($url,ENT_QUOTES,'UTF-8');$mail->Body='<h2>'.$title.'</h2><p><a href="'.$safe.'">'.$title.'</a></p><p>Liên kết dùng một lần, hết hạn sau '.$minutes.' phút. Nếu không yêu cầu, hãy bỏ qua email này.</p>';$mail->AltBody=$plain;$mail->send();
 }
}
