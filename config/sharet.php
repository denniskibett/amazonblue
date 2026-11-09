<?php

/*
| Sharet Pay: a second payment rail beside the own M-Pesa paybill (docs/SHARET_PAYMENTS.md).
| Every value can be set on /staff/integrations › Sharet Pay (saved encrypted, overrides .env).
| Defaults are the conservative ones: off, repayments shown on the own paybill first, payouts off.
*/
return [
    // the whole rail: pay links, webhook, sync and payouts do nothing while this is false
    'enabled' => (bool) env('SHARET_ENABLED', false),

    'api_key' => env('SHARET_API_KEY'),

    // the whsec_ value Sharet returns once when the webhook is registered ("Test connection" saves it)
    'webhook_secret' => env('SHARET_WEBHOOK_SECRET'),

    'base_url' => env('SHARET_BASE_URL', 'https://sharet.africa/api/v1'),

    // which rail borrowers see first on the repay screen: paybill (own M-Pesa paybill) or sharet
    'repayment_rail' => env('SHARET_REPAYMENT_RAIL', 'paybill'),

    // loan disbursements and approved partner payouts may be sent through Sharet (at the 08:00 / 12:00 / 16:00 runs)
    'payouts_enabled' => (bool) env('SHARET_PAYOUTS_ENABLED', false),

    // how long a borrower's pay link stays open (the amount owed changes with penalties, so keep it short)
    'pay_link_minutes' => (int) env('SHARET_PAY_LINK_MINUTES', 60 * 24 * 3),

    // webhook signature tolerance, seconds
    'signature_tolerance' => 300,
];
