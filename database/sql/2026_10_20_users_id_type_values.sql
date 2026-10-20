-- =====================================================================================
-- users.id_type standard values: 20 October 2026
-- Older rows (and the old staff form) stored labels: "National ID", "Passport", "Driving License", "Alien ID",
-- "Military ID". The KYC form uses national_id / passport, so a borrower with "National ID" on file was asked for
-- the ID front again. The app now reads and writes the standard values (User::normaliseIdType); this cleans the rows.
-- Data only, no schema change, so no migration. Works on MariaDB 10.3 (live) and MySQL 8+/9 (local).
-- Safe to run more than once. Undo is not needed: the app reads both forms the same way.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> > backup.sql
-- Run:             mysql <database> < database/sql/2026_10_20_users_id_type_values.sql
-- =====================================================================================

SELECT id_type AS before_value, COUNT(*) AS users FROM `users` GROUP BY id_type;

UPDATE `users` SET id_type = CASE LOWER(TRIM(id_type))
    WHEN 'national id' THEN 'national_id'
    WHEN 'id' THEN 'national_id'
    WHEN 'passport' THEN 'passport'
    WHEN 'driving license' THEN 'drivers_license'
    WHEN 'driving licence' THEN 'drivers_license'
    WHEN 'alien id' THEN 'alien_id'
    WHEN 'military id' THEN 'military_id'
    ELSE id_type END
WHERE LOWER(TRIM(id_type)) IN ('national id', 'id', 'passport', 'driving license', 'driving licence', 'alien id', 'military id')
  AND BINARY id_type NOT IN ('national_id', 'passport');   -- BINARY: the collation would treat 'Passport' as 'passport'

SELECT id_type AS after_value, COUNT(*) AS users FROM `users` GROUP BY id_type;
