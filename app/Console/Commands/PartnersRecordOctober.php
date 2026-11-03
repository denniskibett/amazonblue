<?php

namespace App\Console\Commands;

use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use App\Services\Ledger;
use App\Services\Loans\RepaymentSplit;
use App\Services\Partners\PartnerLines;
use App\Services\Partners\PartnerSlices;
use App\Services\Requests;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off (owner, 3 Nov 2026): records Buosti's and Obiri's October money the way it happened, with the new partner
 * tools, so their statements, the split and the ledger agree with the bank.
 *
 *   Buosti  70,000 in (UJDJAACF19, 13 Oct) → Cynthia #540 60,000 + Dennis #510 10,000 (half of disbursement 633);
 *           60% of the interest; the house settled the #510 slice on 23 Oct; paid 78,400 on 23 Oct (058812596383).
 *   Obiri   30,000 in (UJDHV9KYXU, 13 Oct) → Nigel #536 20,000 (5620GVKU8173) + Dennis #510 10,000;
 *           20,000 in (UJEHV9NTDV, 14 Oct) → Nigel #536 20,000 (5618ATKG9067);
 *           60% of the interest; the house settled the #510 slice on 24 Oct; paid 56,000 on 24 Oct (UJOBDBT08L).
 * Without --apply it shows what it would post and rolls everything back. Each step is skipped when already done.
 * The payouts are left waiting on Second sign-offs (another person approves; they post on the day they were paid).
 */
class PartnersRecordOctober extends Command
{
    protected $signature = 'partners:record-october {--apply : Save it} {--by= : User id recording it (default: the first super-admin)}';

    protected $description = 'Record Buosti\'s and Obiri\'s October partner money, slices, house settlements and payouts';

    public function handle(PartnerLines $lines, PartnerSlices $slices, Requests $requests, RepaymentSplit $split): int
    {
        $by = $this->option('by') ? User::findOrFail($this->option('by')) : User::role('super-admin')->orderBy('id')->firstOrFail();
        $buosti = Partner::findOrFail(9);
        $obiri = Partner::findOrFail(8);
        $houseLine = Investment::where('return_type', 'house')->orderBy('id')->value('id');
        $log = [];

        $run = function () use ($lines, $slices, $requests, $split, $by, $buosti, $obiri, $houseLine, &$log) {
            // 1. Buosti's line: its shares, and the day the money came in
            $b = Investment::findOrFail(3);
            if ($b->partner_share_rate === null) {
                $b->forceFill(['partner_share_rate' => 60, 'partner_penalty_rate' => 50])->save();
                $log[] = 'Buosti line #3: 60% of interest, 50% of penalty';
            }
            $c = \App\Models\PartnerTransaction::where('partner_id', 9)->where('reference', 'MPESA-UJDJAACF19')->first();
            if ($c && substr((string) $c->getRawOriginal('transaction_date'), 0, 10) !== '2026-10-13') {
                $c->forceFill(['transaction_date' => '2026-10-13'])->save();
                $log[] = 'Buosti money in dated 13 Oct (was ' . $c->getOriginal('transaction_date') . ')';
            }
            // 2. Obiri's two lines
            $line = fn (string $code) => Investment::where('name', 'like', '%' . $code . '%')->first();
            $a = $line('UJDHV9KYXU') ?? tap($lines->open($obiri, 30000, 'short_term', null, 'UJDHV9KYXU', '2026-10-13', $by, 'Obiri: Nigel #536 + Dennis #510', 60, 50), fn () => $log[] = 'Obiri line: 30,000 in on 13 Oct (UJDHV9KYXU)');
            $bb = $line('UJEHV9NTDV') ?? tap($lines->open($obiri, 20000, 'short_term', null, 'UJEHV9NTDV', '2026-10-14', $by, 'Obiri: Nigel #536', 60, 50), fn () => $log[] = 'Obiri line: 20,000 in on 14 Oct (UJEHV9NTDV)');
            // 3. slices
            $has = fn (int $loanId, int $lineId) => DB::table('loan_fundings')->where('loan_id', $loanId)->where('investment_id', $lineId)->exists();
            // Buosti's second money (UJK6OAKZ64, 20 Oct): Dennis #510 5,000 (disbursement 646) + Anthony #539 5,000 (disbursement 640)
            $b2 = $line('UJK6OAKZ64') ?? tap($lines->open($buosti, 10000, 'short_term', null, 'UJK6OAKZ64', '2026-10-20', $by, 'Buosti: Dennis #510 + Anthony #539', 60, 50), fn () => $log[] = 'Buosti line: 10,000 in on 20 Oct (UJK6OAKZ64)');
            // #510 first: a line closes when its loans are done, so Obiri's first line must hold its #510 slice before #536 closes it
            if (! $has(510, 3)) {
                $log[] = 'Dennis #510: ' . implode('; ', $slices->slices(Loan::findOrFail(510), [
                    ['line' => $houseLine, 'amount' => 329520, 'disbursement' => null], ['line' => 3, 'amount' => 10000, 'disbursement' => 633],
                    ['line' => $a->id, 'amount' => 10000, 'disbursement' => 633], ['line' => $b2->id, 'amount' => 5000, 'disbursement' => 646]], $by, 'October partner money'));
            }
            if (! $has(539, $b2->id)) {
                $log[] = 'Anthony #539: ' . implode('; ', $slices->slices(Loan::findOrFail(539), [['line' => $b2->id, 'amount' => 5000, 'disbursement' => 640]], $by, 'October partner money'));
            }
            if (! $has(536, $a->id)) {
                $log[] = 'Nigel #536: ' . implode('; ', $slices->slices(Loan::findOrFail(536), [
                    ['line' => $a->id, 'amount' => 20000, 'disbursement' => 643], ['line' => $bb->id, 'amount' => 20000, 'disbursement' => 632]], $by, 'October partner money'));
            }
            // #540 was already Buosti's; re-split it now that the line has its 60%
            foreach (Loan::findOrFail(540)->cycles()->pluck('id') as $cycleId) {
                $split->syncCycle((int) $cycleId);
            }
            $split->syncLineReturns($b->fresh());
            // 4. the house settled both #510 slices
            foreach ([[3, '2026-10-23'], [$a->id, '2026-10-24'], [$b2->id, '2026-10-30']] as [$lineId, $day]) {
                $row = DB::table('loan_fundings')->where('loan_id', 510)->where('investment_id', $lineId)->first();
                if ($row && ! $row->settled_on) {
                    $s = $slices->settleByHouse($row->id, $day, null, $by, 'October payout');
                    $log[] = "House settled {$s['partner']}'s KES " . number_format($s['capital'], 2) . " of #510 on {$day}: interest share KES " . number_format($s['interest'], 2);
                }
            }
            // 5. payouts already made: on the requests the lines queued, else new ones
            $b2id = $b2->id;
            foreach ([[$buosti, '2026-10-23', '058812596383', 78400, [3]], [$obiri, '2026-10-24', 'UJOBDBT08L', 56000, null],
                      [$buosti, '2026-10-30', '019605362653', 10000, [$b2id], 'principal'], [$buosti, '2026-10-30', '310057359503', 1200, [$b2id], 'interest']] as $pay) {
                [$p, $day, $ref, $total, $onlyLines] = $pay;
                $onlyWallet = $pay[5] ?? null;
                $done = 0.0;
                $q = DB::table('partner_requests')->where('partner_id', $p->id)->where('type', 'withdrawal')->whereIn('status', ['pending', 'prepared'])->whereNull('bank_reference')
                    ->when($onlyWallet, fn ($w) => $w->where('wallet_slug', $onlyWallet))
                    ->when($onlyLines, fn ($w) => $w->where(fn ($x) => array_map(fn ($id) => $x->orWhere('reference', 'like', 'LINE-' . $id . '-%'), $onlyLines)));
                foreach ($q->orderBy('id')->get() as $r) {
                    if ($r->bank_reference) { $done += (float) $r->amount; continue; }
                    $requests->recordPaidPayout($p, $r->wallet_slug ?: 'principal', (float) $r->amount, $day, $ref, $by, (int) $r->id);
                    $done += (float) $r->amount;
                    $log[] = "{$p->name}: request #{$r->id} KES " . number_format((float) $r->amount, 2) . " ({$r->wallet_slug}) paid {$day} · {$ref}";
                }
                // what the queued requests don't cover (earnings on a line that had closed before its share was posted)
                foreach ($onlyWallet ? [$onlyWallet] : ['interest', 'principal'] as $wallet) {
                    $held = (float) \App\Models\PartnerTransaction::where('partner_id', $p->id)->where('wallet_slug', $wallet)->sum('amount')
                        - (float) DB::table('partner_requests')->where('partner_id', $p->id)->where('wallet_slug', $wallet)->whereIn('status', ['pending', 'prepared'])->sum('amount');
                    $already = DB::table('partner_requests')->where('partner_id', $p->id)->where('wallet_slug', $wallet)->where('bank_reference', $ref)->exists();
                    if (! $already && $held > 0.005 && $done < $total - 0.005) {
                        $amount = round(min($held, $total - $done), 2);
                        $requests->recordPaidPayout($p, $wallet, $amount, $day, $ref, $by, null, "October payout ({$wallet})");
                        $done += $amount;
                        $log[] = "{$p->name}: KES " . number_format($amount, 2) . " ({$wallet}) paid {$day} · {$ref}";
                    }
                }
                if (abs($done - $total) > 0.005) {
                    $log[] = "!! {$p->name}: requests add up to KES " . number_format($done, 2) . ', the bank shows KES ' . number_format($total, 2);
                }
            }

            return $log;
        };

        if (! $this->option('apply')) {
            $out = $slices->preview(function () use ($run, $buosti, $obiri) {
                $log = $run();
                // show the end state as if the payouts were approved
                foreach (DB::table('partner_requests')->whereIn('partner_id', [8, 9])->where('status', 'prepared')->whereNotNull('bank_reference')->pluck('id') as $id) {
                    app(\App\Services\ApprovalActions::class)->run((object) ['action' => 'partner_withdrawal', 'subject_id' => $id, 'requested_by' => 0, 'payload' => '{}'],
                        User::role('super-admin')->orderBy('id')->first());
                }

                return ['log' => $log, 'balances' => $this->balances([$buosti, $obiri]), 'trial' => app(Ledger::class)->trialBalance()['total']];
            });
            foreach ($out['result']['log'] as $l) {
                $this->line('  ' . $l);
            }
            $this->newLine();
            $this->info('Journal rows it would post (and the payouts once approved):');
            $this->table(['Partner', 'Wallet', 'Type', 'Amount', 'Loan', 'Date', 'Reference'], collect($out['journal'])->map(fn ($r) => [$r->name, $r->wallet_slug, $r->type,
                number_format((float) $r->amount, 2), $r->loan_id ?: '—', substr((string) $r->transaction_date, 0, 10), $r->reference])->all());
            $this->info('Wallets after (should be 0 for both):');
            $this->table(['Partner', 'Principal', 'Interest', 'Broker fee'], $out['result']['balances']);
            $this->line('Trial balance after: ' . $out['result']['trial']);
            $this->warn('Dry run: nothing saved. Run with --apply to save.');

            return self::SUCCESS;
        }

        $log = DB::transaction($run);
        foreach ($log as $l) {
            $this->line('  ' . $l);
        }
        $this->info('Saved. The payouts wait on Second sign-offs: another person approves them, and they post on the day they were paid.');

        return self::SUCCESS;
    }

    private function balances(array $partners): array
    {
        return array_map(fn (Partner $p) => [$p->name] + array_map(fn ($w) => number_format((float) \App\Models\PartnerTransaction::where('partner_id', $p->id)
            ->where('wallet_slug', $w)->sum('amount'), 2), ['principal' => 'principal', 'interest' => 'interest', 'broker_fee' => 'broker_fee']), $partners);
    }
}
