<?php

/*
 * Self-service borrowing for individual borrowers (dashboard "Borrow" panel).
 */
return [

    // Every self-service application uses this product: "Emergency 10 Days" (20% flat, 10 days, 10% penalty).
    'borrower_loan_type_id' => (int) env('LENDING_BORROWER_LOAN_TYPE_ID', 1),

    // Smallest amount a borrower can request.
    'min_amount' => (int) env('LENDING_MIN_AMOUNT', 1000),

    // Limit for a first-time borrower (or anyone whose last loan was not repaid on time).
    'first_time_limit' => (int) env('LENDING_FIRST_TIME_LIMIT', 5000),

    // Limit once the most recent loan was repaid on time.
    'good_standing_limit' => (int) env('LENDING_GOOD_STANDING_LIMIT', 50000),

    // The 4Cs score (App\Services\CreditAssessment) checks the limit: the most each risk category can
    // borrow (never below first_time_limit). Slugs are risk_categories.slug.
    'risk_limit_caps' => [
        'low_risk' => 50000,
        'medium_risk' => 30000,
        'high_risk' => 10000,
        'very_high_risk' => 5000,
    ],

    // Total to repay must fit within this many months of the borrower's declared net income.
    'income_multiple' => (float) env('LENDING_INCOME_MULTIPLE', 1.0),

    // Bridge facilities (businesses, handled by staff) start above this amount; shown on the website.
    'bridge_min_amount' => 100000,

    // How borrowers repay (account › Repay). Stage 2 shows the instructions only; the Daraja integration
    // (STK push, C2B confirmation callbacks, stored payment claims) is Stage 4.
    'mpesa' => [
        // Paybill the borrower pays to. Empty = not confirmed yet: the repay screen shows a placeholder (owner decision).
        'paybill' => env('MPESA_PAYBILL'),
        // What the borrower types as "Account no.": loan_number (e.g. 545) | phone (their registered phone) | id_number.
        'account_format' => env('MPESA_ACCOUNT_FORMAT', 'loan_number'),
        // Stage 4 Phase 4: C2B confirmations record payments automatically. Off = callbacks are refused.
        'enabled' => (bool) env('MPESA_ENABLED', false),
        // "Pay with M-Pesa" sends an STK push to the borrower's phone. Off: the button is shown disabled.
        'stk_enabled' => (bool) env('MPESA_STK_ENABLED', false),
        // "I've paid" claims are stored (payment_claims) and shown on every device; off = the Stage 2 email + session
        'claims_enabled' => (bool) env('MPESA_CLAIMS_ENABLED', false),

        // Daraja. sandbox until the owner adds the production details (then MPESA_ENV=production).
        'env' => env('MPESA_ENV', 'sandbox'),
        'base_url' => [
            'sandbox' => 'https://sandbox.safaricom.co.ke',
            'production' => 'https://api.safaricom.co.ke',
        ],
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        // the shortcode payments go to (C2B) and that STK pushes are made for; 174379 is Safaricom's sandbox
        'shortcode' => env('MPESA_SHORTCODE', '174379'),
        // Lipa na M-Pesa online passkey; the default is Safaricom's published sandbox passkey for 174379
        'passkey' => env('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
        // CustomerPayBillOnline (paybill) or CustomerBuyGoodsOnline (till)
        'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
        // secret path segment in the callback URLs (/mpesa/<secret>/...); set a long random value
        'callback_secret' => env('MPESA_CALLBACK_SECRET'),
        // public base for callback URLs (https, reachable by Safaricom); defaults to APP_URL
        'callback_base' => env('MPESA_CALLBACK_BASE'),
        // Safaricom's callback IPs; empty = any IP (sandbox). Comma-separated in the env.
        'allowed_ips' => array_filter(array_map('trim', explode(',', (string) env('MPESA_ALLOWED_IPS', '')))),
        // automatic matching: the account number must be an open loan's number; the payer's phone must be the
        // borrower's, unless Safaricom hid it, in which case the amount must not be more than is owed
        'match_other_payers' => (bool) env('MPESA_MATCH_OTHER_PAYERS', false),

        // B2C payouts (owner, 23 Oct 2026): staff send a disbursement straight to the borrower's M-Pesa. Every payout
        // needs a second person (LENDING_APPROVALS_ENABLED must be on). Off = the button is not shown.
        'b2c_enabled' => (bool) env('MPESA_B2C_ENABLED', false),
        // the B2C shortcode (often different from the paybill), the API operator ("initiator") and its password
        // encrypted with Safaricom's certificate (the "security credential" from the Daraja portal)
        'b2c_shortcode' => env('MPESA_B2C_SHORTCODE'),
        'b2c_initiator' => env('MPESA_B2C_INITIATOR'),
        'b2c_security_credential' => env('MPESA_B2C_SECURITY_CREDENTIAL'),
        // BusinessPayment (plain payout) | SalaryPayment | PromotionPayment
        'b2c_command' => env('MPESA_B2C_COMMAND', 'BusinessPayment'),
    ],

    // Partner self-service (account › Partner › Add funds / Withdraw). Stage 2 shows instructions and emails staff;
    // nothing is stored or posted from the partner side. Staff record the money through Partners / Investments.
    // Empty values = not confirmed yet: the screen shows a placeholder (owner decision).
    'partner_funding' => [
        // confirmed by the owner, 19 Oct 2026 (env overrides)
        'bank_name' => env('PARTNER_FUNDING_BANK_NAME', 'I&M Bank'),
        'bank_account_name' => env('PARTNER_FUNDING_BANK_ACCOUNT_NAME', 'IMAGINE-NATION AGENCY LIMITED'),
        'bank_account_number' => env('PARTNER_FUNDING_BANK_ACCOUNT_NUMBER', '01104493106350'),
        'bank_branch' => env('PARTNER_FUNDING_BANK_BRANCH', 'Wilson'),
        'bank_swift' => env('PARTNER_FUNDING_BANK_SWIFT'),
        // I&M's bank paybill: the M-Pesa "Account no." is the bank account number, not the partner reference
        'mpesa_paybill' => env('PARTNER_FUNDING_MPESA_PAYBILL', '542542'),
        'mpesa_account' => env('PARTNER_FUNDING_MPESA_ACCOUNT', '01104493106350'),
        // what the partner types as reference / M-Pesa "Account no.": this prefix + their partner number (e.g. AZP-0009)
        'reference_prefix' => env('PARTNER_FUNDING_REFERENCE_PREFIX', 'AZP-'),
        // smallest top-up / withdrawal request a partner can send
        'min_amount' => (int) env('PARTNER_FUNDING_MIN_AMOUNT', 1000),
    ],

    /*
    | The loan engine (Stage 4 Phase 2). v1 = the calculator as it was (LoanCalculator::calculateCycleBalance),
    | kept for 30 days after switching. v2 = App\Services\Loans\CycleBalance: penalty runs daily on the unpaid
    | balance only until it is paid, each payment clears penalty, then interest, then principal; rollover
    | preview and booking use the same function; status follows the cycle balance; loans follow their cycles.
    | Switch only after the owner signs the loans:restate dry-run CSV.
    */
    'engine' => env('LENDING_ENGINE', 'v1'),

    'penalty' => [
        // in_duplum: penalty charged on a cycle stops once it equals the principal still unpaid on the due date
        // (owner 20 Oct 2026: on until the lawyer says otherwise). none: no cap.
        'cap' => env('LENDING_PENALTY_CAP', 'in_duplum'),
    ],

    /*
    | Splitting repayments (Stage 4 Phase 3). Each cash repayment is split by the cycle's waterfall (penalty,
    | interest, principal) and the shares are posted to the partner journal and the ledger:
    |  - brokered loan, AC's money: the broker earns loans.broker_rate % of the interest paid and
    |    loans.broker_penalty_rate % of the penalty paid; AC keeps the rest;
    |  - loan funded by a loan_share line of a broker: the broker earns 100 - brokers.interest_broker % of the
    |    interest; principal paid goes back to the line's capital (lent -> available);
    |  - fixed_rate lines earn their monthly rate from partners:accrue-fixed, not from repayments.
    | Off = nothing is split (the 7 Oct backfill stands).
    */
    'split' => [
        'enabled' => (bool) env('LENDING_SPLIT_ENABLED', false),
        // decision 4 (owner default): a broker earns half the penalty unless brokers.penalty_broker says otherwise
        'default_broker_penalty_rate' => 50,
    ],

    // Maker-checker (Stage 4 Phase 5, decision 14): approvals above approval_limits, write-offs, reversals and
    // partner payouts need a second person with the same permission. Off = one person, as before.
    'approvals' => [
        'enabled' => (bool) env('LENDING_APPROVALS_ENABLED', false),
    ],

    // Borrower and partner requests stored with a status (Stage 4 Phase 5). Off = email + audit row, as in Stage 2.
    'requests' => [
        'enabled' => (bool) env('LENDING_REQUESTS_ENABLED', false),
    ],

    /*
    | Customer notifications (Stage 4 Phase 6): stored in the notification centre, emailed, and sent by SMS for
    | approved, money sent, payment received, due in 3 days, due today, overdue (day 1 and 7), not approved,
    | request decided, partner funds confirmed and withdrawal paid. Service messages only (no marketing).
    | Sent through the queue; reminders go out at 09:00 Nairobi. Off = nothing is sent (Stage 2 emails stay).
    */
    'notifications' => [
        'enabled' => (bool) env('NOTIFICATIONS_ENABLED', false),
        'sms' => [
            // log (writes to the log only) | africastalking | kenyasms
            'driver' => env('SMS_DRIVER', 'log'),
            'africastalking' => [
                'username' => env('AT_USERNAME', 'sandbox'),
                'api_key' => env('AT_API_KEY'),
                'sender_id' => env('AT_SENDER_ID'),
            ],
            // KenyaSMS (owner, 24 Oct 2026). How their API takes a message is set here, so matching their
            // documentation is a settings change: the URL, where the key goes, the field names, JSON or form.
            'kenyasms' => [
                'url' => env('KENYASMS_URL'),
                'api_key' => env('KENYASMS_API_KEY'),
                'sender_id' => env('KENYASMS_SENDER_ID'),
                // bearer (Authorization: Bearer <key>) | header:<Header-Name> | field:<body field name>
                'auth' => env('KENYASMS_AUTH', 'bearer'),
                // json | form
                'format' => env('KENYASMS_FORMAT', 'json'),
                'field_to' => env('KENYASMS_FIELD_TO', 'phone'),
                'field_message' => env('KENYASMS_FIELD_MESSAGE', 'message'),
                'field_sender' => env('KENYASMS_FIELD_SENDER', 'sender_id'),
                // how the number is written: 254 (2547XXXXXXXX) | +254 | 0 (07XXXXXXXX)
                'phone_format' => env('KENYASMS_PHONE_FORMAT', '254'),
            ],
        ],
    ],

    // Borrower behaviour (owner, 23 Oct 2026): when on, the suggested limit from each borrower's repayment
    // history (borrower_profiles, analytics:profiles nightly) can lower their ladder limit. Off = shown to staff only.
    'behaviour_limits' => (bool) env('LENDING_BEHAVIOUR_LIMITS', false),

    // Partner KYC and online subscription (owner, 23 Oct 2026): a partner verifies themselves (or their business),
    // picks a short- or long-term line and an amount, signs the subscription, sends the money and staff confirm it
    // on Requests, which opens the line. Needs LENDING_REQUESTS_ENABLED. Off = Add funds works as before.
    'partner_subscriptions' => [
        'enabled' => (bool) env('LENDING_PARTNER_SUBSCRIPTIONS', false),
        // bump when the subscription wording changes; stored with each signed copy
        'version' => '2026-10-24',
        // long-term terms partners can choose, in months
        'long_term_months' => [6, 12, 24],
    ],

    // Business loans (owner, 23 Oct 2026; plan defaults): a verified business with a limit set by the approver can
    // borrow on a business product. Directors verify themselves from a link; AML screening must be clear.
    'business_loans' => [
        'enabled' => (bool) env('LENDING_BUSINESS_LOANS', false),
        // how long a director's verification link works
        'link_days' => 7,
        // statements that must be on file before approval
        'statement_months' => 3,
    ],

    // AML screening (owner, 23 Oct 2026): staff checklist + the sanctions list staff upload. Required before a
    // business or a partner is approved when on.
    'aml' => [
        'enabled' => (bool) env('LENDING_AML_SCREENING', false),
    ],

    'rollover' => [
        // cycles 2..N keep the previous cycle's rate; from cycle N+1 staff must set a rate or use a payment plan (owner)
        'same_rate_cycles' => 4,
    ],

    /*
    | The ledger (Stage 4 Phase 1): double entry kept in bavix wallets. Every account is a wallet with a
    | 'gl-' slug and 4 decimal places, held by the loan, the partner or the house partner (AC). Every
    | movement is one bavix transfer (from = credit, to = debit), so each entry balances by construction
    | and the sum of all gl- wallets is always 0. App\Services\Ledger posts; nothing reads it for balances
    | yet. Off = nothing is posted; `ledger:backfill` catches up when it is switched on.
    |
    | Balances: a debit-side account (asset, expense) is positive; a credit-side one (liability, equity,
    | income) is negative. Screens show credit-side balances with the sign flipped.
    */
    'ledger' => [
        'enabled' => (bool) env('LENDING_LEDGER_ENABLED', false),

        'decimal_places' => 4,

        // Deleting a repayment entered before today posts a reversal instead: a new row for minus the
        // amount, on the original's date, pointing at it (repayments.reversal_of_id). The original stays.
        // Same-day mistakes are still deleted (owner, 20 Oct 2026). Off = every delete is a soft delete.
        'reversals' => (bool) env('LENDING_REVERSALS_ENABLED', false),

        // holder: house = the house partner (AC), loan = each loan, partner = each partner
        'accounts' => [
            'gl-cash' => ['holder' => 'house', 'type' => 'asset', 'name' => 'Cash (bank and M-Pesa)'],
            'gl-receivable' => ['holder' => 'loan', 'type' => 'asset', 'name' => 'Loan receivable'],
            'gl-capital' => ['holder' => 'partner', 'type' => 'liability', 'name' => 'Partner capital'],
            'gl-earnings' => ['holder' => 'partner', 'type' => 'liability', 'name' => 'Partner earnings payable'],
            'gl-fee-income' => ['holder' => 'house', 'type' => 'income', 'name' => 'Fee income'],
            // engine v2 (Stage 4 Phase 2): what each cycle charges, booked to the loan's receivable
            'gl-interest-income' => ['holder' => 'house', 'type' => 'income', 'name' => 'Interest income'],
            'gl-penalty-income' => ['holder' => 'house', 'type' => 'income', 'name' => 'Penalty income'],
            // a cycle carried into the next for more (+) or less (-, a write-down) than it owed
            'gl-rollover-adjustments' => ['holder' => 'house', 'type' => 'income', 'name' => 'Rollover adjustments'],
            'gl-partner-share' => ['holder' => 'house', 'type' => 'expense', 'name' => 'Partner and broker shares'],
            'gl-discounts' => ['holder' => 'house', 'type' => 'expense', 'name' => 'Discounts and waivers'],
            'gl-bad-debt' => ['holder' => 'house', 'type' => 'expense', 'name' => 'Bad debts written off'],
        ],

        // partner journal type => [partner account, AC account]. A positive journal amount credits the
        // partner account (debit the AC one); a negative amount the reverse. Types not listed move money
        // between a partner's own buckets (lent/available) and are posted from Phase 3.
        'partner_types' => [
            'contribution' => ['gl-capital', 'gl-cash'],
            'withdrawal' => ['by_wallet', 'gl-cash'],
            'profit_distribution' => ['gl-earnings', 'gl-partner-share'],
            'broker_fee_earned' => ['gl-earnings', 'gl-partner-share'],
            'penalty_share' => ['gl-earnings', 'gl-partner-share'],
            'interest' => ['gl-earnings', 'gl-partner-share'],
            'bonus' => ['gl-earnings', 'gl-partner-share'],
        ],

        // partner wallet slug => partner account, for 'by_wallet' (withdrawals)
        'partner_wallets' => [
            'principal' => 'gl-capital',
            'interest' => 'gl-earnings',
            'broker_fee' => 'gl-earnings',
        ],
    ],

    /*
    | audit_logs retention (decision 23, owner default 20 Oct 2026). `php artisan audit:prune` (monthly, dry-run
    | first) deletes only rows past these limits; a table in none of the groups is never pruned.
    |   money:  kept 7 years; rows about a loan are kept until 7 years after the loan closed
    |   kyc:    kept while the person or business exists, then 7 years after the record was deleted
    |   access: role and permission changes, kept 2 years
    */
    'retention' => [
        'money_years' => 7,
        'kyc_years' => 7,
        'access_years' => 2,
        'money_tables' => ['loans', 'repayments', 'disbursements', 'partners', 'partner_transactions', 'investments',
            'debt_recovery_cases', 'payment_claims', 'system'],
        'kyc_tables' => ['users' => 'users', 'businesses' => 'businesses'],
        'access_tables' => ['roles', 'permissions', 'model_has_roles'],
    ],
];
