Set-Location -LiteralPath 'C:\Users\Admins\Desktop\CNPM\ITTV-LibraryManagement'

$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3306'
$env:DB_NAME='ittv_library'
$env:DB_USER='root'
$env:DB_PASSWORD=''
$env:SESSION_SECURE='0'

& C:/xampp/php/php.exe -S 127.0.0.1:8080 -t frontend backend/dev-router.php
