<?php
/**
 * نموذج إعدادات قاعدة البيانات - نظام إنجاز للحلول الذكية
 * انسخ هذا الملف وأعد تسميته إلى: config/db.php ثم ضع بيانات قاعدة البيانات الخاصة بك
 */

define('DB_HOST',    'localhost');
define('DB_USER',    'your_db_user');
define('DB_PASS',    'your_db_password');
define('DB_NAME',    'your_db_name');
define('DB_CHARSET', 'utf8mb4');

/**
 * إنشاء اتصال PDO بقاعدة البيانات
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[DB Error] ' . $e->getMessage());
            http_response_code(503);
            die(json_encode([
                'error'   => true,
                'message' => 'خدمة قاعدة البيانات غير متاحة حالياً. يرجى المحاولة لاحقاً.'
            ]));
        }
    }
    return $pdo;
}

