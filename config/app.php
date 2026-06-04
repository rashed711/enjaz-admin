<?php
/**
 * إعدادات التطبيق العامة - نظام إنجاز للحلول الذكية
 */

// ── إعدادات الجلسة ──────────────────────────────────────────────
session_name('enjaz_session');
if (session_status() === PHP_SESSION_NONE) {
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
