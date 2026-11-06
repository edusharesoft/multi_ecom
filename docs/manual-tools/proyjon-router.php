<?php
// Router for `php -S` that serves D:\laragon\www\proyjon regardless of the working directory.
$publicPath = 'D:/laragon/www/proyjon/public';
chdir($publicPath);
$uri = urldecode(parse_url('https://example.com'.$_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}
$_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';
require_once $publicPath.'/index.php';
