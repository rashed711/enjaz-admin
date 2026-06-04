-- ═══════════════════════════════════════════════════════
-- migration: إضافة جدول باقات الخدمات
-- نفّذ هذا الـ SQL في phpMyAdmin على قاعدة enjaz_admin
-- ═══════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `service_plans` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `service_id`  INT UNSIGNED   NOT NULL,
  `name`        VARCHAR(150)   NOT NULL,
  `description` TEXT           DEFAULT NULL,
  `price`       DECIMAL(12,2)  NOT NULL DEFAULT '0.00',
  `sort_order`  TINYINT(3)     UNSIGNED NOT NULL DEFAULT '0',
  `status`      TINYINT(1)     NOT NULL DEFAULT '1',
  `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_plan_service` (`service_id`),
  CONSTRAINT `fk_plan_service`
    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── بيانات مبدئية لخدمة "استضافة البريد الإلكتروني" ───────────
-- (غيّر service_id لو الرقم مختلف عندك)
-- INSERT INTO service_plans (service_id, name, description, price, sort_order) VALUES
-- (1, 'باقة 1 جيجا',  'مساحة بريد 1 GB',  800,  1),
-- (1, 'باقة 5 جيجا',  'مساحة بريد 5 GB',  2000, 2),
-- (1, 'باقة 10 جيجا', 'مساحة بريد 10 GB', 4000, 3);
