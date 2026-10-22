<?php

/*
 * The repairs plan agreed on 22 Oct 2026 (sheet "Repairs on live 22 Oct 2026"): what was found on each loan, what to do,
 * and what live showed before. The loan detail modal (Operations review) shows it beside the loan, its cycles and the
 * fields to change. A loan the system puts in the broker_fee group and that is not listed here gets the group A
 * broker-fee entry. Amounts are KES; 'live_before' / 'rehearsal' are what live owed and what the 22 Oct local
 * rehearsal produced.
 */
return [
    'plan_date' => '2026-10-22',

    // owner, 22 Oct 2026: do not add extra cash to loans for now (the "Add the extra" suggestion and button are off)
    'allow_add_extra' => false,

    'groups' => [
        'A' => ['label' => 'A. Safe now', 'tone' => 'green', 'meaning' => 'Do it on live now through this page: preview, then apply.'],
        'B' => ['label' => 'B. Hold: check the maths', 'tone' => 'amber', 'meaning' => 'The rehearsal moved this loan far more than the cash explains. Do not apply the automatic rebuild; set the cycle yourself only once the owner has agreed the number.'],
        'C' => ['label' => 'C. Look first', 'tone' => 'blue', 'meaning' => 'Open it together with the owner before changing anything.'],
    ],

    'broker_fee_default' => [
        'group' => 'A',
        'issue' => 'A BROKER FEE / TO BE PAID book row the ledger never posted. The broker already kept the fee out of what it collected.',
        'todo' => 'Book it as the broker fee kept (AC cost): last 3 months to the loan\'s own broker, older pooled to ISIRO.',
    ],

    'loans' => [
        85 => ['group' => 'A', 'issue' => 'A write-off bigger than the debt leaves a credit nobody paid.', 'todo' => 'Cut the write-off to what was owed.', 'live_before' => 240000, 'rehearsal' => 40000],
        290 => ['group' => 'A', 'issue' => 'A broker-fee row larger than what was owed.', 'todo' => 'Book it as the broker fee kept, then cut it to what was owed.', 'live_before' => 6000, 'rehearsal' => 4000],
        123 => ['group' => 'A', 'rollover' => 'write_off', 'issue' => 'Shadrack: a ROLL OVER of 1,357,518 closed the loan, but no other loan took the balance; it is also far above what was owed. A 42,900 disbursement was added in the rehearsal (group C: look first).', 'todo' => 'Turn the ROLL OVER into a write-off cut to what was owed (101,399). Leave the 42,900 for the owner.', 'live_before' => 1357518, 'rehearsal' => 101399],
        347 => ['group' => 'A', 'issue' => 'Leon: a 578,000 write-off far above the debt.', 'todo' => 'Cut the write-off to what was owed.', 'live_before' => 578000, 'rehearsal' => 10000],
        522 => ['group' => 'A', 'issue' => 'Victor: a 4,800 book row twice what was owed.', 'todo' => 'Cut the row to what was owed.', 'live_before' => 4800, 'rehearsal' => 2400],
        220 => ['group' => 'A', 'issue' => 'Leon: the loan was lent and repaid as 90,000 but the money going out was never recorded.', 'todo' => 'Record the missing 90,000 disbursement on cycle 1\'s start.', 'live_before' => 0, 'rehearsal' => 90000],

        139 => ['group' => 'B', 'issue' => 'Samson: 149,000 sent, cycle 1 lent 30,000. The automatic rebuild added the 119,000 and every later cycle re-carried it with interest and penalty: 138,125 became 1,045,036.', 'todo' => 'Owner decides. If the top-up is real, set the principal of the cycle running when it was sent; check whether the later rollovers already included it before carrying it.', 'live_before' => 138125.42, 'rehearsal' => 1045035.98],
        399 => ['group' => 'B', 'issue' => 'Edward: 65,000 sent, 25,000 lent. Rebuild took 310,366 to 863,326.', 'todo' => 'Owner decides; set the cycle by hand, do not use the automatic rebuild.', 'live_before' => 310365.92, 'rehearsal' => 863325.92],
        482 => ['group' => 'B', 'issue' => 'Leon Musau: 118,000 sent, 70,000 lent. Rebuild took 124,000 to 576,000 (and lowered cycle 1 to 60,000).', 'todo' => 'Owner decides; set the cycle by hand.', 'live_before' => 124000, 'rehearsal' => 576000],
        510 => ['group' => 'B', 'issue' => 'Dennis: 34,500 paid in cycle 1, then topped up in cycle 2 (agreed). Rebuild took 274,520 to 960,325.', 'todo' => 'Agreed in principle: add the top-up to cycle 2 at cycle 2\'s rate. Check the number before applying.', 'live_before' => 274520, 'rehearsal' => 960324.80],
        315 => ['group' => 'B', 'issue' => 'Leon: repaid loan; adding the extra cash made it overdue at 792,000.', 'todo' => 'Do not reopen a repaid loan without the owner.', 'live_before' => 192000, 'rehearsal' => 792000],
        365 => ['group' => 'B', 'issue' => 'Sharon: repaid loan; adding 5,000 extra made it overdue.', 'todo' => 'Do not reopen a repaid loan without the owner.', 'live_before' => 25000, 'rehearsal' => 30000],
        513 => ['group' => 'B', 'issue' => 'Erick: 50,000 sent, 40,000 lent. The 10,000 grows at 20% through 5 rollovers (consistent with the design).', 'todo' => 'Owner\'s call; then roll him over as usual (no forbearance).', 'live_before' => 103680, 'rehearsal' => 128563.20],
        511 => ['group' => 'B', 'issue' => 'Joseph (trade finance, active): 186,555 drawn on a 100,000 facility.', 'todo' => 'Owner\'s call: add the draws with interest on them.', 'live_before' => 100000, 'rehearsal' => 186555],
        503 => ['group' => 'B', 'issue' => 'Brian Tanui: 20,000 sent, 10,000 lent.', 'todo' => 'Owner\'s call.', 'live_before' => 10000, 'rehearsal' => 20000],
        538 => ['group' => 'B', 'issue' => 'Victor: 14,000 sent, 1,000 lent.', 'todo' => 'Owner\'s call.', 'live_before' => 1000, 'rehearsal' => 14000],

        79 => ['group' => 'C', 'issue' => 'The rehearsal deleted two repayment rows (156, 157).', 'todo' => 'Look together: if they were real cash they are reversed, never deleted.'],
        519 => ['group' => 'C', 'issue' => 'The rehearsal deleted repayment 778.', 'todo' => 'Look together: reverse it if it was real cash.'],
        309 => ['group' => 'C', 'issue' => 'A ROLL OVER row was turned into a write-off in the rehearsal.', 'todo' => 'Look together: did another loan take the balance?'],
        326 => ['group' => 'C', 'issue' => 'A ROLL OVER row was turned into a write-off in the rehearsal; #326 → #366 Douglas (4,200) may be the real move.', 'todo' => 'Look together; compare with #366.'],
        537 => ['group' => 'C', 'issue' => 'Brian Bogonko: 60,000 sent, cycle lent 100,000.', 'todo' => 'Lend only 60,000, or record a missing 40,000? Owner decides.'],
        534 => ['group' => 'C', 'issue' => 'Anita: 20,000 sent, 30,000 lent.', 'todo' => 'Lend only 20,000, or record a missing 10,000? Owner decides.'],
        30 => ['group' => 'C', 'issue' => 'Samson: #30 owed 8,640; #40 started at 15,600 (+6,960).', 'todo' => 'One loan or two? Owner decides; then move the receivable.'],
        532 => ['group' => 'C', 'issue' => 'Yvonne: ledger 4,700 above her cycles and moving 940 a day; the cycles look right.', 'todo' => 'Claude investigates before any change.'],
    ],
];
