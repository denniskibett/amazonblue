<?php

namespace Tests\Feature\Money;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use App\Services\Partners\PartnerLines;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** "Make partner" from a user, and broker/funding changes from the disbursement edit modal (owner, 23 Oct 2026). */
class MakePartnerAndEditFundingTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-23 10:00');
        config(['lending.engine' => 'v2', 'lending.split.enabled' => true, 'lending.ledger.enabled' => false, 'lending.approvals.enabled' => false]);
        $this->seed(RoleSeeder::class);
        $this->loanTypes();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo(['create disbursements', 'edit disbursements', 'create partners', 'view partners', 'edit partners', 'create repayments']);
        Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_partner_form_opens_filled_in_from_the_user_and_can_rename_the_login(): void
    {
        $u = User::factory()->create(['name' => 'BUOSTI AGENCY', 'email' => 'stephine@example.test', 'phone' => '0726000000']);
        $u->assignRole('borrower');

        $this->actingAs($this->admin)->get(route('partners.index', ['from_user' => $u->id, 'name' => 'BUOSTI AGENCIES', 'person' => 'Stephine Buluma']))
            ->assertOk()->assertSee('BUOSTI AGENCIES')->assertSee('Stephine Buluma')->assertSee('stephine@example.test');

        $this->actingAs($this->admin)->postJson(route('partners.store'), [
            'user_id' => $u->id, 'name' => 'BUOSTI AGENCIES', 'email' => 'stephine@example.test', 'phone' => '0726000000', 'company_name' => 'BUOSTI AGENCIES',
            'type' => 'business', 'status' => 'active', 'risk_tolerance' => 'moderate', 'person_name' => 'Stephine Buluma',
        ])->assertSuccessful();

        $this->assertSame('BUOSTI AGENCIES', Partner::where('user_id', $u->id)->value('name'));
        $this->assertSame('Stephine Buluma', $u->fresh()->name);
        $this->assertTrue($u->fresh()->hasRole('partner'));
    }

    public function test_the_edit_modal_moves_broker_and_funding_before_payments_and_points_to_the_preview_after(): void
    {
        $broker = Partner::create(['name' => 'ISIRO', 'email' => 'isiro@example.test', 'type' => 'business', 'account_type' => 'principal', 'broker_rate' => 40]);
        $funder = Partner::create(['name' => 'IMAGINE', 'email' => 'imagine@example.test', 'type' => 'corporate', 'account_type' => 'principal', 'profit_share_rate' => 40]);
        $line = app(PartnerLines::class)->open($funder, 20000, 'short_term', null, 'EDITLINE01', '2026-10-20');

        $this->loanRow(951, 1, 10000, 10000, '2026-10-23', '2026-11-02', 'approved', 1, 30);
        $this->cycleRow(951, 951, 1, 0, 2000, 12000, 20, '2026-10-23', '2026-11-02', 'active', 'Initial');
        $this->actingAs($this->admin)->postJson(route('disbursements.store'), ['loan_id' => 951, 'amount' => 10000, 'disburse_date' => '2026-10-23',
            'transaction' => 'QD951', 'mode' => 'mpesa', 'broker_partner_id' => 0])->assertCreated();
        $d = Disbursement::where('loan_id', 951)->firstOrFail();

        $edit = fn (array $extra) => $this->actingAs($this->admin)->putJson(route('disbursements.update', $d), $extra + ['amount' => 10000, 'disburse_date' => '2026-10-23',
            'transaction' => 'QD951', 'mode' => 'mpesa']);
        $edit(['broker_partner_id' => $broker->id, 'funding_mode' => 'line', 'investment_id' => $line->id])->assertOk();
        $this->assertSame([1, $broker->id], [(int) Loan::find(951)->broker_status, (int) Loan::find(951)->partner_id]);
        $this->assertSame($line->id, (int) $d->fresh()->investment_id);
        $this->assertSame(10000.0, (float) $line->fundings()->value('amount_disbursed'));

        app(\App\Services\RepaymentService::class)->createRepayment(Loan::find(951), ['amount' => 1000, 'repayment_date' => '2026-10-23', 'transaction' => 'QK951', 'mode' => 'mpesa']);
        $edit(['broker_partner_id' => 0, 'funding_mode' => 'line', 'investment_id' => $line->id])->assertStatus(422)->assertJsonValidationErrors('broker_partner_id');
    }
}
