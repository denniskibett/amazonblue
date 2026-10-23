<?php

namespace Tests\Feature\Money;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\User;
use App\Support\MpesaSms;
use App\Support\PaymentModes;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Bank ↔ M-Pesa keeps both references; the same money can't go on a loan twice (owner, 23 Oct 2026). */
class PaymentRefsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-23 10:00');
        config(['lending.engine' => 'v2', 'lending.approvals.enabled' => false]);
        $this->seed(RoleSeeder::class);
        $this->loanTypes();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo(['create disbursements', 'create repayments']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function approved(int $id): Loan
    {
        $this->loanRow($id, 1, 10000, 10000, '2026-10-23', '2026-11-02', 'approved', 1, 30);
        $this->cycleRow($id, $id, 1, 0, 2000, 12000, 20, '2026-10-23', '2026-11-02', 'active', 'Initial');

        return Loan::findOrFail($id);
    }

    public function test_a_bank_to_mpesa_message_gives_both_references(): void
    {
        $m = MpesaSms::parse('Bank to M-PESA transfer of KES 5,000.00 to 254712345678 - JOHN DOE successfully processed. Transaction Ref ID: 5690NMQS2509. M-PESA Ref ID: UJH6OA6DRB');
        $this->assertSame(['bank_to_mpesa', '5690NMQS2509', 'UJH6OA6DRB', 5000.0], [$m['mode'], $m['bank_ref'], $m['mpesa_ref'], $m['amount']]);
        $this->assertSame('pesalink', MpesaSms::parse('Pesalink transfer of KES 25,000.00 to JANE A/c 0123 on 23/10/2026 14:05 processed successfully. Transaction Ref ID: 276917479797')['mode']);
        $this->assertSame(['mpesa', 'bank', null], [PaymentModes::shape('UJH6OA6DRB'), PaymentModes::shape('5690NMQS2509'), PaymentModes::shape('ROLLOVER 2')]);
    }

    public function test_a_bank_to_mpesa_disbursement_needs_and_keeps_both_references(): void
    {
        $loan = $this->approved(901);
        $base = ['loan_id' => 901, 'amount' => 10000, 'disburse_date' => '2026-10-23', 'mode' => 'bank_to_mpesa'];
        $this->actingAs($this->admin)->postJson(route('disbursements.store'), $base + ['bank_ref' => '5690NMQS2509'])
            ->assertStatus(422)->assertJsonValidationErrors('mpesa_ref');
        $this->actingAs($this->admin)->postJson(route('disbursements.store'), $base + ['bank_ref' => '5690NMQS2509', 'mpesa_ref' => 'ujh6oa6drb'])->assertCreated();

        $d = Disbursement::where('loan_id', 901)->firstOrFail();
        $this->assertSame(['5690NMQS2509', '5690NMQS2509', 'UJH6OA6DRB', 'bank_to_mpesa'], [$d->transaction, $d->bank_ref, $d->mpesa_ref, $d->mode]);
    }

    public function test_the_same_money_is_refused_twice_on_a_loan_and_an_mpesa_code_fills_mpesa_ref(): void
    {
        $this->loanRow(902, 1, 10000, 10000, '2026-10-20', '2026-10-30', 'disbursed', 1, 30);
        $this->cycleRow(902, 902, 1, 0, 2000, 12000, 20, '2026-10-20', '2026-10-30', 'active', 'Initial');
        $pay = fn (array $extra) => $this->actingAs($this->admin)->postJson(route('repayments.store'), $extra + ['loan_id' => 902, 'loan_cycle_id' => 902, 'amount' => 1000, 'repayment_date' => '2026-10-23']);

        $pay(['transaction' => 'TJN4H7K2LM', 'mode' => 'mpesa'])->assertOk();
        $this->assertSame('TJN4H7K2LM', Repayment::where('loan_id', 902)->value('mpesa_ref'));
        // the same M-Pesa code again, even entered as the M-Pesa side of a bank transfer
        $pay(['mode' => 'mpesa_to_bank', 'mpesa_ref' => 'TJN4H7K2LM', 'bank_ref' => 'FT26AB12345'])->assertStatus(422)->assertJsonValidationErrors('transaction');
        $this->assertSame(1, Repayment::where('loan_id', 902)->count());
    }

    public function test_past_references_are_filled_with_a_dry_run_first(): void
    {
        $this->loanRow(903, 1, 10000, 10000, '2026-10-01', '2026-10-11', 'repaid', 1, 30);
        DB::table('disbursements')->insert(['loan_id' => 903, 'amount' => 10000, 'net_amount' => 10000, 'transaction' => '5690NMQS2509', 'kind' => 'cash',
            'mode' => 'Mpesa', 'disburse_date' => '2026-10-01', 'payment_date' => '2026-10-01']);

        $this->artisan('payments:refs')->assertSuccessful();
        $this->assertNull(DB::table('disbursements')->where('loan_id', 903)->value('bank_ref'));

        $this->artisan('payments:refs', ['--apply' => true])->assertSuccessful();
        $row = DB::table('disbursements')->where('loan_id', 903)->first();
        $this->assertSame(['5690NMQS2509', null, 'bank_to_mpesa'], [$row->bank_ref, $row->mpesa_ref, $row->mode]);
    }
}
