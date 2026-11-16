<?php

namespace App\Console\Commands;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use App\Services\Loans\LoanFundings;
use App\Services\Partners\PartnerLines;
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
 *   php artisan partners:fund-loan 5 547 --amount=35000 --code=XXXXXXXXXX --date=2026-10-31 [--share=60] [--penalty=50] [--apply]
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
        {--apply : save it (otherwise worked out and rolled back)}';

    protected $description = 'Book money a partner put into one loan as a line funding part of it (dry run unless --apply)';

    public function handle(PartnerLines $lines, PartnerSlices $slices, LoanFundings $fundings): int
    {
        $partner = Partner::find($this->argument('partner'));
        $loan = Loan::withTrashed()->find($this->argument('loan'));
        $amount = round((float) $this->option('amount'), 2);
        $code = strtoupper(trim((string) $this->option('code')));
        if (! $partner || ! $loan || $amount <= 0 || $code === '') {
            $this->error('Give a partner id, a loan id, --amount above 0 and --code.');

            return self::FAILURE;
        }
        $d = $this->option('disbursement')
            ? Disbursement::where('id', $this->option('disbursement'))->where('loan_id', $loan->id)->first()
            : Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->orderBy('id')->first();
        if (! $d) {
            $this->error('No such cash disbursement on loan #' . $loan->id . '.');

            return self::FAILURE;
        }
        $date = (string) ($this->option('date') ?: substr((string) $d->disburse_date, 0, 10));

        // idempotent: the same money, or a second slice for the same partner, is refused
        if ($lines->codeUsed($code)) {
            $this->warn("{$code} is already recorded as partner money: nothing to do.");

            return self::SUCCESS;
        }
        $rows = $fundings->rows($loan->id);
        if ($rows->contains(fn ($r) => (int) $r->partner_id === $partner->id)) {
            $this->warn("{$partner->name} already has a slice of loan #{$loan->id}: nothing to do.");

            return self::SUCCESS;
        }

        $total = $rows->isEmpty() ? round((float) Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->sum('amount'), 2) : round((float) $rows->sum('amount'), 2);
        $house = $rows->isEmpty() ? $total : round((float) $rows->filter(fn ($r) => $r->is_house)->sum('amount'), 2);
        if ($amount - $house > 0.009) {
            $this->error('Only KES ' . number_format($house, 2) . ' of loan #' . $loan->id . ' is the house\'s to give.');

            return self::FAILURE;
        }
        $houseLine = Investment::where('return_type', 'house')->orderBy('id')->value('id');
        $plan = $rows->reject(fn ($r) => $r->is_house)->map(fn ($r) => ['line' => $r->investment_id, 'amount' => (float) $r->amount, 'disbursement' => $r->disbursement_id])->values()->all();

        $this->line("Partner #{$partner->id} {$partner->name} · loan #{$loan->id} ({$loan->status}, KES " . number_format($total, 2) . ") · disbursement #{$d->id} {$d->transaction} " . substr((string) $d->disburse_date, 0, 10));
        $this->line('Before: ' . ($rows->isEmpty() ? 'no slices (cash from line #' . ($d->investment_id ?? '—') . ')' : $rows->map(fn ($r) => '#' . ($r->investment_id ?? 'none') . ' ' . number_format((float) $r->amount, 2))->implode(', ')));

        $reqBefore = \Illuminate\Support\Facades\Schema::hasTable('partner_requests') ? (DB::table('partner_requests')->max('id') ?? 0) : null;
        $work = function () use ($reqBefore, $lines, $slices, $partner, $loan, $amount, $code, $date, $d, $plan, $house, $houseLine) {
            $line = $lines->open($partner, $amount, 'short_term', null, $code, $date, null, 'Their money for loan #' . $loan->id . ' (owner, 17 Nov 2026)',
                $this->option('share') !== null ? (float) $this->option('share') : null, $this->option('penalty') !== null ? (float) $this->option('penalty') : null);
            $plan[] = ['line' => $line->id, 'amount' => $amount, 'disbursement' => $d->id];
            if ($house - $amount > 0.009) {
                $plan[] = ['line' => $houseLine, 'amount' => round($house - $amount, 2), 'disbursement' => null];
            }
            $changes = $slices->slices($loan, $plan, null, 'partner money for this loan (partners:fund-loan)');
            $lines->closeIfDone($line->fresh());
            $line = $line->fresh();

            // a short-term line whose loans are all repaid closes, and its payout waits on Requests
            $requests = $reqBefore === null ? collect() : DB::table('partner_requests')->where('id', '>', $reqBefore)->get(['id', 'type', 'amount', 'status']);

            return ['line' => $line->id, 'name' => $line->name, 'status' => $line->status, 'changes' => $changes, 'figures' => $lines->status($line), 'requests' => $requests];
        };

        if (! $this->option('apply')) {
            $out = $slices->preview($work);
            $this->report($out['result'], $out['journal']);
            $this->warn('Dry run: nothing saved. Add --apply to save.');

            return self::SUCCESS;
        }
        $before = DB::table('partner_transactions')->max('id') ?? 0;
        $result = $work();
        $this->report($result, DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('t.id', '>', $before)->orderBy('t.id')
            ->get(['p.name', 't.wallet_slug', 't.type', 't.amount', 't.loan_id', 't.reference', 't.transaction_date']));
        $this->info('Saved.');

        return self::SUCCESS;
    }

    private function report(array $r, $journal): void
    {
        $f = $r['figures'];
        $this->line("Line #{$r['line']} {$r['name']} · {$r['status']}");
        $this->line('Slices: ' . implode('; ', $r['changes']));
        $this->line('Line: put in ' . number_format($f['committed'], 2) . ' · lent ' . number_format($f['lent'], 2) . ' · back ' . number_format($f['back'], 2)
            . ' · earned ' . number_format($f['earned'], 2) . ' · loans ' . $f['loans'] . ' (' . $f['open_loans'] . ' open)');
        foreach ($r['requests'] as $q) {
            $this->line("Request #{$q->id} on Requests: {$q->type} KES " . number_format((float) $q->amount, 2) . " ({$q->status})");
        }
        $this->table(['Partner', 'Wallet', 'Type', 'Amount', 'Loan', 'Date', 'Reference'], collect($journal)->map(fn ($t) => [$t->name, $t->wallet_slug, $t->type,
            number_format((float) $t->amount, 2), $t->loan_id, substr((string) $t->transaction_date, 0, 10), $t->reference])->all());
    }
}
