<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use App\Services\Loans\LoanFundings;
use App\Services\Partners\PartnerLoanMoney;
use App\Services\Partners\PartnerSlices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Money a partner put into one loan that was booked as the house's (owner, 17 Nov 2026; ISIRO's 35,000 for Qatamur
 * Mamo's loan 547): opens a short-term line for the partner, dated the day the money went in, and gives that line its
 * part of the loan's disbursement; the house keeps the rest. Every repayment of the loan is re-split, as on the
 * partner page. Without --apply it is worked out and rolled back, and prints the journal rows it would post.
 * Idempotent: refuses when the code is already partner money or the partner already has a slice of the loan.
 *
 *   php artisan partners:fund-loan 5 547 --amount=35000 --code=XXXXXXXXXX --date=2026-10-31 [--share=60] [--penalty=50]
 *       [--paid-on=2026-11-11 --paid-ref=XXXXXXXXXX] [--apply]
 * With --paid-on and --paid-ref the payouts the closed line puts on Requests are recorded as already made (the partner
 * took their part straight from the borrower's repayment), so nothing is left waiting.
 */
class PartnersFundLoan extends Command
{
    protected $signature = 'partners:fund-loan {partner} {loan}
        {--amount= : what the partner put into the loan}
        {--code= : the M-Pesa or bank reference of their money}
        {--date= : the day their money went in (Y-m-d; default the disbursement day)}
        {--disbursement= : which cash disbursement it paid for (default the loan\'s first)}
        {--share= : their share of the interest, % (default their profit share, else 60)}
        {--penalty= : their share of the penalty, % (default 50)}
        {--paid-on= : when the line closes, its payouts were already made on this day (Y-m-d), e.g. kept from the borrower\'s repayment}
        {--paid-ref= : the M-Pesa or bank reference of that payout (with --paid-on)}
        {--by=1 : the staff user recording it}
        {--apply : save it (otherwise worked out and rolled back)}';

    protected $description = 'Book money a partner put into one loan as a line funding part of it (dry run unless --apply)';

    public function handle(PartnerLoanMoney $money, PartnerSlices $slices, LoanFundings $fundings): int
    {
        $partner = Partner::find($this->argument('partner'));
        $loan = Loan::withTrashed()->find($this->argument('loan'));
        if (! $partner || ! $loan) {
            $this->error('Give a partner id and a loan id.');

            return self::FAILURE;
        }
        $rows = $fundings->rows($loan->id);
        $this->line("Partner #{$partner->id} {$partner->name} · loan #{$loan->id} ({$loan->status})");
        $this->line('Before: ' . ($rows->isEmpty() ? 'no slices' : $rows->map(fn ($r) => '#' . ($r->investment_id ?? 'none') . ' ' . number_format((float) $r->amount, 2))->implode(', ')));

        $o = ['code' => $this->option('code'), 'date' => $this->option('date'), 'disbursement' => $this->option('disbursement'), 'share' => $this->option('share'),
            'penalty' => $this->option('penalty'), 'paid_on' => $this->option('paid-on'), 'paid_ref' => $this->option('paid-ref')];
        $by = User::find($this->option('by'));
        $work = fn () => $money->fund($partner, $loan, (float) $this->option('amount'), $o, $by, 'partner money for this loan (partners:fund-loan)');
        try {
            if (! $this->option('apply')) {
                $out = $slices->preview($work);
                if ($this->skipped($out['result'])) {
                    return self::SUCCESS;
                }
                $this->report($out['result'], $out['journal']);
                $this->warn('Dry run: nothing saved. Add --apply to save.');

                return self::SUCCESS;
            }
            $before = DB::table('partner_transactions')->max('id') ?? 0;
            $result = $work();
        } catch (\DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        if ($this->skipped($result)) {
            return self::SUCCESS;
        }
        $this->report($result, DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('t.id', '>', $before)->orderBy('t.id')
            ->get(['p.name', 't.wallet_slug', 't.type', 't.amount', 't.loan_id', 't.reference', 't.transaction_date']));
        $this->info('Saved.');

        return self::SUCCESS;
    }

    private function skipped(array $r): bool
    {
        if ($r['skipped']) {
            $this->warn($r['skipped'] . ' Nothing to do.');
        }

        return (bool) $r['skipped'];
    }

    private function report(array $r, $journal): void
    {
        $f = $r['figures'];
        $this->line("Line #{$r['line']} {$r['name']} · {$r['status']}");
        $this->line('Slices: ' . implode('; ', $r['changes']));
        $this->line('Line: put in ' . number_format($f['committed'], 2) . ' · lent ' . number_format($f['lent'], 2) . ' · back ' . number_format($f['back'], 2)
            . ' · earned ' . number_format($f['earned'], 2) . ' · loans ' . $f['loans'] . ' (' . $f['open_loans'] . ' open)');
        foreach ($r['requests'] as $q) {
            $this->line("Request #{$q->id} on Requests: {$q->type} from {$q->wallet_slug} KES " . number_format((float) $q->amount, 2) . " ({$q->status})");
        }
        $this->table(['Partner', 'Wallet', 'Type', 'Amount', 'Loan', 'Date', 'Reference'], collect($journal)->map(fn ($t) => [$t->name, $t->wallet_slug, $t->type,
            number_format((float) $t->amount, 2), $t->loan_id, substr((string) $t->transaction_date, 0, 10), $t->reference])->all());
    }
}
