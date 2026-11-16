-- Business name (owner, 16 Nov 2026): Amazonblue Capital is now Aimzone Ventures Capital.
-- Safe to run more than once; MariaDB 10.3 (live) and MySQL 9 (local).
UPDATE `system` SET `name` = 'Aimzone Ventures Capital', `updated_at` = NOW()
WHERE `id` = 1 AND `name` LIKE 'Amazon%blue Capital';
