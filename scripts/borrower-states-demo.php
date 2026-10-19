<?php
// Extra demo borrowers for the borrower-journey walkthroughs (Stage 2), one per loan state. Clone only (loans_wp_s2).
// Run: DB_DATABASE=loans_wp_s2 php scripts/borrower-states-demo.php   (idempotent: skips logins that already have loans)
// Uses DemoSeed's own helpers (user, loan, repay) and the Loan model's state methods; no calculation code touched.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// demo data only: never on production or the live mirror
if (app()->environment('production') || \Illuminate\Support\Facades\DB::connection()->getDatabaseName() === 'loans_main') {
    fwrite(STDERR, "Refusing to run: demo scripts only run on a demo copy (e.g. DB_DATABASE=loans_wp_s2).\n");
    return;
}

use App\Models\Loan; use App\Models\User; use App\Services\LoanCalculator; use Illuminate\Support\Facades\DB;

$db = DB::connection()->getDatabaseName();
if ($db !== 'loans_wp_s2') { fwrite(STDERR, "refusing: $db\n"); exit(1); }

$seed = new App\Console\Commands\DemoSeed();
$call = fn (string $m, ...$a) => (fn () => $this->$m(...$a))->call($seed);
$calc = app(LoanCalculator::class);
$type = (int) config('lending.borrower_loan_type_id');
$manager = User::where('email', 'manager@demo.aimzone.test')->first();
$recoveryOfficer = User::where('email', 'recovery@demo.aimzone.test')->first();

$people = [
  'pending' => ['Faith Achieng', '0700900201'],
  'approved' => ['Kevin Kiprop', '0700900202'],
  'inrecovery' => ['Samuel Njoroge', '0700900203'],
  'plan' => ['Mercy Wambui', '0700900204'],
  'writtenoff' => ['Daniel Ouma', '0700900205'],
];
DB::transaction(function () use ($people, $call, $calc, $type, $manager, $recoveryOfficer) {
  foreach ($people as $key => [$name, $phone]) {
    $u = $call('user', $key, $name, 'borrower', $phone, true);
    if (Loan::withTrashed()->where('user_id', $u->id)->exists()) { echo "$key: has loans, skipped\n"; continue; }
    $disburse = function (Loan $loan, $date, $ref) {
      DB::table('disbursements')->insert(['loan_id' => $loan->id, 'loan_cycle_id' => $loan->cycles()->value('id'), 'amount' => $loan->amount, 'net_amount' => $loan->amount, 'transaction' => $ref, 'mode' => 'mpesa', 'disburse_date' => $date->toDateString(), 'payment_date' => $date->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
    };
    switch ($key) {
      case 'pending':
        $call('loan', $calc, $u, $type, 6000, now()->subHours(3), Loan::STATUS_PENDING);
        break;
      case 'approved':
        $call('loan', $calc, $u, $type, 7500, now()->subDay(), Loan::STATUS_APPROVED);
        break;
      case 'inrecovery':
        // an older repaid loan, then one that went to recovery
        $old = $call('loan', $calc, $u, $type, 5000, now()->subDays(90), Loan::STATUS_DISBURSED);
        $disburse($old, now()->subDays(90), 'QHA1RC0001');
        $call('repay', $old, 6000, now()->subDays(81), 'QHB2RC0002');
        $loan = $call('loan', $calc, $u, $type, 12000, now()->subDays(60), Loan::STATUS_DISBURSED);
        $disburse($loan, now()->subDays(60), 'QHC3RC0003');
        $call('repay', $loan, 2000, now()->subDays(45), 'QHD4RC0004');
        $loan->status = Loan::STATUS_OVERDUE; $loan->save();
        $loan->markAsDefaulted('Demo: unpaid 30+ days after due date');
        $loan->refresh(); $loan->startRecovery();
        $case = App\Models\DebtRecoveryCase::where('loan_id', $loan->id)->first();
        if ($case) { $case->update(['assigned_to' => $recoveryOfficer?->id, 'last_contact_date' => now()->subDays(3), 'next_action_date' => now()->addDays(4), 'status_id' => DB::table('recovery_statuses')->where('slug', 'negotiation')->value('id') ?? $case->status_id]); }
        break;
      case 'plan':
        $loan = $call('loan', $calc, $u, $type, 9000, now()->subDays(25), Loan::STATUS_DISBURSED);
        $disburse($loan, now()->subDays(25), 'QHE5PL0005');
        $call('repay', $loan, 1500, now()->subDays(12), 'QHF6PL0006');
        $loan->status = Loan::STATUS_OVERDUE; $loan->save();
        $loan->refresh()->startPaymentPlan(10.0, 21);
        $loan->refresh();
        if ($loan->status !== Loan::STATUS_FORBEARANCE) { $loan->grantForbearance(21, 'Demo payment plan'); }
        break;
      case 'writtenoff':
        $loan = $call('loan', $calc, $u, $type, 5000, now()->subDays(200), Loan::STATUS_DISBURSED);
        $disburse($loan, now()->subDays(200), 'QHG7WO0007');
        $call('repay', $loan, 1000, now()->subDays(185), 'QHH8WO0008');
        App\Models\Repayment::create(['loan_id' => $loan->id, 'loan_cycle_id' => $loan->cycles()->value('id'), 'amount' => 3000, 'repayment_date' => now()->subDays(150)->toDateString(), 'transaction' => 'ROLL OVER', 'mode' => 'rollover']);
        App\Models\Repayment::create(['loan_id' => $loan->id, 'loan_cycle_id' => $loan->cycles()->value('id'), 'amount' => 2000, 'repayment_date' => now()->subDays(120)->toDateString(), 'transaction' => 'WRITE OFF', 'mode' => 'write_off']);
        $loan->status = Loan::STATUS_WRITTEN_OFF ?? 'written_off'; $loan->save();
        break;
    }
    echo "$key: {$u->email} ok\n";
  }
});
