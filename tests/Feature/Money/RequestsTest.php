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

    public function test_the_extension_modal_starts_the_new_cycle_on_the_last_due_date_or_a_day_staff_pick(): void
    {
        $this->loan529();   // cycle 2 ran 6 Oct -> 16 Oct; today is 17 Oct (one day late)
        $borrower = \App\Models\User::find(DB::table('loans')->where('id', 529)->value('user_id'));
        $borrower->assignRole('borrower');
        $this->actingAs($borrower)->post(route('account.extension.request', 529), ['reason' => 'Salary comes on the 28th', 'confirm' => '1']);
        $id = DB::table('borrower_requests')->value('id');

        $default = $this->actingAs($this->manager)->getJson(route('staff.requests.preview', $id))->assertOk()->json();
        $this->assertTrue($default['ok']);
        $this->assertSame('2026-10-16', $default['plan']['start_date'], 'from the last due date');
        $this->assertEqualsWithDelta(0, $default['plan']['penalty'], 0.01, 'no penalty day in between');

        $late = $this->actingAs($this->manager)->getJson(route('staff.requests.preview', ['id' => $id, 'date' => '2026-10-17']))->json();
        $this->assertSame('2026-10-17', $late['plan']['start_date']);
        $this->assertGreaterThan(0, $late['plan']['penalty'], 'a later day carries the penalty up to it');

        $this->assertFalse($this->actingAs($this->manager)->getJson(route('staff.requests.preview', ['id' => $id, 'date' => '2026-10-01']))->json('ok'), 'before the cycle started');

        $this->actingAs($this->manager)->post(route('staff.requests.borrower', $id), ['decision' => 'approve', 'date' => '2026-10-16'])->assertSessionHas('success');
        $this->assertSame('2026-10-16', \Illuminate\Support\Carbon::parse(DB::table('loan_cycles')->where('loan_id', 529)->where('cycle_number', 3)->value('start_date'))->toDateString());
    }
}
