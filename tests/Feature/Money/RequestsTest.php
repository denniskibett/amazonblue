<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Stage 4 Phase 5: stored borrower and partner requests with staff decisions. */
class RequestsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.requests.enabled' => true]);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-17 10:00');
        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_an_approved_extension_books_the_rollover_and_the_borrower_sees_it(): void
    {
        $this->loan529();
        $borrower = User::findOrFail(Loan::findOrFail(529)->user_id);
        $borrower->assignRole('borrower');
        $this->actingAs($borrower)->post(route('account.extension.request', 529), ['reason' => 'Salary comes on the 28th', 'confirm' => '1'])->assertRedirect();
        $id = DB::table('borrower_requests')->value('id');

        $this->actingAs($this->manager)->post(route('staff.requests.borrower', $id), ['decision' => 'approve'])->assertSessionHas('success');
        $this->assertSame('approved', DB::table('borrower_requests')->value('status'));
        $this->assertSame(3, (int) LoanCycle::where('loan_id', 529)->max('cycle_number'));
        $this->actingAs($borrower)->get(route('account.extension', 529))->assertOk()->assertSee('Extension agreed');
    }

    public function test_a_declined_extension_shows_the_note(): void
    {
        $this->loan529();
        $borrower = User::findOrFail(Loan::findOrFail(529)->user_id);
        $borrower->assignRole('borrower');
        $this->actingAs($borrower)->post(route('account.extension.request', 529), ['reason' => 'Need time', 'confirm' => '1']);
        $this->actingAs($this->manager)->post(route('staff.requests.borrower', DB::table('borrower_requests')->value('id')), ['decision' => 'decline', 'note' => 'Please clear the penalty first'])->assertSessionHas('success');
        $this->actingAs($borrower)->get(route('account.extension', 529))->assertSee('Please clear the penalty first');
    }

    public function test_partner_funds_confirmed_add_capital_and_a_withdrawal_needs_two_people(): void
    {
        config(['lending.approvals.enabled' => true]);
        $partner = Partner::create(['name' => 'IMAGINE-NATION', 'email' => 'imagine@example.test', 'type' => 'corporate', 'account_type' => 'principal']);
        $req = app(\App\Services\Requests::class);
        $fund = $req->partner('funding', $partner, 100000, null, 'QBANK123', null, null);
        $this->actingAs($this->manager)->post(route('staff.requests.partner', $fund), ['decision' => 'yes'])->assertSessionHas('success');
        $this->assertEqualsWithDelta(100000, (float) $partner->getWallet('principal')->refresh()->balanceFloat, 0.001);

        $out = $req->partner('withdrawal', $partner, 30000, 'principal', null, null, null);
        $this->actingAs($this->manager)->post(route('staff.requests.partner', $out), ['decision' => 'yes'])->assertSessionHas('success');
        $this->assertSame('prepared', DB::table('partner_requests')->where('id', $out)->value('status'));
        $this->assertEqualsWithDelta(100000, (float) $partner->getWallet('principal')->refresh()->balanceFloat, 0.001, 'not paid yet');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('staff.sign-offs.decide', DB::table('approvals')->value('id')), ['decision' => 'approve'])->assertSessionHas('success');
        $this->assertSame('done', DB::table('partner_requests')->where('id', $out)->value('status'));
        $this->assertEqualsWithDelta(70000, (float) $partner->getWallet('principal')->refresh()->balanceFloat, 0.001);
    }
}
