-- =====================================================================
-- تحديث باقات الاستضافة وأسعار تجديد الدومينات - نظام إنجاز للحلول الذكية
-- تاريخ التحديث: 2026-10-12
--
-- هذا الملف يقوم بالآتي بدون حذف أي بيانات أو التأثير على العملاء:
-- 1. توحيد مسميات باقات البريد الإلكتروني لتكون موحدة (G5, G10, G20)
--    - 02-5G-2000EG تصبح G5
--    - 03-10G-4000EG تصبح G10
--    - 04-20G-5000EG تصبح G20
-- 2. تحديث أسعار تجديد الدومينات:
--    - كل أسعار الدومينات بالجنيه المصري تصبح 1,350 ج.م
--    - كل أسعار الدومينات بالريال السعودي تصبح 86 ر.س
-- 3. إضافة إعدادات أسعار تجديد الدومين في جدول الإعدادات للرجوع إليها مستقبلاً
-- =====================================================================

START TRANSACTION;

-- ─────────────────────────────────────────────────────────────────────
-- 1. توحيد مسميات الباقات في جدول الباقات (service_plans)
-- ─────────────────────────────────────────────────────────────────────
UPDATE `service_plans` 
SET `name` = 'G5', `description` = 'مساحة 5 جيجا' 
WHERE `name` = '02-5G-2000EG';

UPDATE `service_plans` 
SET `name` = 'G10', `description` = 'مساحة 10 جيجا' 
WHERE `name` = '03-10G-4000EG';

UPDATE `service_plans` 
SET `name` = 'G20', `price` = 5000.00, `description` = 'مساحة 20 جيجا' 
WHERE `name` = '04-20G-5000EG';

-- تحديث أسعار باقات الدومينات في service_plans
UPDATE `service_plans` 
SET `price` = 1350.00 
WHERE `service_id` = 2 AND `currency` = 'EGP';

UPDATE `service_plans` 
SET `original_price` = 86.00 
WHERE `service_id` = 2 AND `currency` = 'SAR';

-- تحديث السعر الافتراضي لخدمة الدومين في جدول الخدمات الرئيسية (services)
UPDATE `services` 
SET `default_price` = 1350.00 
WHERE `id` = 2;


-- ─────────────────────────────────────────────────────────────────────
-- 2. توحيد مسميات الباقات في اشتراكات العملاء الحالية (client_subscriptions)
-- ─────────────────────────────────────────────────────────────────────
UPDATE `client_subscriptions` 
SET `plan_name` = 'G5' 
WHERE `plan_name` = '02-5G-2000EG';

UPDATE `client_subscriptions` 
SET `plan_name` = 'G10' 
WHERE `plan_name` = '03-10G-4000EG';

UPDATE `client_subscriptions` 
SET `plan_name` = 'G20' 
WHERE `plan_name` = '04-20G-5000EG';


-- ─────────────────────────────────────────────────────────────────────
-- 3. تحديث أسعار تجديد الدومينات لاشتراكات العملاء النشطة (client_subscriptions)
-- ─────────────────────────────────────────────────────────────────────
-- للعملاء الذين يتعاملون بالجنيه المصري (1,350 جنيه)
UPDATE `client_subscriptions` 
SET `price` = 1350.00 
WHERE `service_id` = 2 
  AND `currency` = 'EGP' 
  AND `status` = 'active';

-- للعملاء الذين يتعاملون بالريال السعودي (86 ريال)
UPDATE `client_subscriptions` 
SET `original_price` = 86.00 
WHERE `service_id` = 2 
  AND `currency` = 'SAR' 
  AND `status` = 'active';


-- ─────────────────────────────────────────────────────────────────────
-- 4. إدراج إعدادات أسعار تجديد الدومينات في جدول settings
-- ─────────────────────────────────────────────────────────────────────
INSERT INTO `settings` (`key`, `value`) 
VALUES ('renewal_domain_price_egp', '1350') 
ON DUPLICATE KEY UPDATE `value` = '1350';

INSERT INTO `settings` (`key`, `value`) 
VALUES ('renewal_domain_price_sar', '86') 
ON DUPLICATE KEY UPDATE `value` = '86';

COMMIT;
