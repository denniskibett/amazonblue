-- =====================================================================================
-- Features and suggestions (17 November 2026)
-- Same change as database/migrations/2026_11_17_000010_create_suggestions_table.php and
-- 2026_11_17_000020_create_feature_items_table.php:
--   suggestions     ideas and problems sent from the Suggest button on every page
--   feature_items   the sysadmin's tracker: working / needs work / missing, with priority and the shortcoming
-- Works on MariaDB 10.3 (live) and MySQL 8+/9 (local). Safe to run more than once. The tracker list is added only
-- when feature_items is empty. Nothing else is changed or deleted.
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `suggestions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `kind` VARCHAR(20) NOT NULL DEFAULT 'idea' COMMENT 'idea, problem',
  `body` TEXT NOT NULL,
  `page` VARCHAR(255) NULL COMMENT 'the page it was sent from',
  `status` VARCHAR(20) NOT NULL DEFAULT 'new' COMMENT 'new, planned, done, declined',
  `reply` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `suggestions_user_id_foreign` (`user_id`),
  KEY `suggestions_status_index` (`status`),
  CONSTRAINT `suggestions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `feature_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `area` VARCHAR(60) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'improve' COMMENT 'working, improve, missing',
  `priority` VARCHAR(10) NOT NULL DEFAULT 'later' COMMENT 'now, next, later',
  `shortcoming` TEXT NULL,
  `suggestion_id` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `feature_items_status_priority_index` (`status`, `priority`),
  KEY `feature_items_suggestion_id_foreign` (`suggestion_id`),
  KEY `feature_items_updated_by_foreign` (`updated_by`),
  CONSTRAINT `feature_items_suggestion_id_foreign` FOREIGN KEY (`suggestion_id`) REFERENCES `suggestions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feature_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `feature_items` (`area`, `name`, `status`, `priority`, `shortcoming`, `created_at`, `updated_at`)
SELECT v.`area`, v.`name`, v.`status`, v.`priority`, NULLIF(v.`shortcoming`, ''), v.`created_at`, v.`updated_at` FROM (
  SELECT 'Lending' AS `area`, 'Loan applications and approvals' AS `name`, 'working' AS `status`, 'later' AS `priority`, '' AS `shortcoming`, NOW() AS `created_at`, NOW() AS `updated_at`
  UNION ALL
  SELECT 'Lending', 'KYC and business reviews', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Lending', 'Loan products and calculator', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Lending', 'Add Disbursement (who is behind the loan)', 'improve', 'now', 'A payout from one source writes no loan_fundings row (11 on live). The sources table (transaction details first, whose money, broker row marked) ships with this release.', NOW(), NOW()
  UNION ALL
  SELECT 'Lending', 'Add Repayment (who it goes back to)', 'improve', 'now', 'Shows each source''s share of the payment. ''Already took it'' (a partner kept their share) is not in the modal yet.', NOW(), NOW()
  UNION ALL
  SELECT 'Lending', 'Edit lock after 3 edits', 'missing', 'next', 'Designed: who is behind the loan and their shares lock after the 3rd edit, with a reason and change history.', NOW(), NOW()
  UNION ALL
  SELECT 'Lending', 'Rollovers, payment plans and grace days', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Lending', 'Collections and debt recovery', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Lending', 'Second sign-offs', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'One record of who funded each loan', 'improve', 'now', 'The same fact sits in 3 tables (disbursements.investment_id, investment_fundings, loan_fundings) that drift: 11 payouts missing rows, 2 pointing at another line.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Shares fixed at disbursement', 'missing', 'next', 'Shares are worked out again at every repayment from 5 rate sources.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Partners list', 'improve', 'next', 'Lent now read an empty wallet; Out on loans and Owed to partners cards ship with this release.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Partner page', 'improve', 'next', '8 wallet cards in two rows and 5 overlapping sheets. Design: one row of cards, three actions.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Partner portal', 'improve', 'next', 'About 10 pages. Should show their investments and when each matures, like a borrower''s loan and due date.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Floats: money to lend per account', 'missing', 'next', 'No page shows what each account has free to lend.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Facility split (loan 458)', 'working', 'later', 'Its own mechanism; fold into loan_fundings later, keeping the 76,032 then 50% penalty rule.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Partner payouts', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Delete partner', 'improve', 'later', 'The button has never worked: nothing listens for it and the server answers JSON.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Reconcile by reference', 'missing', 'later', 'Check each M-Pesa or bank reference against the wallets it moved.', NOW(), NOW()
  UNION ALL
  SELECT 'Partners & funding', 'Payments linked to the partner journal', 'improve', 'later', 'partner_transaction_id on repayments and disbursements is never filled; investment_tracker wallets all 0.', NOW(), NOW()
  UNION ALL
  SELECT 'Payments', 'M-Pesa payments and payouts', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Payments', 'Sharet Pay', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Payments', 'Wallets, reconciliation and end of day', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Reports', 'P&L, cash and portfolio', 'improve', 'next', 'Audit 15 Nov: deleted and merged loans counted in losses, cost of funds lists differ, unit economics always all-time.', NOW(), NOW()
  UNION ALL
  SELECT 'Reports', 'Lending analytics', 'improve', 'later', 'Slow first load, N+1 queries, no CSV export, missing empty states.', NOW(), NOW()
  UNION ALL
  SELECT 'Reports', 'Partners and funding report', 'missing', 'later', 'Earned vs paid vs owed per partner, fixed-return lines coming due.', NOW(), NOW()
  UNION ALL
  SELECT 'Reports', 'Portfolio at risk', 'missing', 'later', 'PAR by days late, by product and officer; recovery rate.', NOW(), NOW()
  UNION ALL
  SELECT 'Platform', 'HTTPS redirect and security headers', 'improve', 'now', 'http:// is not redirected; no HSTS, X-Frame-Options, nosniff or Referrer-Policy.', NOW(), NOW()
  UNION ALL
  SELECT 'Platform', 'Page speed', 'improve', 'later', '0.7-1.0 s before the first byte; check caches and OPcache on deploy.', NOW(), NOW()
  UNION ALL
  SELECT 'Platform', 'Server disk clean-up', 'improve', 'later', 'Disk 76%: weekly clean of old backups and releases.', NOW(), NOW()
  UNION ALL
  SELECT 'Platform', 'Staff mail', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Platform', 'Roles, permissions and audit log', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Platform', 'Deploy with tests, backup and rollback', 'working', 'later', '', NOW(), NOW()
  UNION ALL
  SELECT 'Platform', 'Suggestions from every page', 'working', 'later', 'Staff, borrowers and partners send ideas from the Suggest button; reviewed here.', NOW(), NOW()
) v
WHERE NOT EXISTS (SELECT 1 FROM `feature_items` x);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, m.batch FROM (
  SELECT '2026_11_17_000010_create_suggestions_table' AS migration, COALESCE(MAX(batch), 0) + 1 AS batch FROM `migrations`
  UNION ALL
  SELECT '2026_11_17_000020_create_feature_items_table', COALESCE(MAX(batch), 0) + 1 FROM `migrations`
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.migration = m.migration);

SELECT 'Done: features and suggestions' AS result;
