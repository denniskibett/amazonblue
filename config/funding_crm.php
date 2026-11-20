<?php

/*
 * Funding CRM (owner, 20 Nov 2026), modelled on the scharge sales CRM. Targets are businesses that need float,
 * working capital, asset or trade finance, never individuals. Prospects come only from lawful public sources
 * (company websites, business directories, public tender awards, association member lists), each with its source.
 *
 * All cold email goes from sales@sharet.africa, shared by every Sharet product, so each email carries an AIM-
 * reference in its subject and replies are sorted by that prefix (scharge uses SC-, Breve BREVE-).
 * This is regulated credit: emails and the playbook never promise approval, a rate or an amount.
 */
return [
    'from' => [
        'address' => env('FUNDING_CRM_FROM', 'sales@sharet.africa'),
        'name' => env('FUNDING_CRM_FROM_NAME', 'Aimzone Ventures'),
    ],

    // a campaign run sends at most this many emails (keeps the shared sender's reputation)
    'batch_limit' => (int) env('FUNDING_CRM_BATCH', 40),

    // days until the call that follows a cold email
    'follow_up_days' => 3,

    'stages' => [
        'new' => 'New',
        'contacted' => 'Contacted',
        'interested' => 'Interested',
        'applied' => 'Applied',
        'approved' => 'Approved',
        'won' => 'Funded',
        'lost' => 'Lost',
    ],

    'needs' => [
        'float' => 'Float',
        'working_capital' => 'Working capital',
        'asset' => 'Asset finance',
        'trade' => 'Trade finance',
    ],

    // where prospect details may come from (Kenya Data Protection Act 2019: business contacts the business published)
    'sources' => [
        'company_website' => 'The company\'s own website',
        'business_directory' => 'Public business directory',
        'tender_award' => 'Public tender award notice',
        'association' => 'Industry association member list',
        'event' => 'Met at an event (card given)',
        'referral' => 'Referral, with their consent',
        'inbound' => 'They contacted us',
    ],

    // segment => playbook. Placeholders in subject and body: {company} {town} {manager} {manager_phone}
    'segments' => [
        'distributor' => [
            'label' => 'Distributors and wholesalers',
            'need' => 'float',
            'approach' => [
                'Ask the finance manager or owner how many days they wait between paying the manufacturer and being paid by retailers.',
                'Their pain is stock-outs when principals want cash upfront at month end; lead with float, not loans.',
                'Ask for their top three principals and how they pay them (cash, 30 days, bank guarantee).',
            ],
            'offers' => ['Stock or float finance against confirmed orders', 'Invoice discounting on big retail and county buyers', 'Truck or van finance for delivery fleets'],
            'objections' => [
                'We already have an overdraft' => 'Good. We are often used beside a bank line for the month-end peak or a large order the bank will not stretch to.',
                'Too expensive' => 'Compare the cost to the margin lost on a missed order. Pricing is set after we see the deal, and there is no obligation to take an offer.',
            ],
            'subject' => 'Float for {company} at month end',
            'body' => "Hello,\n\nI'm {manager} at Aimzone Ventures. We work with distributors in {town} that need float to pay principals before retailers pay them.\n\nIf a large order or a month-end payment ever stretches your cash, we can look at stock or invoice finance against the order itself. Every request is assessed on its own; there is no cost to ask.\n\nWould a 15-minute call this week help? You can also start an application at the link below and finish it when it suits you.\n\nKind regards,\n{manager}\n{manager_phone}",
        ],
        'sacco' => [
            'label' => 'SACCOs and microfinance',
            'need' => 'working_capital',
            'approach' => [
                'Speak to the CEO or finance manager; boards decide, so expect a second meeting.',
                'SACCOs often run short of liquidity when loan demand peaks (school fees, harvests). Ask when that happens.',
                'Ask for SASRA status and the last audited accounts early.',
            ],
            'offers' => ['Wholesale liquidity line for on-lending peaks', 'Asset finance for members\' boda, matatu and farm equipment'],
            'objections' => [
                'We borrow from banks' => 'We can complement a bank line at peaks, and move faster on smaller tranches.',
            ],
            'subject' => 'Liquidity at peak months for {company}',
            'body' => "Hello,\n\nI'm {manager} at Aimzone Ventures. We speak with SACCOs that see loan demand outrun deposits in peak months such as January and harvest time.\n\nWe look at short liquidity lines and member asset finance, each assessed on your accounts and portfolio. Is this something your board has discussed?\n\nI'd be glad to share how it works in a short call.\n\nKind regards,\n{manager}\n{manager_phone}",
        ],
        'fuel' => [
            'label' => 'Fuel stations and dealers',
            'need' => 'float',
            'approach' => [
                'Dealers pay the oil marketer in cash before every load; ask how many loads a week and the cash gap per load.',
                'Owners of 2+ stations feel the gap most; aim there first.',
            ],
            'offers' => ['Float to pay for fuel loads, repaid from daily sales', 'Equipment finance for pumps and tanks'],
            'objections' => ['Margins are thin' => 'That is why the cost is tied to the days you use the money; repaying early costs less.'],
            'subject' => 'Paying for the next load at {company}',
            'body' => "Hello,\n\nI'm {manager} at Aimzone Ventures. Fuel dealers tell us the hardest part is paying for the next load before the last one has sold.\n\nWe look at short float repaid from daily sales, assessed on your own station figures. Would it help to talk it through?\n\nKind regards,\n{manager}\n{manager_phone}",
        ],
        'logistics' => [
            'label' => 'Logistics and transport',
            'need' => 'asset',
            'approach' => [
                'Ask about fleet size, contracts (who they haul for) and how long clients take to pay.',
                'Two needs: another truck to take a new contract, and cash while waiting 60-90 days for big clients to pay.',
            ],
            'offers' => ['Truck and trailer finance against the supplier quote', 'Invoice discounting on haulage contracts'],
            'objections' => ['We buy with cash' => 'Financing one truck keeps your cash for fuel and drivers while the new contract starts paying.'],
            'subject' => 'Another truck for {company}?',
            'body' => "Hello,\n\nI'm {manager} at Aimzone Ventures. We work with transporters who win a contract and need another truck, or who wait months for clients to pay.\n\nWe look at asset finance against a supplier quote, and at invoice finance on haulage contracts, each assessed on its merits. Shall we set up a short call?\n\nKind regards,\n{manager}\n{manager_phone}",
        ],
        'construction' => [
            'label' => 'Construction and contractors',
            'need' => 'trade',
            'approach' => [
                'Contractors with county or national government LPOs wait long for payment; ask about current LPOs and certificates.',
                'Check AGPO status (youth, women) and the paying entity\'s record.',
            ],
            'offers' => ['LPO finance to buy materials for a won tender', 'Plant and machinery finance'],
            'objections' => ['The county pays late' => 'We price for the real payment time and agree it with you before you start.'],
            'subject' => 'Materials for {company}\'s next LPO',
            'body' => "Hello,\n\nI'm {manager} at Aimzone Ventures. Contractors often win an LPO but need cash for materials long before the client pays.\n\nWe look at LPO and invoice finance, and at plant and machinery finance, each assessed on the order and your track record. Would a short call help?\n\nKind regards,\n{manager}\n{manager_phone}",
        ],
        'agribusiness' => [
            'label' => 'Agribusiness and agro-dealers',
            'need' => 'working_capital',
            'approach' => ['Seasonal: ask when they buy stock and when farmers or processors pay.', 'Processors with offtake contracts are the strongest fit.'],
            'offers' => ['Seasonal stock finance', 'Invoice finance on offtake contracts', 'Tractor and equipment finance'],
            'objections' => ['Weather risk' => 'We look at your buyers and history across seasons, not one harvest.'],
            'subject' => 'Seasonal stock for {company}',
            'body' => "Hello,\n\nI'm {manager} at Aimzone Ventures. Agribusinesses we speak to need stock before the season and get paid after it.\n\nWe look at seasonal stock finance, invoice finance on offtake contracts, and equipment finance, each assessed on your figures. Is it worth a short call?\n\nKind regards,\n{manager}\n{manager_phone}",
        ],
        'manufacturer' => [
            'label' => 'Manufacturers',
            'need' => 'trade',
            'approach' => ['Ask about imported raw materials, how they pay suppliers abroad, and their buyers\' payment terms.'],
            'offers' => ['Import and supplier payment finance', 'Machinery finance', 'Invoice discounting on big buyers'],
            'objections' => ['We use LCs from our bank' => 'We can sit beside your bank for supplier payments the bank limit does not cover.'],
            'subject' => 'Paying suppliers ahead of sales at {company}',
            'body' => "Hello,\n\nI'm {manager} at Aimzone Ventures. Manufacturers we speak with often pay for raw materials months before their buyers pay them.\n\nWe look at supplier payment and import finance, machinery finance and invoice finance, each assessed on the deal. Could we have a short call?\n\nKind regards,\n{manager}\n{manager_phone}",
        ],
        'retail' => [
            'label' => 'Large retailers and supermarkets',
            'need' => 'float',
            'approach' => ['Ask about festive and back-to-school stock peaks and supplier credit terms.'],
            'offers' => ['Stock finance for peak seasons', 'Fit-out and equipment finance for new branches'],
            'objections' => ['Suppliers give us credit' => 'For the peaks they will not cover, we can fill the gap for the season only.'],
            'subject' => 'Stock for the busy season at {company}',
            'body' => "Hello,\n\nI'm {manager} at Aimzone Ventures. Retailers tell us the festive and school seasons need more stock than supplier credit covers.\n\nWe look at seasonal stock finance and equipment finance for new branches, each assessed on your figures. Would a short call be useful?\n\nKind regards,\n{manager}\n{manager_phone}",
        ],
    ],
];
