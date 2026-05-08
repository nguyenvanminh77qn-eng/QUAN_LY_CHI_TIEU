<?php
    /**
     * Simple .env file loader
     * Loads environment variables from .env file into $_ENV and $_SERVER
     */
    
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    function loadEnv($filePath = null) {
        if ($filePath === null) {
            $filePath = __DIR__ . '/../.env';
        }

        if (!file_exists($filePath)) {
            // Nếu không có .env, sử dụng default localhost
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
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse line
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
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

    // Load .env file when this file is included
    loadEnv();
?>
