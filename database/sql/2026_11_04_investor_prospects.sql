-- =====================================================================================
-- Capital raising (4 November 2026)
-- Same change as database/migrations/2026_11_04_000030_create_investor_prospects_table.php, plus the role and
-- permission in database/seeders/RoleSeeder.php:
--   investor_prospects                 an investment manager's prospects, first call to money in
--   partners.investment_manager_id     who looks after the partner
--   role "investment-manager"          view dashboard, view partners, view investments, view partner statements,
--                                      view investment fundings, manage investor pipeline
--   "manage investor pipeline" also for super-admin and admin
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- After it: php artisan permission:cache-reset
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `investor_prospects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `kind` VARCHAR(20) NOT NULL DEFAULT 'individual',
  `contact` VARCHAR(255) NULL,
  `wants` VARCHAR(255) NULL,
  `ticket` DECIMAL(15,2) NULL,
  `stage` VARCHAR(20) NOT NULL DEFAULT 'first_call',
  `chance` TINYINT UNSIGNED NULL,
  `next_on` DATE NULL,
  `next_step` VARCHAR(255) NULL,
  `manager_id` BIGINT UNSIGNED NULL,
  `partner_id` BIGINT UNSIGNED NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `investor_prospects_stage_index` (`stage`),
  CONSTRAINT `investor_prospects_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investor_prospects_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS az_add_im;
DELIMITER //
CREATE PROCEDURE az_add_im()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'partners' AND column_name = 'investment_manager_id') THEN
    ALTER TABLE `partners` ADD COLUMN `investment_manager_id` BIGINT UNSIGNED NULL AFTER `user_id`,
      ADD CONSTRAINT `partners_investment_manager_id_foreign` FOREIGN KEY (`investment_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
  END IF;
END //
DELIMITER ;
CALL az_add_im();
DROP PROCEDURE IF EXISTS az_add_im;

INSERT INTO roles (name, guard_name, created_at, updated_at)
SELECT 'investment-manager', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'investment-manager' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'manage investor pipeline', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'manage investor pipeline' AND guard_name = 'web');

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.guard_name = 'web'
WHERE p.guard_name = 'web' AND (
      (r.name = 'investment-manager' AND p.name IN ('view dashboard', 'view partners', 'view investments', 'view partner statements', 'view investment fundings', 'manage investor pipeline'))
   OR (r.name IN ('super-admin', 'admin') AND p.name = 'manage investor pipeline'))
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_04_000030_create_investor_prospects_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: investor prospects, partners.investment_manager_id, investment-manager role' AS result;
