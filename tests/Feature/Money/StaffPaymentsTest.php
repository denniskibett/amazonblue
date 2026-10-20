<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Models\PaymentClaim;
use App\Models\Repayment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Stage 4 Phase 4: the staff M-Pesa payments queue and statement check. */
class StaffPaymentsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $teller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2']);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-21 10:00');
        $this->loanRow(903, 1, 5000, 5000, '2026-10-15', '2026-10-25', 'disbursed', 1, 30);
        $this->cycleRow(903, 903, 1, 0, 1000, 6000, 20, '2026-10-15', '2026-10-25', 'active', 'Initial');
        $this->teller = User::factory()->create();
        $this->teller->assignRole('teller');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_queue_shows_and_a_teller_assigns_an_unmatched_payment(): void
    {
        $t = MpesaTransaction::create(['type' => 'c2b', 'trans_id' => 'UJL1QUEUE1', 'amount' => 1200, 'bill_ref' => 'JANE903', 'status' => 'unmatched', 'note' => 'not a loan number']);
        $this->actingAs($this->teller)->get(route('staff.payments'))->assertOk()->assertSee('UJL1QUEUE1');
        $this->actingAs($this->teller)->post(route('staff.payments.assign', $t), ['loan_id' => 903])->assertRedirect()->assertSessionHas('success');

        $r = Repayment::firstOrFail();
        $this->assertSame([903, 'UJL1QUEUE1', $this->teller->id], [(int) $r->loan_id, $r->transaction, (int) $r->created_by]);
        $this->assertSame('matched', $t->fresh()->status);
        $this->actingAs($this->teller)->post(route('staff.payments.assign', $t), ['loan_id' => 903])->assertSessionHas('error');
        $this->assertSame(1, Repayment::count());
    }

    public function test_a_claim_not_found_tells_the_borrower_why(): void
    {
        $c = PaymentClaim::create(['user_id' => Loan::findOrFail(903)->user_id, 'loan_id' => 903, 'code' => 'UJL1NOPE01', 'amount' => 500, 'status' => 'checking']);
        $this->actingAs($this->teller)->post(route('staff.payments.claim', $c), ['decision' => 'match'])->assertSessionHas('error');
        $this->actingAs($this->teller)->post(route('staff.payments.claim', $c), ['decision' => 'reject', 'reason' => 'Not on our M-Pesa statement'])->assertSessionHas('success');
        $this->assertSame(['rejected', 'Not on our M-Pesa statement'], [$c->fresh()->status, $c->fresh()->reason]);
    }

    public function test_the_statement_check_finds_missing_entries_both_ways(): void
    {
        Repayment::create(['loan_id' => 903, 'loan_cycle_id' => 903, 'amount' => 1000, 'repayment_date' => '2026-10-20', 'transaction' => 'UJK1ONBOTH', 'kind' => 'cash', 'mode' => 'mpesa']);
        Repayment::create(['loan_id' => 903, 'loan_cycle_id' => 903, 'amount' => 700, 'repayment_date' => '2026-10-20', 'transaction' => 'UJK1OURSONL', 'kind' => 'cash', 'mode' => 'mpesa']);
        Repayment::create(['loan_id' => 903, 'loan_cycle_id' => 903, 'amount' => 300, 'repayment_date' => '2026-10-20', 'transaction' => 'UJK1DIFFER', 'kind' => 'cash', 'mode' => 'mpesa']);
        $csv = "Organisation Statement\nReceipt No.,Completion Time,Details,Paid In,Withdrawn\n"
            . "UJK1ONBOTH,2026-10-20 09:00:00,Pay Bill from 2547...,1000.00,\n"
            . "UJK1DIFFER,2026-10-20 10:00:00,Pay Bill,350.00,\n"
            . "UJK1THEIRS,2026-10-20 11:00:00,Pay Bill,999.00,\n";
        $file = UploadedFile::fake()->createWithContent('statement.csv', $csv);

        $this->actingAs($this->teller)->post(route('staff.payments.reconcile'), ['statement' => $file])->assertRedirect();
        $r = session('recon');
        $this->assertSame(1, $r['ok']);
        $this->assertSame('UJK1THEIRS', $r['on_statement_not_recorded'][0]['receipt']);
        $this->assertSame('UJK1DIFFER', $r['amount_differs'][0]['receipt']);
        $this->assertSame([], $r['recorded_not_on_statement'], 'UJK1OURSONL is 11 characters, not an M-Pesa receipt shape');
    }

    public function test_a_borrower_cannot_open_the_queue(): void
    {
        $b = User::factory()->create();
        $b->assignRole('borrower');
        $this->actingAs($b)->get(route('staff.payments'))->assertRedirect(); // staff pages send customers to their account
    }
}
