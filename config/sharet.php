<?php

/*
| Sharet Pay: a second payment rail beside the own M-Pesa paybill (docs/SHARET_PAYMENTS.md).
|
| One place sets it: /staff/integrations › Sharet Pay. The API key and webhook signing secret live only there
| (encrypted in the integrations table, never shown again); the card's switch is the on/off. Support\Integrations
| writes them over the values below at boot. The options further down may also come from .env, but only as a
| fallback for an option never saved on the card.
*/
return [
    // set by the card's switch (Support\Integrations::apply); off with no saved Sharet integration
    'enabled' => false,

    // saved on the card only (no .env): sk_test_… or sk_live_…, scopes collect, payout and read
    'api_key' => null,

    // the whsec_ value Sharet returns once when the webhook is registered ("Test connection" saves it)
    'webhook_secret' => null,

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
