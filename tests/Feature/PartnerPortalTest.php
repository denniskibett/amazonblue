<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use App\Services\PartnerLedger;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * owner, 1 Nov 2026: partners could not see what was paid, what is to be paid, or pending loans;
 * borrower + partner users landed on the borrower home; some partners had no login.
 */
class PartnerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_borrower_partner_lands_on_partner_home_without_an_open_loan(): void
    {
        [$user] = $this->partnerUser(['borrower', 'partner']);

        $this->actingAs($user)->get('/account')->assertRedirect(route('account.partner.home'));
        // the switch's Borrowing link shows the borrower home and is remembered for the session
        $this->actingAs($user)->get('/account?as=borrower')->assertOk()->assertViewIs('home.account.borrower');
        $this->actingAs($user)->get('/account')->assertOk()->assertViewIs('home.account.borrower');
        // opening the partner home resets it
        $this->actingAs($user)->get('/account/partner')->assertOk()->assertSee('Nothing has been paid out to you yet');
        $this->actingAs($user)->get('/account')->assertRedirect(route('account.partner.home'));
    }

    public function test_borrower_partner_with_a_pending_application_lands_on_borrower_home(): void
    {
        [$user] = $this->partnerUser(['borrower', 'partner']);
        $this->loan(['user_id' => $user->id, 'status' => 'pending']);

        $this->actingAs($user)->get('/account')->assertOk()->assertViewIs('home.account.borrower');
    }

    public function test_borrower_with_partner_role_but_no_partner_row_stays_on_borrower_home(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['borrower', 'partner']);

        $this->actingAs($user)->get('/account')->assertOk()->assertViewIs('home.account.borrower');
    }

    public function test_home_shows_to_be_paid_paid_out_earnings_and_pending_from_the_journal(): void
    {
        [$user, $partner] = $this->partnerUser(['partner']);
        $ledger = app(PartnerLedger::class);
        $ledger->post($partner, 'broker_fee', 'broker_fee_earned', 8100, reference: 'T-BF', transactionDate: '2026-10-01');
        $ledger->post($partner, 'broker_fee', 'penalty_share', 7100, reference: 'T-PS', transactionDate: '2026-10-02');
        $ledger->post($partner, 'interest', 'profit_distribution', 2300, reference: 'T-PD', transactionDate: '2026-10-03');
        $ledger->post($partner, 'broker_fee', 'withdrawal', -4400, reference: 'T-WD', transactionDate: '2026-10-15');
        $this->loan(['partner_id' => $partner->id, 'status' => 'pending', 'amount' => 6543]);

        $this->actingAs($user)->get('/account/partner')->assertOk()
            ->assertSee('To be paid out')
            ->assertSee('10,800.00')   // broker fee wallet now: 8,100 + 7,100 − 4,400
            ->assertSee('13,100.00')   // total withdrawable: 10,800 + 2,300
            ->assertSee('Paid out')->assertSee('4,400.00')->assertSee('15 Oct 2026')
            ->assertSee('8,100.00')->assertSee('7,100.00')->assertSee('2,300.00')
            ->assertSee("Your capital hasn't been recorded as an investment yet", false)
            ->assertSee('Pending loans (not sent yet)');

        $this->actingAs($user)->get('/account/partner/loans?list=pending')->assertOk()
            ->assertSee('Pending · 1')->assertSee('6,543.00');
        // not-sent loans are not in the brokered list
        $this->actingAs($user)->get('/account/partner/loans?list=brokered')->assertOk()->assertDontSee('6,543.00');
        $this->actingAs($user)->get('/account/partner/investments')->assertOk()
            ->assertSee("Your capital hasn't been recorded as an investment yet");
    }

    public function test_a_partner_cannot_see_another_partners_loans(): void
    {
        [$user] = $this->partnerUser(['partner']);
        [, $other] = $this->partnerUser(['partner']);
        $theirs = $this->loan(['partner_id' => $other->id, 'status' => 'pending', 'amount' => 9876]);

        $this->actingAs($user)->get('/account/partner/loans?list=pending')->assertOk()->assertDontSee('9,876.00');
        $this->actingAs($user)->get("/account/partner/loans/{$theirs->id}")->assertNotFound();
    }

    public function test_staff_link_an_existing_user_and_a_double_link_is_refused(): void
    {
        $staff = $this->staff();
        $a = Partner::create(['name' => 'Imagine Nation', 'email' => 'in@example.test']);
        $b = Partner::create(['name' => 'Obiri', 'email' => 'ob@example.test']);
        $user = User::factory()->create(['email' => 'person@example.test']);

        $this->actingAs($staff)->post(route('staff.partners.login.store', $a), ['mode' => 'existing', 'lookup' => 'person@example.test'])
            ->assertSessionHasNoErrors();
        $this->assertSame($user->id, $a->fresh()->user_id);
        $this->assertTrue($user->fresh()->hasRole('partner'));

        $this->actingAs($staff)->post(route('staff.partners.login.store', $b), ['mode' => 'existing', 'lookup' => 'person@example.test'])
            ->assertSessionHasErrorsIn('login', 'login');
        $this->assertNull($b->fresh()->user_id);
    }

    public function test_staff_create_a_login_sends_the_set_password_email(): void
    {
        Notification::fake();
        $staff = $this->staff();
        $partner = Partner::create(['name' => 'New Partner', 'email' => 'np@example.test']);

        $this->actingAs($staff)->post(route('staff.partners.login.store', $partner), [
            'mode' => 'new', 'name' => 'New Partner', 'email' => 'np@example.test', 'phone' => '0711000222',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'np@example.test')->firstOrFail();
        $this->assertSame($user->id, $partner->fresh()->user_id);
        $this->assertTrue($user->hasRole('partner'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_link_login_needs_edit_partners(): void
    {
        $user = User::factory()->create();
        $user->assignRole('loan-officer');
        $partner = Partner::create(['name' => 'P', 'email' => 'p@example.test']);

        $this->actingAs($user)->post(route('staff.partners.login.store', $partner), ['mode' => 'existing', 'lookup' => 'x@example.test'])
            ->assertForbidden();
    }

    private function partnerUser(array $roles): array
    {
        $user = User::factory()->create();
        $user->assignRole($roles);
        $partner = Partner::create(['name' => 'Partner ' . $user->id, 'email' => 'p' . $user->id . '@example.test', 'user_id' => $user->id]);

        return [$user, $partner];
    }

    private function staff(): User
    {
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $staff->givePermissionTo('edit partners');

        return $staff;
    }

    private function loan(array $attributes): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);
        $amount = $attributes['amount'] ?? 5000;

        return Loan::create($attributes + [
            'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId, 'amount' => $amount, 'original_amount' => $amount,
            'borrow_date' => now()->toDateString(), 'status' => 'disbursed', 'broker_status' => 0,
            'reason' => 'School fees for the new term', 'consent' => true, 'consent_date' => now(), 'cycle' => 1,
        ]);
    }
}
