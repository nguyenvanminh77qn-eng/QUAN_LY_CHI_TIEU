<?php
if (!defined('CODE')) define('CODE', true);

date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * Simple .env file loader
 */
function loadEnv($filePath = null) {
    if ($filePath === null) {
        $filePath = __DIR__ . '/../../.env';
    }

    if (!file_exists($filePath)) {
        if (!isset($_ENV['DB_HOST'])) {
            $_ENV['DB_HOST'] = 'localhost';
            $_ENV['DB_USER'] = 'root';
            $_ENV['DB_PASS'] = '';
            $_ENV['DB_NAME'] = 'quan_ly_chi_tieu';
            $_ENV['DB_PORT'] = '3306';
            $_ENV['SMTP_HOST'] = '';
            $_ENV['SMTP_PORT'] = '587';
            $_ENV['SMTP_USER'] = '';
            $_ENV['SMTP_PASS'] = '';
        }
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (
                (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)
            ) {
                $value = substr($value, 1, -1);
            }
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

loadEnv();

const TEMPLATE = "home";
const ACTION = "welcome";

define('_WEB_HOST', 'http://'.$_SERVER['HTTP_HOST'].'/QUAN_LY_CHI_TIEU');
define('_WEB_ROOT', _WEB_HOST.'/');
define('_WEB_PATH', __DIR__.'/../../');
define('WEB_PATH_TEMPLATE', _WEB_PATH.'src/views/');
define('_ASSETS', _WEB_ROOT.'public/assets/');
define('_CSS', _ASSETS.'css/');
define('_JS', _ASSETS.'js/');
define('_IMG', _ASSETS.'images/');
define('USER_HASH_SALT', 'qlct_salt_2024_secure_key');
