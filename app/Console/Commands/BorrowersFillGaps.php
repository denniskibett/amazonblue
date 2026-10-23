<?php

namespace App\Console\Commands;

use App\Models\Borrower;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Borrowers data check (owner, 23 Oct 2026).
 *  - Every borrower account should have a borrowers row. --apply creates the missing ones as plain individual
 *    borrowers (client_type individual, status active). Nothing a borrower entered is ever changed.
 *  - Lists who is missing income type, net income, employer or business, or pay schedule. Those are asked for in the
 *    borrower's account ("Complete your profile") and can be emailed from Messages; staff can also fill them in.
 * Dry-run by default: prints the plan and writes a CSV to storage/app/private/reports.
 */
class BorrowersFillGaps extends Command
{
    protected $signature = 'borrowers:fill-gaps {--apply : Create the missing borrowers rows}';

    protected $description = 'Find borrower accounts with no borrowers row or missing income/employer/pay details; --apply creates the missing rows';

    public function handle(): int
    {
        $users = User::role('borrower')->whereNull('deleted_at')->with('borrower')->orderBy('id')->get()
            ->reject(fn (User $u) => $u->isStaff());
        $noRow = $users->filter(fn (User $u) => ! $u->borrower);
        $rows = $users->map(function (User $u) {
            $missing = $u->borrower ? $u->borrower->missing() : ['borrowers row', 'income type', 'net income', 'employer or business', 'pay schedule'];

            return ['user_id' => $u->id, 'name' => $u->name, 'phone' => $u->phone, 'email' => $u->email,
                'kyc' => $u->kyc_status ?: 'not started', 'loans' => DB::table('loans')->where('user_id', $u->id)->whereNull('deleted_at')->count(),
                'missing' => implode('; ', $missing)];
        })->filter(fn ($r) => $r['missing'] !== '')->values();

        $dir = storage_path('app/private/reports');
        @mkdir($dir, 0775, true);
        $file = $dir . '/borrowers-gaps-' . now()->format('Ymd-Hi') . ($this->option('apply') ? '' : '-dry-run') . '.csv';
        $fh = fopen($file, 'w');
        fputcsv($fh, ['user_id', 'name', 'phone', 'email', 'kyc', 'loans', 'missing']);
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        fclose($fh);

        $this->info($users->count() . ' borrower accounts; ' . $noRow->count() . ' without a borrowers row; ' . $rows->count() . ' missing something.');
        foreach (['income type', 'net income', 'employer or business', 'pay schedule'] as $what) {
            $this->line('  missing ' . str_pad($what, 22) . $rows->filter(fn ($r) => str_contains($r['missing'], $what))->count());
        }
        $this->line('CSV: ' . $file);

        if (! $this->option('apply')) {
            $this->warn('Dry run: nothing changed. Run with --apply to create the ' . $noRow->count() . ' missing borrowers row(s).');

            return self::SUCCESS;
        }
        DB::transaction(function () use ($noRow) {
            foreach ($noRow as $u) {
                $b = Borrower::firstOrCreate(['user_id' => $u->id], ['client_type' => 'individual', 'status' => 1]);
                AuditTrail::record('borrowers', $b->id, 'create', null, ['user_id' => $u->id, 'event' => 'fill_gaps']);
            }
        });
        $this->info('Created ' . $noRow->count() . ' borrowers row(s). Missing details are asked for in each borrower\'s account.');

        return self::SUCCESS;
    }
}
