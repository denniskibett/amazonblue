<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\CustomerEvents;
use App\Services\Loans\CycleBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily at 09:00 Nairobi (Stage 4 Phase 6): due in 3 days, due today, and late on day 1 and day 7, for loans
 * that still owe on their latest cycle. Never before 08:00 or after 20:00, whoever runs it.
 */
class NotificationsReminders extends Command
{
    protected $signature = 'notifications:reminders {--dry-run}';

    protected $description = 'Send due-soon, due-today and overdue reminders to borrowers';

    public function handle(CustomerEvents $events): int
    {
        if (! CustomerEvents::enabled() || ! \App\Services\LoanCalculator::v2()) {
            $this->line('Off: needs NOTIFICATIONS_ENABLED and engine v2.');

            return self::SUCCESS;
        }
        $hour = (int) now('Africa/Nairobi')->format('G');
        if ($hour < 8 || $hour >= 20) {
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
                $diff === 3 => 'due_soon',
                $diff === 0 => 'due_today',
                $diff === -1 || $diff === -7 => 'overdue',
                default => null,
            };
            if (! $event) {
                continue;
            }
            $sent++;
            if (! $this->option('dry-run')) {
                $events->loan($event, $loan, ['owed' => $b['final_outstanding'], 'due' => $due->format('j M Y'), 'days' => -$diff, 'paybill' => $paybill]);
            }
        }
        $this->line($sent . ' reminder(s) ' . ($this->option('dry-run') ? 'to send' : 'sent') . '.');

        return self::SUCCESS;
    }
}
