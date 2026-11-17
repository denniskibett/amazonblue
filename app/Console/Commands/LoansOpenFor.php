<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\User;
use App\Services\LoanCalculator;
use App\Services\Partners\PartnerSlices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Opens an approved loan for a borrower, recorded after the money went out (owner, 17 Nov 2026: IMAGINE-NATION's
 * 3,230 from Yvonne's line #14, 3 months at 2% a month). Creates the borrower when the email is new (no password:
 * they reset it to sign in). Its payouts are then recorded with loans:add-payout. Dry run unless --apply; a borrower
 * who already has a loan of the same amount on the same day is refused.
 *
 *   php artisan loans:open-for imaginenationke@gmail.com --name="IMAGINE-NATION AGENCY LIMITED" --phone=+254717492048 --type=15 --amount=3230 --date=2026-11-14 --reason="..." [--apply]
 */
class LoansOpenFor extends Command
{
    protected $signature = 'loans:open-for {email}
        {--name= : the borrower\'s name, for a new borrower}
        {--phone= : the borrower\'s phone, for a new borrower}
        {--type= : loan product id}
        {--amount= : the loan amount}
        {--date= : the borrow day (Y-m-d)}
        {--reason= : why, at least 10 characters}
        {--by=1 : the staff user recording it}
        {--apply : save it (otherwise worked out and rolled back)}';

    protected $description = 'Open an approved loan for a borrower, creating the borrower if new (dry run unless --apply)';

    public function handle(LoanCalculator $calc, PartnerSlices $slices): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $type = LoanType::find($this->option('type'));
        $amount = round((float) $this->option('amount'), 2);
        $date = (string) $this->option('date');
        $reason = trim((string) $this->option('reason'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $type || $amount <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strlen($reason) < 10) {
            $this->error('Give an email, --type (a loan product), --amount above 0, --date=Y-m-d and a --reason of 10+ characters.');

            return self::FAILURE;
        }
        $user = User::where('email', $email)->first();
        if (! $user && (! $this->option('name') || ! $this->option('phone'))) {
            $this->error("No user with {$email}: give --name and --phone to create the borrower.");

            return self::FAILURE;
        }
        if ($user && Loan::where('user_id', $user->id)->where('amount', $amount)->whereDate('borrow_date', $date)->exists()) {
            $this->warn("{$user->name} already has a KES " . number_format($amount, 2) . " loan on {$date}: nothing to do.");

            return self::SUCCESS;
        }

        $work = function () use ($calc, $email, $user, $type, $amount, $date, $reason) {
            $new = ! $user;
            $user ??= User::create(['name' => (string) $this->option('name'), 'email' => $email, 'phone' => (string) $this->option('phone'),
                'password' => Hash::make(Str::random(40))]);
            if ($new && ! $user->hasRole('borrower')) {
                $user->assignRole('borrower');
            }
            $loan = Loan::create(['user_id' => $user->id, 'loan_type_id' => $type->id, 'amount' => $amount, 'original_amount' => $amount, 'borrow_date' => $date,
                'status' => 'approved', 'reason' => $reason, 'consent' => true, 'consent_date' => now(), 'loan_officer_id' => User::whereKey((int) $this->option('by'))->exists() ? (int) $this->option('by') : null, 'cycle' => 1]);
            $cycle = $calc->createInitialCycle($loan);
            \App\Services\AuditTrail::record('loans', $loan->id, 'create', null, ['event' => 'loans:open-for', 'amount' => $amount, 'type' => $type->id, 'by' => (int) $this->option('by')]);

            return ['user' => $user->id . ' ' . $user->name . ($new ? ' (new borrower)' : ''), 'loan' => $loan->id, 'interest' => (float) $cycle->interest_capitalized,
                'owed' => (float) $cycle->new_balance, 'due' => substr((string) $cycle->due_date, 0, 10)];
        };

        $this->line("{$email} · {$type->name} · KES " . number_format($amount, 2) . " from {$date}");
        $r = $this->option('apply') ? \Illuminate\Support\Facades\DB::transaction($work) : $slices->preview($work)['result'];
        $this->line("Borrower #{$r['user']} · loan #{$r['loan']} (approved) · interest " . number_format($r['interest'], 2) . ' · owed ' . number_format($r['owed'], 2) . " · due {$r['due']}");
        $this->option('apply') ? $this->info('Saved. Record its payouts with loans:add-payout ' . $r['loan'] . ' ...') : $this->warn('Dry run: nothing saved. Add --apply to save.');

        return self::SUCCESS;
    }
}
