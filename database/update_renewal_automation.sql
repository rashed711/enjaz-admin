-- ============================================================
-- تحديث قاعدة بيانات نظام إنجاز للحلول الذكية
-- ميزة التنبيه التلقائي للتجديدات ومراجعة الإشعارات عبر الواتساب
-- ============================================================

-- 1. إنشاء جدول تتبع ومراجعة إشعارات التجديد (جدول مستقل جديد تماماً)
CREATE TABLE IF NOT EXISTS `renewal_notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `cycle_end_date` DATE NOT NULL,
  `stage` INT NOT NULL COMMENT '60, 45, 30, 15, 7',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending_review' COMMENT 'pending_review, approved, sent, skipped, failed',
  `message` TEXT NOT NULL,
  `admin_notified_at` DATETIME NULL,
  `reviewed_at` DATETIME NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `sent_at` DATETIME NULL,
  `response` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_client_cycle_stage` (`client_id`, `cycle_end_date`, `stage`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. إدراج إعدادات التجديد الافتراضية في جدول settings (بدون تكرار)
INSERT INTO `settings` (`key`, `value`) 
SELECT 'renewal_auto_review', '1' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'renewal_auto_review');

INSERT INTO `settings` (`key`, `value`) 
SELECT 'renewal_admin_mobile', '201028855779' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'renewal_admin_mobile');

INSERT INTO `settings` (`key`, `value`) 
SELECT 'renewal_alert_hour', '20:00' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'renewal_alert_hour');

INSERT INTO `settings` (`key`, `value`) 
SELECT 'renewal_send_hour', '11:00' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'renewal_send_hour');

INSERT INTO `settings` (`key`, `value`) 
SELECT 'renewal_last_check_date', '' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'renewal_last_check_date');

-- 3. إعدادات النسخة الاحتياطية اليومية لشيت العملاء على البريد (الساعة 12:00 منتصف الليل)
INSERT INTO `settings` (`key`, `value`) 
SELECT 'backup_email', 'rashed1711@gmail.com' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'backup_email');

INSERT INTO `settings` (`key`, `value`) 
SELECT 'backup_clients_enabled', '1' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'backup_clients_enabled');

INSERT INTO `settings` (`key`, `value`) 
SELECT 'backup_clients_hour', '00:00' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'backup_clients_hour');

INSERT INTO `settings` (`key`, `value`) 
SELECT 'backup_clients_last_date', '' 
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `key` = 'backup_clients_last_date');

-- 4. إضافة حقل تحديد عملاء محددين لجدول الجدولة (إن لم يكن موجوداً)
-- ALTER TABLE `whatsapp_schedules` ADD COLUMN `target_client_ids` TEXT NULL AFTER `target_type`;


