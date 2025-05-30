<?php
// Database Configuration
define("HOST", "localhost");
define("DB_NAME", "shop_db");
define("USERNAME", "root");
define("PASSWORD", "");

// Application Configuration
define("BASE_PATH", "/FashionShop");

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/error.log');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Character Encoding
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
?>