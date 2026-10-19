<?php

namespace App\Console\Commands;

use App\Models\Borrower;
use App\Models\Broker;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\Repayment;
use App\Models\User;
use App\Services\LoanCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Demo people and loans for Stage 2 walkthroughs (docs/stage2/README.md). Never on production
 * or loans_main. Idempotent: users are matched by email; loans are only made for demo borrowers
 * who have none (--fresh deletes the demo borrowers' loans, cycles and repayments first).
 * Loans are made the way staff make them (Loan::create + LoanCalculator::createInitialCycle),
 * repayments the way RepaymentController::store records them. No calculation code is touched.
 */
class DemoSeed extends Command
{
    protected $signature = 'demo:seed {--fresh : Delete and remake the demo borrowers\' loans}';

    protected $description = 'Create demo logins (@demo.aimzone.test) with loans for walkthroughs (never on production)';

    public const DOMAIN = 'demo.aimzone.test';

    public const PASSWORD = 'Demo12345!';

    public function handle(LoanCalculator $calculator): int
    {
        $db = DB::connection()->getDatabaseName();
        if (app()->environment('production') || $db === 'loans_main') {
            $this->error("Refusing to seed demo data (environment: " . app()->environment() . ", database: {$db}).");
            $this->line('Run it against a copy, e.g. DB_DATABASE=loans_wp_s2 php artisan demo:seed');

            return self::FAILURE;
        }

        $logins = [];
        DB::transaction(function () use ($calculator, &$logins) {
            $people = [
                // key => [name, role, what it shows]
                'active' => ['Amina Wanjiru', 'borrower', 'verified borrower, active loan with 2 repayments'],
                'overdue' => ['Brian Otieno', 'borrower', 'verified borrower, overdue loan'],
                'rejected' => ['Grace Muthoni', 'borrower', 'verified borrower, rejected application'],
                'new' => ['Peter Kamau', 'borrower', 'new borrower, KYC not started'],
                'partner' => ['Lake Capital Partners', 'partner', 'partner with a KES 250,000.00 contribution'],
                'broker' => ['Joseph Mwangi', 'broker', 'broker'],
                'admin' => ['Demo Admin', 'admin', 'staff: admin'],
                'teller' => ['Demo Teller', 'teller', 'staff: teller'],
                'manager' => ['Demo Manager', 'manager', 'staff: manager'],
                'recovery' => ['Demo Recovery', 'recovery-officer', 'staff: recovery officer'],
                'auditor' => ['Demo Auditor', 'auditor', 'staff: auditor'],
            ];

            $n = 0;
            $users = [];
            foreach ($people as $key => [$name, $role, $note]) {
                $n++;
                $users[$key] = $this->user($key, $name, $role, '0700' . str_pad((string) (900100 + $n), 6, '0', STR_PAD_LEFT), $key !== 'new' && $role === 'borrower');
                $logins[] = [$role, $users[$key]->email, self::PASSWORD, $note];
            }

            if ($this->option('fresh')) {
                $this->wipeLoans(collect($users)->only(['active', 'overdue', 'rejected', 'new'])->pluck('id')->all());
            }

            $type = (int) config('lending.borrower_loan_type_id');

            // active: disbursed 6 days ago (due in a few days on the self-service product), two part payments (well short of the amount, so it stays open)
            if (! Loan::withTrashed()->where('user_id', $users['active']->id)->exists()) {
                $loan = $this->loan($calculator, $users['active'], $type, 10000, now()->subDays(6), Loan::STATUS_DISBURSED);
                $this->repay($loan, 3000, now()->subDays(4), 'QJK8X2LM4P');
                $this->repay($loan, 2000, now()->subDays(1), 'QJL1C7TT9A');
            }

            // overdue: borrowed 20 days ago on the self-service product, past its due date, nothing paid
            if (! Loan::withTrashed()->where('user_id', $users['overdue']->id)->exists()) {
                $loan = $this->loan($calculator, $users['overdue'], $type, 8000, now()->subDays(20), Loan::STATUS_DISBURSED);
                $loan->status = Loan::STATUS_OVERDUE;
                $loan->save();
            }

            // rejected: applied 3 days ago, rejected the way LoanController::reject does it
            if (! Loan::withTrashed()->where('user_id', $users['rejected']->id)->exists()) {
                $loan = $this->loan($calculator, $users['rejected'], $type, 15000, now()->subDays(3), Loan::STATUS_PENDING);
                $loan->status = Loan::STATUS_REJECTED;
                $loan->recovery_notes = 'Rejected by ' . $users['manager']->name . ' on ' . now()->subDays(2)->format('Y-m-d H:i') . ': Income could not be confirmed with the employer.';
                $loan->save();
            }

            // partner: Partner row linked to the user, one contribution through the partner journal
            $partner = Partner::firstOrCreate(['user_id' => $users['partner']->id], [
                'name' => $users['partner']->name,
                'email' => $users['partner']->email,
                'phone' => $users['partner']->phone,
                'type' => 'business',
                'status' => 'active',
                'profit_share_rate' => 0,
                'max_loan_to_value' => 75,
                'risk_tolerance' => 'moderate',
                'notes' => 'Demo partner (demo:seed).',
            ]);
            if (! $partner->ledgerEntries()->exists()) {
                $partner->addContribution(250000, 'DEMO-CONTRIB-1', 'Demo contribution');
            }

            // broker profile
            Broker::firstOrCreate(['user_id' => $users['broker']->id], [
                'interest_client' => 20, 'interest_broker' => 40, 'penalty_client' => 50, 'penalty_broker' => 50, 'cert_no' => 'DEMO-01',
            ]);

            // business KYC: Amina's company, submitted and waiting in Business reviews
            $this->demoBusiness($users['active']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Demo data ready in {$db}. Password for every login: " . self::PASSWORD);
        $this->table(['role', 'email', 'password', 'shows'], $logins);

        return self::SUCCESS;
    }

    private function user(string $key, string $name, string $role, string $phone, bool $verified): User
    {
        $user = User::withTrashed()->firstOrNew(['email' => "{$key}@" . self::DOMAIN]);
        $user->fill([
            'name' => $name,
            'phone' => $user->phone ?: $phone,
            'password' => self::PASSWORD,
            'status' => 1,
        ]);
        if ($verified) {
            $user->fill([
                'gender' => $user->gender ?: 'female',
                'dob' => $user->dob ?: '1992-04-12',
                'id_type' => 'national_id',
                'id_number' => $user->id_number ?: (string) (30000000 + crc32($key) % 9000000),
                'kyc_status' => 'approved',
                'kyc_submitted_at' => $user->kyc_submitted_at ?: now()->subDays(20),
                'kyc_reviewed_at' => $user->kyc_reviewed_at ?: now()->subDays(19),
            ]);
        }
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->deleted_at = null;
        $user->save();

        $user->syncRoles([$role]);
        $user->syncLegacyRole();

        if ($role === 'borrower') {
            Borrower::firstOrCreate(['user_id' => $user->id], ['client_type' => 'individual', 'status' => 1]);
        }

        return $user;
    }

    /** As LoanController::store: create the loan, then its first cycle. */
    private function loan(LoanCalculator $calculator, User $user, int $type, float $amount, Carbon $borrowed, string $status): Loan
    {
        $loan = Loan::create([
            'user_id' => $user->id,
            'loan_type_id' => $type,
            'amount' => $amount,
            'original_amount' => $amount,
            'borrow_date' => $borrowed->toDateString(),
            'status' => $status,
            'broker_status' => 0,
            'reason' => 'Demo loan for walkthroughs (demo:seed).',
            'consent' => true,
            'consent_date' => $borrowed,
            'cycle' => 1,
        ]);
        $calculator->createInitialCycle($loan);

        return $loan->refresh();
    }

    /** As RepaymentController::store: record the payment, close the cycle / loan only when the rules say so. */
    private function repay(Loan $loan, float $amount, Carbon $date, string $reference): void
    {
        $cycle = $loan->cycles()->where('status', 'active')->orderByDesc('cycle_number')->first();
        Repayment::create([
            'loan_id' => $loan->id,
            'loan_cycle_id' => $cycle?->id,
            'amount' => $amount,
            'repayment_date' => $date->toDateString(),
            'transaction' => $reference,
            'mode' => 'mpesa',
        ]);

        if ($cycle && $cycle->new_balance - Repayment::where('loan_cycle_id', $cycle->id)->sum('amount') <= 0) {
            $cycle->update(['status' => 'completed']);
        }
        if (! $loan->cycles()->where('status', 'active')->exists() || $loan->repayments()->sum('amount') >= $loan->amount) {
            $loan->status = Loan::STATUS_REPAID;
            $loan->save();
        }
    }

    private function wipeLoans(array $userIds): void
    {
        $loanIds = Loan::withTrashed()->whereIn('user_id', $userIds)->pluck('id');
        Repayment::withTrashed()->whereIn('loan_id', $loanIds)->forceDelete();
        LoanCycle::whereIn('loan_id', $loanIds)->delete();
        Loan::withTrashed()->whereIn('id', $loanIds)->forceDelete();
        $this->line('Removed ' . $loanIds->count() . ' demo loan(s).');
    }

    /** One submitted business for the "active" borrower, with three people and the required documents (tiny PDFs). */
    private function demoBusiness(User $owner): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('businesses')) {
            return;
        }
        $business = \App\Models\Business::withTrashed()->firstOrCreate(
            ['owner_user_id' => $owner->id, 'registration_number' => 'PVT-DEMO2026'],
            [
                'legal_name' => 'Rift Agro Traders Ltd', 'trading_name' => 'Rift Agro', 'registration_type' => 'limited_company',
                'kra_pin' => 'P051234567X', 'industry' => 'Agriculture & commodities', 'date_registered' => '2019-03-14',
                'address_line_1' => 'Kiptagich House, Uganda Road', 'city' => 'Eldoret', 'county' => 'Uasin Gishu', 'country' => 'Kenya',
                'phone' => '0700900200', 'email' => 'accounts@riftagro.demo', 'monthly_revenue' => 450000, 'employees' => 12,
                'bank_name' => 'Equity Bank', 'bank_account' => '0180299123456',
                'status' => 'submitted', 'submitted_at' => now()->subDay(),
            ]
        );
        if (! $business->members()->exists()) {
            $business->members()->createMany([
                ['user_id' => $owner->id, 'full_name' => $owner->name, 'role' => 'director', 'id_number' => $owner->id_number, 'phone' => $owner->phone, 'email' => $owner->email, 'ownership_pct' => 60, 'is_signatory' => true, 'verified_at' => now()],
                ['full_name' => 'Peter Langat', 'role' => 'director', 'id_number' => '27654321', 'phone' => '0711000222', 'ownership_pct' => 30, 'is_signatory' => true],
                ['full_name' => 'Grace Chebet', 'role' => 'shareholder', 'id_number' => '29876543', 'ownership_pct' => 10],
            ]);
        }
        if (! $business->documents()->exists()) {
            foreach (['certificate', 'cr12', 'kra_pin_certificate'] as $type) {
                $label = \App\Models\BusinessDocument::TYPES[$type][0];
                $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj "
                    . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 300 120]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj "
                    . "4 0 obj<</Length 60>>stream\nBT /F1 12 Tf 20 60 Td (Demo: {$label}) Tj ET\nendstream endobj "
                    . "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
                $path = \App\Support\PrivateFiles::putNew('business_documents', $owner, 'pdf', $pdf);
                $business->documents()->create(['type' => $type, 'path' => $path, 'original_name' => $type . '-demo.pdf', 'mime' => 'application/pdf', 'size' => strlen($pdf), 'uploaded_by' => $owner->id]);
            }
        }
    }
}
