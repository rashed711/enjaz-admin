<?php
/**
 * إعدادات التطبيق العامة - نظام إنجاز للحلول الذكية
 */

// ── إعدادات الجلسة الآمنة ─────────────────────────────────────────
session_name('enjaz_sess');
if (session_status() === PHP_SESSION_NONE) {
    // أعلى مستوى أمان للجلسة
    ini_set('session.cookie_httponly', '1');      // يمنع الوصول عبر JavaScript
    ini_set('session.cookie_samesite', 'Strict'); // يمنع CSRF عبر cross-site
    ini_set('session.use_strict_mode', '1');      // يرفض Session IDs غير معتمدة
    ini_set('session.use_only_cookies', '1');     // الجلسة عبر الكوكيز فقط
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');    // HTTPS فقط إن كان متاحاً
    }
    session_start();
}

// ── الثوابت الأساسية ─────────────────────────────────────────────
define('APP_NAME',     'إنجاز للحلول الذكية');
define('APP_VERSION',  '1.0.0');
define('APP_URL',      '');             // ← رابط السيرفر إذا أردت (اختياري)
define('APP_TIMEZONE', 'Africa/Cairo');

// ── إعداد التوقيت ────────────────────────────────────────────────
date_default_timezone_set(APP_TIMEZONE);

// ── مسارات الملفات ───────────────────────────────────────────────
define('ROOT_PATH',    dirname(__DIR__));
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('INCLUDES_PATH',ROOT_PATH . '/includes');
define('ASSETS_URL',   'assets');

// ── إعدادات الصلاحيات المتاحة ────────────────────────────────────
define('ALL_PERMISSIONS', [
    'view_clients'       => 'عرض العملاء',
    'add_clients'        => 'إضافة عملاء',
    'edit_clients'       => 'تعديل العملاء',
    'delete_clients'     => 'حذف العملاء',
    'view_payments'      => 'عرض المدفوعات',
    'add_payments'       => 'إضافة مدفوعات',
    'delete_payments'    => 'حذف مدفوعات',
    'view_subscriptions' => 'عرض الاشتراكات',
    'add_subscriptions'  => 'إضافة اشتراكات',
    'edit_subscriptions' => 'تعديل الاشتراكات',
    'manage_services'    => 'إدارة الخدمات',
    'view_reports'       => 'عرض التقارير',
    'send_whatsapp'      => 'إرسال واتساب',
    'print_invoices'     => 'طباعة الفواتير',
    'manage_users'       => 'إدارة المستخدمين',
    'manage_expenses'    => 'إدارة المصروفات',
]);

// ── تحميل قاعدة البيانات ─────────────────────────────────────────
require_once CONFIG_PATH . '/db.php';

// التأكد من جعل حقول التواريخ تقبل القيمة الفارغة (Nullable)
try {
    $db = getDB();
    $desc = $db->query("DESCRIBE client_subscriptions start_date")->fetch();
    if ($desc && strtolower($desc['Null'] ?? 'no') === 'no') {
        $db->exec("ALTER TABLE client_subscriptions MODIFY start_date DATE NULL;");
        $db->exec("ALTER TABLE client_subscriptions MODIFY end_date DATE NULL;");
    }
} catch (Exception $e) {}

// التأكد من إضافة حقول الدومين ومزود الخدمة لجدول العملاء
try {
    $db = getDB();
    $desc = $db->query("SHOW COLUMNS FROM clients LIKE 'domain'")->fetch();
    if (!$desc) {
        $db->exec("ALTER TABLE clients ADD COLUMN domain VARCHAR(255) NULL AFTER activity;");
        $db->exec("ALTER TABLE clients ADD COLUMN domain_provider VARCHAR(100) NULL AFTER domain;");
    }
} catch (Exception $e) {}

// التأكد من إضافة عمود السيرفر (لوحة التحكم) لجدول العملاء
try {
    $db = getDB();
    $desc = $db->query("SHOW COLUMNS FROM clients LIKE 'server_panel'")->fetch();
    if (!$desc) {
        $db->exec("ALTER TABLE clients ADD COLUMN server_panel VARCHAR(100) NOT NULL DEFAULT 'cp.enjaz.cloud' AFTER username_note;");
    }
} catch (Exception $e) {}

// التأكد من إضافة حقل الدولة لجدول العملاء وتعيين الدول للعملاء الحاليين
try {
    $db = getDB();
    $desc = $db->query("SHOW COLUMNS FROM clients LIKE 'country'")->fetch();
    if (!$desc) {
        $db->exec("ALTER TABLE clients ADD COLUMN country VARCHAR(10) NOT NULL DEFAULT 'EG' AFTER activity;");
        
        // تعيين الدول للعملاء الحاليين تلقائياً بناءً على مفتاح الهاتف أو الملاحظات
        $db->exec("UPDATE clients SET country = 'SA' WHERE mobile LIKE '966%' OR mobile LIKE '+966%' OR mobile_2 LIKE '966%' OR name LIKE '%(سعودي)%';");
        $db->exec("UPDATE clients SET country = 'SD' WHERE mobile LIKE '249%' OR mobile LIKE '+249%';");
        $db->exec("UPDATE clients SET country = 'AE' WHERE mobile LIKE '971%' OR mobile LIKE '+971%';");
        $db->exec("UPDATE clients SET country = 'KW' WHERE mobile LIKE '965%' OR mobile LIKE '+965%';");
        $db->exec("UPDATE clients SET country = 'QA' WHERE mobile LIKE '974%' OR mobile LIKE '+974%';");
        $db->exec("UPDATE clients SET country = 'OM' WHERE mobile LIKE '968%' OR mobile LIKE '+968%';");
        $db->exec("UPDATE clients SET country = 'BH' WHERE mobile LIKE '973%' OR mobile LIKE '+973%';");
        $db->exec("UPDATE clients SET country = 'ID' WHERE mobile LIKE '62%' OR mobile LIKE '+62%' OR mobile_2 LIKE '62%' OR mobile_2 LIKE '+62%';");
    }
    // تحديث أي عميل إندونيسي قديم كان مصنف OTHER
    $db->exec("UPDATE clients SET country = 'ID' WHERE (mobile LIKE '62%' OR mobile LIKE '+62%' OR mobile_2 LIKE '62%' OR mobile_2 LIKE '+62%') AND (country = 'OTHER' OR country = 'EG');");
} catch (Exception $e) {}

// التأكد من إضافة حقول العملة لجدول الباقات service_plans
try {
    $db = getDB();
    $desc = $db->query("SHOW COLUMNS FROM service_plans LIKE 'currency'")->fetch();
    if (!$desc) {
        $db->exec("ALTER TABLE service_plans ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'EGP' AFTER description;");
        $db->exec("ALTER TABLE service_plans ADD COLUMN original_price DECIMAL(12,2) NULL DEFAULT NULL AFTER currency;");
    }
} catch (Exception $e) {}

// التأكد من إضافة حقول العملة لجدول الاشتراكات client_subscriptions
try {
    $db = getDB();
    $desc = $db->query("SHOW COLUMNS FROM client_subscriptions LIKE 'currency'")->fetch();
    if (!$desc) {
        $db->exec("ALTER TABLE client_subscriptions ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'EGP' AFTER plan_name;");
        $db->exec("ALTER TABLE client_subscriptions ADD COLUMN original_price DECIMAL(12,2) NULL DEFAULT NULL AFTER currency;");
    }
} catch (Exception $e) {}


// التأكد من تعديل جدول المدفوعات لدعم طرق دفع مخصصة وإرفاق ملفات الإيصال
try {
    $db = getDB();
    $db->exec("ALTER TABLE payments MODIFY COLUMN payment_method VARCHAR(100) NOT NULL DEFAULT 'كاش';");
    
    $desc = $db->query("SHOW COLUMNS FROM payments LIKE 'receipt_file'")->fetch();
    if (!$desc) {
        $db->exec("ALTER TABLE payments ADD COLUMN receipt_file VARCHAR(255) NULL AFTER notes;");
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key` = 'payment_methods'");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $db->exec("INSERT INTO settings (`key`, `value`) VALUES ('payment_methods', 'كاش,تحويل بنكي,فودافون كاش,شيك,أخرى')");
    }
} catch (Exception $e) {}

// التأكد من إضافة عمود الترتيب لجدول الخدمات
try {
    $db = getDB();
    $desc = $db->query("SHOW COLUMNS FROM services LIKE 'sort_order'")->fetch();
    if (!$desc) {
        $db->exec("ALTER TABLE services ADD COLUMN sort_order INT NOT NULL DEFAULT 0;");
        
        // تعيين الترتيب الافتراضي بناءً على اسم الخدمة
        $db->exec("UPDATE services SET sort_order = 1 WHERE name LIKE '%دومين%' OR name LIKE '%domain%';");
        $db->exec("UPDATE services SET sort_order = 2 WHERE name LIKE '%بريد%' OR name LIKE '%ايميل%' OR name LIKE '%email%';");
        $db->exec("UPDATE services SET sort_order = 3 WHERE name LIKE '%تصميم%' OR name LIKE '%موقع%' OR name LIKE '%web%';");
        $db->exec("UPDATE services SET sort_order = 99 WHERE sort_order = 0;");
    }
} catch (Exception $e) {}

// التأكد من إنشاء جدول المصروفات
try {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS `expenses` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `title` VARCHAR(255) NOT NULL,
          `amount` DECIMAL(10,2) NOT NULL,
          `expense_date` DATE NOT NULL,
          `category` VARCHAR(100) NOT NULL DEFAULT 'أخرى',
          `notes` TEXT NULL,
          `created_by` INT UNSIGNED NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_expense_date` (`expense_date`),
          KEY `idx_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {}

// التأكد من إنشاء جدول قائمة انتظار رسائل الواتساب المجدولة
try {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS `whatsapp_queue` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `client_id` INT UNSIGNED NULL,
          `mobile` VARCHAR(50) NOT NULL,
          `message` TEXT NOT NULL,
          `send_at` DATETIME NOT NULL,
          `min_delay` INT UNSIGNED NOT NULL DEFAULT 3,
          `max_delay` INT UNSIGNED NOT NULL DEFAULT 15,
          `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, sending, sent, failed',
          `response` TEXT NULL,
          `sent_by` INT UNSIGNED NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          KEY `idx_status_send_at` (`status`, `send_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {}


// ── تحميل الدوال المساعدة ────────────────────────────────────────
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth.php';

// ── جلب إعدادات النظام من قاعدة البيانات ────────────────────────
function getSettings(): array {
    static $settings = null;
    if ($settings === null) {
        try {
            $db  = getDB();
            $stmt = $db->query("SELECT `key`, `value` FROM `settings`");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return $settings;
}

function getSetting(string $key, string $default = ''): string {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}
