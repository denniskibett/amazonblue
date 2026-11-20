<?php

/*
 * The business finance journey (owner, 20 Nov 2026): a business applies for asset finance or trade finance in steps,
 * every step saved, resumed from a private link. Designs: docs/design/aimzone-canvas, page "Later" (WA-E1..E5 trade,
 * B-1..B-8 and AF-12 asset). This is regulated credit: the copy makes no promise of approval, and every figure shown
 * before review is an estimate.
 */
return [
    'enabled' => (bool) env('FINANCE_JOURNEY_ENABLED', true),

    // days a resume link keeps working after the applicant's last visit
    'link_days' => (int) env('FINANCE_JOURNEY_LINK_DAYS', 30),

    // uploads: per file, per application, and accepted kinds
    'uploads' => [
        'max_kb' => (int) env('FINANCE_JOURNEY_MAX_KB', 10240),
        'max_files' => (int) env('FINANCE_JOURNEY_MAX_FILES', 20),
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    'steps' => [
        'business' => 'Business',
        'need' => 'What you need',
        'terms' => 'Amount and term',
        'documents' => 'Documents',
        'review' => 'Consent and submit',
    ],

    'products' => [
        'asset' => [
            'label' => 'Asset finance',
            'prefix' => 'AIM-AF',
            'need_label' => 'The asset',
            'blurb' => 'Vehicles, trucks, equipment and machinery for your business, paid off over time.',
            // indicative only: reducing balance, monthly instalments
            'monthly_rate' => (float) env('FINANCE_JOURNEY_ASSET_RATE', 3.5),
            'terms' => [6, 12, 18, 24, 36, 48],
            'min_amount' => 100000,
            'max_amount' => 50000000,
            'min_deposit_pct' => 10,
            'categories' => [
                'truck' => 'Truck or trailer',
                'vehicle' => 'Van, pickup or car',
                'bus' => 'Bus or matatu',
                'equipment' => 'Equipment',
                'machinery' => 'Machinery or plant',
                'other' => 'Other business asset',
            ],
        ],
        'trade' => [
            'label' => 'Trade finance',
            'prefix' => 'AIM-TF',
            'need_label' => 'The deal',
            'blurb' => 'Pay a supplier now, finance an LPO or an invoice, and repay when your buyer pays.',
            // indicative only: a fee per month for the tenor, repaid in one sum at the end
            'monthly_rate' => (float) env('FINANCE_JOURNEY_TRADE_RATE', 3.0),
            'terms' => [1, 2, 3, 4, 6],
            'min_amount' => 100000,
            'max_amount' => 100000000,
            'kinds' => [
                'supplier' => 'Pay a supplier (local or import)',
                'lpo' => 'LPO finance',
                'invoice' => 'Invoice discounting',
                'stock' => 'Stock or float finance',
            ],
        ],
    ],

    // document => [label, hint, how many files, required for which products]
    'documents' => [
        'kra_pin' => ['Business KRA PIN certificate', 'From iTax', 1, ['asset', 'trade']],
        'cr12' => ['CR12', 'Directors and shareholders, dated within the last 6 months', 1, ['asset', 'trade']],
        'statements' => ['Bank or M-Pesa statements', 'The last 6 months, business account; one file per month is fine', 6, ['asset', 'trade']],
        'director_id' => ['Directors\' IDs', 'ID or passport of each director', 4, ['asset', 'trade']],
        'quote' => ['Supplier quote or proforma', 'For the asset, from the supplier, with their name and price', 2, ['asset']],
        'deal' => ['LPO, invoice or proforma', 'The order or invoice this request is for', 3, ['trade']],
        'other' => ['Anything else', 'Audited accounts, contracts, a board resolution', 4, []],
    ],

    'statuses' => [
        'draft' => 'In progress',
        'submitted' => 'Submitted',
        'in_review' => 'Being reviewed',
        'more_info' => 'We need something from you',
        'approved' => 'Approved in principle',
        'declined' => 'Not approved',
        'withdrawn' => 'Withdrawn',
    ],
];
