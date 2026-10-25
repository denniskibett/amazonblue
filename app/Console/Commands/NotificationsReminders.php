<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\CustomerEvents;
use App\Services\Loans\CycleBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily at 09:00 Nairobi (Stage 4 Phase 6): due in 3 days and due today, and late reminders on the days in
 * lending.notifications.overdue_days (1, 3, 7, 14, 21, 30, 45, 60 by default), for loans that still owe on their latest
 * cycle. A late reminder is the branded email with the penalty breakdown (Messaging\CollectionNotices: what was due,
 * the penalty so far, what each further day adds); after 30 days it is worded as a final notice (owner, 25 Oct 2026).
 * NOTIFICATIONS_OVERDUE_EMAILS alone sends just the late emails. Never before 08:00 or after 20:00.
 */
class NotificationsReminders extends Command
{
    protected $signature = 'notifications:reminders {--dry-run}';

    protected $description = 'Send due-soon, due-today and overdue reminders to borrowers';

    public function handle(CustomerEvents $events): int
    {
        $all = CustomerEvents::enabled();
        $lateOnly = ! $all && (bool) config('lending.notifications.overdue_emails');
        // a dry run sends nothing, so it lists the late reminders even while the switches are off (owner, 26 Oct 2026)
        if (! $all && ! $lateOnly && $this->option('dry-run')) {
            $lateOnly = true;
            $this->line('Switches are off; showing who would get a late-payment email if NOTIFICATIONS_OVERDUE_EMAILS were on.');
        }
        if ((! $all && ! $lateOnly) || ! \App\Services\LoanCalculator::v2()) {
            $this->line('Off: needs NOTIFICATIONS_ENABLED (or NOTIFICATIONS_OVERDUE_EMAILS) and engine v2.');

            return self::SUCCESS;
        }
        $notices = app(\App\Services\Messaging\CollectionNotices::class);
        $lateDays = (array) config('lending.notifications.overdue_days', [1, 7]);
        $hour = (int) now('Africa/Nairobi')->format('G');
        if (($hour < 8 || $hour >= 20) && ! $this->option('dry-run')) {
            $this->line('Quiet hours (20:00-08:00 Nairobi): nothing sent.');

            return self::SUCCESS;
        }
        $today = now('Africa/Nairobi')->startOfDay();
        $paybill = (string) (config('lending.mpesa.paybill') ?: config('lending.mpesa.shortcode'));
        $sent = 0;
        $loans = Loan::whereIn('status', [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])->get();
        foreach ($loans as $loan) {
            $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
            if (! $cycle) {
                continue;
            }
            $b = CycleBalance::at($cycle, $today->toDateString());
            if ($b['final_outstanding'] <= 0) {
                continue;
            }
            $due = Carbon::parse($cycle->due_date)->startOfDay();
            $diff = (int) $today->diffInDays($due, false);
            $event = match (true) {
                $diff === 3 && $all => 'due_soon',
                $diff === 0 && $all => 'due_today',
                $diff < 0 && in_array(-$diff, $lateDays, true) => 'overdue',
                default => null,
            };
            if (! $event) {
                continue;
            }
            if ($event === 'overdue' && $this->sentRecently($loan->id)) {
                continue; // staff sent a notice from Messages in the last two days
            }
            $sent++;
            if ($this->option('dry-run')) {
                $this->line(sprintf('  loan %d: %s, %d day(s) late, owes KES %s', $loan->id, $event, max(0, -$diff), number_format($b['final_outstanding'], 2)));
                continue;
            }
            if ($event === 'overdue') {
                $this->sendLate($notices, $loan, $b, -$diff, $due, $lateOnly ? ['email'] : ['email', 'sms']);
            } else {
                $events->loan($event, $loan, ['owed' => $b['final_outstanding'], 'due' => $due->format('j M Y'), 'days' => -$diff, 'paybill' => $paybill]);
            }
        }
        $this->line($sent . ' reminder(s) ' . ($this->option('dry-run') ? 'to send' : 'sent') . '.');

        return self::SUCCESS;
    }

    /** The late reminder: the branded overdue (or, after 30 days, final) notice with the penalty breakdown. */
    private function sendLate(\App\Services\Messaging\CollectionNotices $notices, Loan $loan, array $b, int $daysLate, Carbon $due, array $channels): void
    {
        $user = $loan->user;
        if (! $user) {
            return;
        }
        $kind = $daysLate > 30 ? 'final_notice' : 'overdue';
        $penalty = \App\Support\PenaltySummary::from($b);
        try {
            $email = $notices->email($kind, $user, $loan, (float) $b['final_outstanding'], $daysLate, $due, $penalty);
            $sms = $notices->sms($kind, $loan, (float) $b['final_outstanding'], $daysLate, $due);
            $user->notify(new \App\Notifications\CollectionNotice($kind, $email, $sms, $channels, route('account.loan', $loan)));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function sentRecently(int $loanId): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('message_batch_items')
            && \Illuminate\Support\Facades\DB::table('message_batch_items')->where('loan_id', $loanId)->where('status', 'sent')
                ->where('sent_at', '>=', now()->subDays(2))->exists();
    }
}
