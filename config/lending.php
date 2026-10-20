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
        // Stage 4: C2B callbacks record payments automatically. Off until the integration exists.
        'enabled' => (bool) env('MPESA_ENABLED', false),
        // Stage 4: "Pay with M-Pesa" sends an STK push to the borrower's phone. Off: the button is shown disabled.
        'stk_enabled' => (bool) env('MPESA_STK_ENABLED', false),
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

        // holder: house = the house partner (AC), loan = each loan, partner = each partner
        'accounts' => [
            'gl-cash' => ['holder' => 'house', 'type' => 'asset', 'name' => 'Cash (bank and M-Pesa)'],
            'gl-receivable' => ['holder' => 'loan', 'type' => 'asset', 'name' => 'Loan receivable'],
            'gl-capital' => ['holder' => 'partner', 'type' => 'liability', 'name' => 'Partner capital'],
            'gl-earnings' => ['holder' => 'partner', 'type' => 'liability', 'name' => 'Partner earnings payable'],
            'gl-fee-income' => ['holder' => 'house', 'type' => 'income', 'name' => 'Fee income'],
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
