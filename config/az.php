<?php

/*
 * Words shown for each status on both sides (x-az.status-badge, x-staff.status-badge).
 * Colours come from resources/design/tokens.cjs (CSS classes .st-<status> / .pay-<status>,
 * Tailwind bg-status-<status>-bg ...). See docs/stage2/DESIGN-TOKENS.md.
 */
return [
    'loan_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'disbursed' => 'Disbursed',
        'active' => 'Disbursed', // legacy value, shown like disbursed
        'overdue' => 'Overdue',
        'defaulted' => 'Defaulted',
        'recovery' => 'In recovery',
        'forbearance' => 'Forbearance',
        'repaid' => 'Repaid',
        'written_off' => 'Written off',
        'rejected' => 'Rejected',
    ],

    // customers see friendlier words for a few (x-az.status-badge only)
    'customer_loan_statuses' => [
        'disbursed' => 'Active',
        'active' => 'Active',
        'pending' => 'Under review',
    ],

    'payment_statuses' => [
        'pending' => 'Pending',
        'received' => 'Received',
        'failed' => 'Failed',
    ],
];
