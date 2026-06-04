-- ================================================================
-- نظام إنجاز للحلول الذكية - قاعدة البيانات
-- Enjaz Smart Solutions - Database Schema
-- ================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `enjaz_admin`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `enjaz_admin`;

-- ================================================================
-- جدول المستخدمين (موظفون + مدير)
-- ================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`     VARCHAR(100) NOT NULL UNIQUE,
    `password`     VARCHAR(255) NOT NULL,
    `full_name`    VARCHAR(200) NOT NULL,
    `role`         ENUM('admin','employee') NOT NULL DEFAULT 'employee',
    `permissions`  JSON NULL COMMENT 'صلاحيات مخصصة للموظف',
    `status`       TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=نشط, 0=موقوف',
    `last_login`   DATETIME NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- جدول العملاء
-- ================================================================
CREATE TABLE IF NOT EXISTS `clients` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`         VARCHAR(200) NOT NULL COMMENT 'اسم العميل',
    `company_name` VARCHAR(200) NULL COMMENT 'اسم الشركة',
    `mobile`       VARCHAR(20) NOT NULL COMMENT 'رقم الموبايل',
    `mobile_2`     VARCHAR(20) NULL COMMENT 'رقم موبايل إضافي',
    `activity`     VARCHAR(200) NULL COMMENT 'النشاط التجاري',
    `username_note`VARCHAR(200) NULL COMMENT 'اسم المستخدم (للإشارة)',
    `email`        VARCHAR(200) NULL,
    `address`      TEXT NULL,
    `notes`        TEXT NULL,
    `status`       TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=نشط, 0=موقوف',
    `created_by`   INT UNSIGNED NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- جدول الخدمات المتاحة
-- ================================================================
CREATE TABLE IF NOT EXISTS `services` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`            VARCHAR(200) NOT NULL COMMENT 'اسم الخدمة',
    `description`     TEXT NULL,
    `default_price`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `duration_months` INT NOT NULL DEFAULT 12 COMMENT 'مدة الاشتراك الافتراضية بالأشهر',
    `status`          TINYINT(1) NOT NULL DEFAULT 1,
    `created_by`      INT UNSIGNED NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- جدول اشتراكات العملاء في الخدمات
-- ================================================================
CREATE TABLE IF NOT EXISTS `client_subscriptions` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id`    INT UNSIGNED NOT NULL,
    `service_id`   INT UNSIGNED NOT NULL,
    `plan_name`    VARCHAR(200) NULL COMMENT 'اسم الخطة (مثل: أساسية، بريميوم)',
    `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'سعر الاشتراك الفعلي',
    `start_date`   DATE NOT NULL,
    `end_date`     DATE NOT NULL,
    `status`       ENUM('active','expired','cancelled','pending') NOT NULL DEFAULT 'active',
    `notes`        TEXT NULL,
    `created_by`   INT UNSIGNED NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- جدول المدفوعات
-- ================================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id`        INT UNSIGNED NOT NULL,
    `subscription_id`  INT UNSIGNED NULL COMMENT 'مرتبط باشتراك محدد (اختياري)',
    `amount`           DECIMAL(10,2) NOT NULL,
    `payment_date`     DATE NOT NULL,
    `payment_method`   ENUM('cash','transfer','check','other') NOT NULL DEFAULT 'cash',
    `reference_number` VARCHAR(200) NULL COMMENT 'رقم الإيصال أو التحويل',
    `notes`            TEXT NULL,
    `created_by`       INT UNSIGNED NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subscription_id`) REFERENCES `client_subscriptions`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- جدول الفواتير
-- ================================================================
CREATE TABLE IF NOT EXISTS `invoices` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'رقم الفاتورة (INV-0001)',
    `client_id`      INT UNSIGNED NOT NULL,
    `total_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `paid_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `remaining`      DECIMAL(10,2) GENERATED ALWAYS AS (`total_amount` - `paid_amount`) STORED,
    `items`          JSON NOT NULL COMMENT 'بنود الفاتورة',
    `notes`          TEXT NULL,
    `status`         ENUM('draft','sent','paid','partial','cancelled') NOT NULL DEFAULT 'draft',
    `created_by`     INT UNSIGNED NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- جدول سجل رسائل الواتساب
-- ================================================================
CREATE TABLE IF NOT EXISTS `whatsapp_logs` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id`   INT UNSIGNED NULL,
    `mobile`      VARCHAR(20) NOT NULL,
    `message`     TEXT NOT NULL,
    `msg_type`    ENUM('renewal','payment','statement','custom','bulk') NOT NULL DEFAULT 'custom',
    `status`      ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending',
    `response`    TEXT NULL COMMENT 'رد الـ API',
    `sent_by`     INT UNSIGNED NULL,
    `sent_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`sent_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- جدول إعدادات النظام
-- ================================================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`        VARCHAR(100) NOT NULL UNIQUE,
    `value`      TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- بيانات أولية - الإعدادات
-- ================================================================
INSERT INTO `settings` (`key`, `value`) VALUES
('company_name', 'إنجاز للحلول الذكية'),
('company_phone', ''),
('company_email', ''),
('company_address', ''),
('invoice_prefix', 'INV'),
('invoice_counter', '1'),
('whatsapp_api_url', ''),
('whatsapp_api_token', ''),
('whatsapp_sender', ''),
('currency', 'جنيه'),
('renewal_warning_days', '30');

-- ================================================================
-- بيانات أولية - الخدمات
-- ================================================================
INSERT INTO `services` (`name`, `description`, `default_price`, `duration_months`) VALUES
('استضافة البريد الإلكتروني', 'خدمة استضافة البريد الإلكتروني الاحترافي', 1000.00, 12),
('حجز الدومين', 'تسجيل وتجديد أسماء النطاقات', 1200.00, 12),
('تصميم المواقع', 'تصميم وتطوير المواقع الإلكترونية', 3000.00, 0);

-- ================================================================
-- بيانات أولية - المدير الافتراضي
-- كلمة المرور: Admin@2025 (مشفرة بـ bcrypt)
-- ================================================================
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `permissions`) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin', NULL);

SET FOREIGN_KEY_CHECKS = 1;
