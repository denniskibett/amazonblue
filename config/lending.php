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
];
