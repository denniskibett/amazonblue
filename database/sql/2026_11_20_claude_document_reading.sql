-- =====================================================================================
-- Claude document reading on business documents (20 November 2026)
-- Same change as database/migrations/2026_11_20_000020_add_claude_reading_to_business_documents.php.
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. No data changed or deleted.
-- =====================================================================================

DROP PROCEDURE IF EXISTS az_add_doc_reading;
DELIMITER //
CREATE PROCEDURE az_add_doc_reading()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'business_documents' AND column_name = 'read_status') THEN
    ALTER TABLE `business_documents`
      ADD COLUMN `read_status` VARCHAR(12) NULL AFTER `size`,
      ADD COLUMN `extracted` TEXT NULL AFTER `read_status`,
      ADD COLUMN `read_error` VARCHAR(255) NULL AFTER `extracted`,
      ADD COLUMN `read_at` TIMESTAMP NULL AFTER `read_error`;
  END IF;
END //
DELIMITER ;
CALL az_add_doc_reading();
DROP PROCEDURE IF EXISTS az_add_doc_reading;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_20_000020_add_claude_reading_to_business_documents' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE migration = m.migration);

SELECT 'Done: Claude document reading columns on business_documents' AS result;
