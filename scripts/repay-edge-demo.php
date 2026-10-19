<?php
// Temporary edge-case borrowers for the repay walkthrough (loans_wp_s2 only): repay-repaid@, repay-recovery@,
// repay-pending@demo.aimzone.test (password Demo12345!). Loans are made like demo:seed does it.
//   DB_DATABASE=loans_wp_s2 php artisan tinker --execute="require 'scripts/repay-edge-demo.php';"
//   REMOVE=1 DB_DATABASE=loans_wp_s2 php artisan tinker --execute="require 'scripts/repay-edge-demo.php';"   (clean up)
// demo data only: never on production or the live mirror
if (app()->environment('production') || \Illuminate\Support\Facades\DB::connection()->getDatabaseName() === 'loans_main') {
    fwrite(STDERR, "Refusing to run: demo scripts only run on a demo copy (e.g. DB_DATABASE=loans_wp_s2).\n");
    return;
}
use App\Models\{User, Loan, Repayment, Borrower};
$db = DB::connection()->getDatabaseName();
if ($db !== 'loans_wp_s2') { echo "wrong db $db\n"; return; }
$emails = ['repay-repaid', 'repay-recovery', 'repay-pending'];
if (getenv('REMOVE')) {
    foreach ($emails as $k) {
        $u = User::withTrashed()->where('email', "$k@demo.aimzone.test")->first();
        if (! $u) continue;
        $ids = Loan::withTrashed()->where('user_id', $u->id)->pluck('id');
        Repayment::withTrashed()->whereIn('loan_id', $ids)->forceDelete();
        DB::table('loan_cycles')->whereIn('loan_id', $ids)->delete();
        DB::table('loan_risk_assessments')->whereIn('loan_id', $ids)->delete();
        Loan::withTrashed()->whereIn('id', $ids)->forceDelete();
        Borrower::where('user_id', $u->id)->delete();
        DB::table('model_has_roles')->where('model_id', $u->id)->where('model_type', User::class)->delete();
        DB::table('audit_logs')->where('user_id', $u->id)->delete();
        $u->forceDelete();
        echo "removed $k\n";
    }
    return;
}
$calc = app(App\Services\LoanCalculator::class);
$mk = function ($key, $name) {
    $u = User::withTrashed()->firstOrNew(['email' => "$key@demo.aimzone.test"]);
    $u->fill(['name' => $name, 'phone' => $u->phone ?: '07009' . rand(10000, 99999), 'password' => 'Demo12345!', 'status' => 1,
        'kyc_status' => 'approved', 'kyc_submitted_at' => now()->subDays(40), 'kyc_reviewed_at' => now()->subDays(39), 'id_type' => 'national_id', 'id_number' => (string) rand(31000000, 39000000)]);
    $u->email_verified_at = now(); $u->save();
    $u->syncRoles(['borrower']); $u->syncLegacyRole();
    Borrower::firstOrCreate(['user_id' => $u->id], ['client_type' => 'individual', 'status' => 1]);
    return $u;
};
$loan = function ($u, $amount, $days, $status) use ($calc) {
    $l = Loan::create(['user_id' => $u->id, 'loan_type_id' => (int) config('lending.borrower_loan_type_id'), 'amount' => $amount, 'original_amount' => $amount,
        'borrow_date' => now()->subDays($days)->toDateString(), 'status' => $status, 'broker_status' => 0, 'reason' => 'Repay walkthrough (temporary)', 'consent' => true, 'consent_date' => now()->subDays($days), 'cycle' => 1]);
    $calc->createInitialCycle($l);
    return $l->refresh();
};
$pay = function ($l, $amount, $days, $ref) {
    $c = $l->cycles()->where('status', 'active')->orderByDesc('cycle_number')->first();
    Repayment::create(['loan_id' => $l->id, 'loan_cycle_id' => $c?->id, 'amount' => $amount, 'repayment_date' => now()->subDays($days)->toDateString(), 'transaction' => $ref, 'mode' => 'mpesa']);
    if ($c && $c->new_balance - Repayment::where('loan_cycle_id', $c->id)->sum('amount') <= 0) $c->update(['status' => 'completed']);
    if (! $l->cycles()->where('status', 'active')->exists() || $l->repayments()->sum('amount') >= $l->amount) { $l->status = 'repaid'; $l->save(); }
};
$u = $mk('repay-repaid', 'Wanjiku Njeri');
if (! Loan::where('user_id', $u->id)->exists()) { $l = $loan($u, 6000, 9, 'disbursed'); $pay($l, 500, 6, 'BROKER FEE ADJ'); $pay($l, 7200, 3, 'QJP4H8RT2C'); }
$u = $mk('repay-recovery', 'Kevin Ochieng');
if (! Loan::where('user_id', $u->id)->exists()) { $l = $loan($u, 7000, 45, 'disbursed'); $l->status = 'recovery'; $l->save(); }
$u = $mk('repay-pending', 'Mercy Achieng');
if (! Loan::where('user_id', $u->id)->exists()) { $loan($u, 5000, 0, 'pending'); }
foreach ($emails as $k) { $u = User::where('email', "$k@demo.aimzone.test")->first(); echo $k, ' ', Loan::where('user_id', $u->id)->get(['id', 'status'])->toJson(), "\n"; }
