<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ApprovalActions;
use App\Services\AuditTrail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Partner payouts already made (a day paid and a bank reference) need no second sign-off (owner, 4 Nov 2026). This
 * posts the ones still waiting on /staff/sign-offs, each on the day it was paid, and closes their sign-off.
 */
class PartnersPostPaid extends Command
{
    protected $signature = 'partners:post-paid {--apply : Post them} {--by= : User id (default: the first super-admin)}';

    protected $description = 'Post partner payouts already made that still wait on a second sign-off';

    public function handle(ApprovalActions $actions): int
    {
        $by = $this->option('by') ? User::findOrFail($this->option('by')) : User::role('super-admin')->orderBy('id')->firstOrFail();
        $waiting = DB::table('approvals as a')->join('partner_requests as r', 'r.id', '=', 'a.subject_id')->join('partners as p', 'p.id', '=', 'r.partner_id')
            ->where('a.action', 'partner_withdrawal')->where('a.subject_type', 'partner_requests')->where('a.status', 'pending')
            ->whereNotNull('r.paid_on')->whereNotNull('r.bank_reference')->orderBy('r.paid_on')->orderBy('r.id')
            ->get(['a.*', 'r.id as request_id', 'r.amount as paid', 'r.wallet_slug', 'r.paid_on', 'r.bank_reference', 'p.name as partner']);
        $this->table(['Sign-off', 'Request', 'Partner', 'Wallet', 'Amount', 'Paid on', 'Reference'], $waiting->map(fn ($a) => [$a->id, $a->request_id, $a->partner,
            $a->wallet_slug, number_format((float) $a->paid, 2), substr((string) $a->paid_on, 0, 10), $a->bank_reference])->all());
        if (! $this->option('apply')) {
            $this->warn('Dry run: nothing posted. Run with --apply.');

            return self::SUCCESS;
        }
        foreach ($waiting as $a) {
            DB::transaction(function () use ($a, $actions, $by) {
                $actions->run($a, $by);
                DB::table('approvals')->where('id', $a->id)->update(['status' => 'approved', 'decided_by' => $by->id, 'decided_at' => now(),
                    'reason' => 'Already paid ' . substr((string) $a->paid_on, 0, 10) . ' · ' . $a->bank_reference . ': no second sign-off', 'updated_at' => now()]);
                AuditTrail::record('approvals', $a->id, 'update', ['status' => 'pending'], ['status' => 'approved', 'decided_by' => $by->id, 'reason' => 'already paid']);
            });
        }
        $this->info('Posted ' . $waiting->count() . ' payout(s).');

        return self::SUCCESS;
    }
}
