<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 0.14 / decision 23: audit:prune deletes only rows past retention, and nothing on --dry-run.
 */
class AuditPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_rows_past_retention_are_deleted(): void
    {
        $old = now()->subYears(8);
        $closed = $this->loan('repaid', $old);
        $open = $this->loan('disbursed', $old);
        $active = User::factory()->create();
        $gone = User::factory()->create();
        $gone->delete();
        DB::table('users')->where('id', $gone->id)->update(['deleted_at' => $old]);

        $observed = DB::table('audit_logs')->pluck('id')->all(); // rows the AuditObserver wrote for the fixtures (today)
        $keep = [
            $this->audit('loans', $open->id, $old),               // loan still open
            $this->audit('loans', $closed->id, now()->subYears(6)), // younger than 7 years
            $this->audit('users', $active->id, $old),             // relationship still on
            $this->audit('roles', 1, now()->subYear()),           // access, 1 year
            $this->audit('something_else', 1, now()->subYears(20)), // not in the policy
        ];
        $drop = [
            $this->audit('loans', $closed->id, $old),
            $this->audit('partners', 3, $old),
            $this->audit('users', $gone->id, $old),
            $this->audit('roles', 1, now()->subYears(3)),
        ];

        $this->artisan('audit:prune', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(count($observed) + 9, DB::table('audit_logs')->count());

        $this->artisan('audit:prune')->assertSuccessful();
        $this->assertEqualsCanonicalizing([...$observed, ...$keep], DB::table('audit_logs')->pluck('id')->all());
        $this->assertSame(0, DB::table('audit_logs')->whereIn('id', $drop)->count());
    }

    private function audit(string $table, int $recordId, $at): int
    {
        return DB::table('audit_logs')->insertGetId([
            'table_name' => $table, 'record_id' => $recordId, 'action' => 'update', 'created_at' => $at,
        ]);
    }

    private function loan(string $status, $at): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);
        $id = DB::table('loans')->insertGetId([
            'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId, 'amount' => 5000,
            'borrow_date' => $at->toDateString(), 'status' => $status, 'broker_status' => 0, 'reason' => 'Retention fixture',
            'consent' => 1, 'cycle' => 1, 'created_at' => $at, 'updated_at' => $at,
        ]);

        return Loan::findOrFail($id);
    }
}
