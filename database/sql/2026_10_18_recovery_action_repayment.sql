-- =====================================================================================
-- Recovery collections become repayments: 18 October 2026
-- recovery_actions.repayment_id links an action's "amount collected" to the Repayment it created
-- (+ index/foreign key fk_recovery_action_repayment, ON DELETE SET NULL).
-- Same change as database/migrations/2026_10_18_000052_add_repayment_id_to_recovery_actions_table.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_18_recovery_action_repayment.sql
-- =====================================================================================

SET @sql = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `recovery_actions` ADD COLUMN `repayment_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `amount_collected`, ADD INDEX `fk_recovery_action_repayment` (`repayment_id`), ADD CONSTRAINT `fk_recovery_action_repayment` FOREIGN KEY (`repayment_id`) REFERENCES `repayments` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION',
  'SELECT ''recovery_actions.repayment_id already exists'' AS note')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'recovery_actions' AND column_name = 'repayment_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- guard outside the aggregate, so a second run inserts nothing
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_10_18_000052_add_repayment_id_to_recovery_actions_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: recovery_actions.repayment_id ready' AS result;
