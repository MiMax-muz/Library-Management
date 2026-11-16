<?php
declare(strict_types=1);
$localFile=__DIR__.'/mail.local.php';
$local=is_file($localFile)?require $localFile:[];
if(!is_array($local))throw new RuntimeException('mail.local.php phải trả về một array.');
$read=static function(string $env,string $key,mixed $default='')use($local):mixed{
 $value=getenv($env);return $value!==false?$value:($local[$key]??$default);
};
$username=$read('MAIL_USERNAME','username');
return [
 'driver'=>$read('MAIL_DRIVER','driver','smtp'),
 'environment'=>$read('APP_ENV','environment','production'),
 'app_url'=>$read('APP_URL','app_url','http://127.0.0.1:8080'),
 'host'=>$read('MAIL_HOST','host','smtp.gmail.com'),
 'port'=>(int)$read('MAIL_PORT','port',587),
 'username'=>$username,
 'password'=>$read('MAIL_PASSWORD','password'),
 'from'=>$read('MAIL_FROM','from',$username),
 'encryption'=>$read('MAIL_ENCRYPTION','encryption','tls'),
];
