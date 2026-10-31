<?php
// Router for PHP's built-in server (pretty permalinks):  WP_ROOT=/path/to/wordpress php -S 0.0.0.0:8081 router.php
$root = rtrim(getenv('WP_ROOT') ?: __DIR__ . '/site', '/');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = $root . $path;
if ($path !== '/' && is_file($file)) {
    if (preg_match('/\.php$/', $file)) { $_SERVER['SCRIPT_FILENAME'] = $file; $_SERVER['SCRIPT_NAME'] = $path; chdir(dirname($file)); require $file; return true; }
    return false;
}
if ($path !== '/' && is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {   // /wp-admin/ -> wp-admin/index.php
    $_SERVER['SCRIPT_FILENAME'] = rtrim($file, '/') . '/index.php'; $_SERVER['SCRIPT_NAME'] = rtrim($path, '/') . '/index.php'; $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    chdir($file); require rtrim($file, '/') . '/index.php'; return true;
}
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
chdir($root);
require $root . '/index.php';
