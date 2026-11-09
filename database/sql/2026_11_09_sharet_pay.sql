-- =====================================================================================
-- Sharet Pay as a second payment rail (9 November 2026). See docs/SHARET_PAYMENTS.md.
-- The own M-Pesa paybill (C2B / STK) is unchanged; these tables only hold the Sharet side.
-- Hand-written, no migration (owner's rule for this change). Works on MariaDB 10.3 (live) and MySQL 8+/9 (local).
-- Safe to run more than once: CREATE TABLE IF NOT EXISTS only. No data changed or deleted.
--
-- Back up first:   mysqldump --single-transaction --routines --triggers <database> | gzip > backup.sql.gz
-- Run:             mysql <database> < database/sql/2026_11_09_sharet_pay.sql
-- =====================================================================================

-- A borrower's Sharet pay link for (part of) what a loan owes. When paid, it becomes one repayment
-- (repayments.mode = 'sharet') through the same code as an M-Pesa payment (Mpesa\PaymentRecorder::recordCash).
CREATE TABLE IF NOT EXISTS `sharet_payment_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sharet_id` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `loan_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `environment` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'test',
  `amount` decimal(15,2) NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `pay_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(140) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_via` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sharet_transaction_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `repayment_id` bigint unsigned DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `raw` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sharet_payment_requests_sharet_id` (`sharet_id`),
  KEY `idx_sharet_payment_requests_loan` (`loan_id`, `status`),
  KEY `idx_sharet_payment_requests_repayment` (`repayment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Money sent out through Sharet: a loan disbursement or an approved partner payout.
-- status: awaiting_approval -> queued -> paying -> paid | failed; or cancelled. Sent only by payments:sweep
-- (08:00 / 12:00 / 16:00 Africa/Nairobi), at most once per slot, with an Idempotency-Key per attempt.
CREATE TABLE IF NOT EXISTS `sharet_payouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,          -- disbursement | partner_payout
  `loan_id` bigint unsigned DEFAULT NULL,
  `partner_request_id` bigint unsigned DEFAULT NULL,
  `partner_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `destination` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,       -- Sharet "to" object (JSON)
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'awaiting_approval',
  `attempt` int unsigned NOT NULL DEFAULT 1,
  `slot` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,       -- e.g. 2026-11-09T08, the run that sent it
  `idempotency_key` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_body` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,      -- exactly what was sent, so a retry replays it
  `environment` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sharet_payment_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sharet_transaction_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disbursement_id` bigint unsigned DEFAULT NULL,
  `requested_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `failure` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sharet_payouts_idempotency_key` (`idempotency_key`),
  KEY `idx_sharet_payouts_status` (`status`),
  KEY `idx_sharet_payouts_loan` (`loan_id`),
  KEY `idx_sharet_payouts_partner_request` (`partner_request_id`),
  KEY `idx_sharet_payouts_payment` (`sharet_payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every Sharet webhook event received; the unique event id makes a repeated delivery a no-op.
CREATE TABLE IF NOT EXISTS `sharet_webhook_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sharet_webhook_events_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Done: sharet_payment_requests, sharet_payouts and sharet_webhook_events ready' AS result;
