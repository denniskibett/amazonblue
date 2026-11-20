<?php

/*
 * Currency and conversion (owner, 20 Nov 2026; docs/FUNDING_CRM_AND_FINANCE_JOURNEY.md › Currency).
 *
 * Every amount is stored in the currency it was made in and is never rewritten: loans, repayments, disbursements,
 * the ledger and partner money are in the book currency (KES); a row with its own currency (a trade finance
 * application in USD) says so. The display currency only changes how staff summary figures and totals are shown,
 * converted at Sharet's FX rate for the day, with the rate and its date next to them. Loan pages, receipts,
 * statements, agreements and what anyone is charged always stay in their own currency.
 *
 * The list mirrors Sharet's config/fx.php: fiat only, ISO 4217.
 */
return [
    // The currency of every stored amount without its own currency. Never change this on a live system.
    'book' => 'KES',

    // The system display currency. A sysadmin sets it on Integrations › Currency (saved there, else .env).
    // Under the white-label edition each lender's own saved value applies to that lender only.
    'display' => env('DISPLAY_CURRENCY', 'KES'),

    'currencies' => [
        'KES' => ['name' => 'Kenyan shilling', 'decimals' => 2],
        'UGX' => ['name' => 'Ugandan shilling', 'decimals' => 0],
        'TZS' => ['name' => 'Tanzanian shilling', 'decimals' => 0],
        'RWF' => ['name' => 'Rwandan franc', 'decimals' => 0],
        'USD' => ['name' => 'US dollar', 'decimals' => 2],
        'EUR' => ['name' => 'Euro', 'decimals' => 2],
        'GBP' => ['name' => 'Pound sterling', 'decimals' => 2],
        'CNY' => ['name' => 'Chinese yuan', 'decimals' => 2],
        'AED' => ['name' => 'UAE dirham', 'decimals' => 2],
        'INR' => ['name' => 'Indian rupee', 'decimals' => 2],
        'ZAR' => ['name' => 'South African rand', 'decimals' => 2],
    ],

    'rates' => [
        // Sharet's reference rates: {"base":"KES","date":"Y-m-d","rates":{"USD":"0.0077"}} or {"pairs":{"KES/USD":"0.0077"}}
        'url' => env('SHARET_FX_URL', rtrim((string) env('SHARET_BASE_URL', 'https://sharet.africa/api/v1'), '/') . '/fx/rates'),
        'cache_minutes' => (int) env('FX_CACHE_MINUTES', 360),
        'retry_minutes' => 15,
        'timeout' => 5,
        // the last rates Sharet gave us, kept on disk so a cache clear or an outage never loses them
        'store' => storage_path('app/fx-rates.json'),
    ],
];
