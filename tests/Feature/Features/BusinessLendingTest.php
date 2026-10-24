<?php

namespace Tests\Feature\Features;

use App\Models\Business;
use App\Models\BusinessDocument;
use App\Models\BusinessMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\User;
use App\Services\Approvals;
use App\Services\Aml\SanctionsList;
use App\Services\Aml\Screening;
use App\Services\Business\BusinessApproval;
use App\Services\Business\BusinessLending;
use App\Services\Business\DirectorVerification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Business loans (owner, 23 Oct 2026; plan defaults): approval rules, director links, the business limit, applying,
 * change requests, AML screening and the second sign-off.
 */
class BusinessLendingTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $checker;

    private User $owner;

    private Business $business;

    private BusinessMember $director;

    private LoanType $product;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00');
        Storage::fake('local');
        Notification::fake();
        config(['lending.business_loans.enabled' => true, 'lending.requests.enabled' => true, 'lending.approvals.enabled' => false,
            'lending.aml.enabled' => false, 'lending.ledger.enabled' => false, 'lending.engine' => 'v2', 'lending.notifications.sms.driver' => 'log']);
        $this->seed(RoleSeeder::class);
        $this->staff = User::factory()->create(['name' => 'Staff One']);
        $this->staff->assignRole('admin');
        $this->staff->givePermissionTo(['edit users', 'view users']);
        $this->checker = User::factory()->create(['name' => 'Staff Two']);
        $this->checker->assignRole('admin');
        $this->checker->givePermissionTo(['edit users', 'view users']);

        $this->owner = User::factory()->create(['kyc_status' => 'approved', 'id_number' => '11111111', 'name' => 'Amina Otieno']);
        $this->owner->assignRole('borrower');
        $this->business = Business::create(['owner_user_id' => $this->owner->id, 'legal_name' => 'Otieno Hardware Ltd', 'registration_number' => 'PVT-2026-001',
            'registration_type' => 'limited_company', 'status' => Business::STATUS_SUBMITTED, 'monthly_revenue' => 300000]);
        BusinessMember::create(['business_id' => $this->business->id, 'user_id' => $this->owner->id, 'full_name' => 'Amina Otieno', 'role' => 'owner',
            'id_number' => '11111111', 'ownership_pct' => 60]);
        $this->director = BusinessMember::create(['business_id' => $this->business->id, 'full_name' => 'Peter Mwangi', 'role' => 'director',
            'id_number' => '22222222', 'email' => 'peter@example.test', 'phone' => '0712345678', 'ownership_pct' => 40]);
        foreach (['certificate', 'cr12', 'kra_pin_certificate'] as $t) {
            BusinessDocument::create(['business_id' => $this->business->id, 'type' => $t, 'path' => "b/{$t}.pdf", 'original_name' => "{$t}.pdf", 'mime' => 'application/pdf', 'size' => 10]);
        }
        $this->product = LoanType::forceCreate(['name' => 'Business 1 month 10%', 'audience' => 'business', 'period' => 1, 'unit' => 'months',
            'interest_rate' => 10, 'penalty_rate' => 1, 'grace_period_days' => 0, 'default_threshold_days' => 30]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function verifyDirector(): void
    {
        $link = app(DirectorVerification::class)->send($this->director->fresh(), $this->owner);
        $token = substr($link, strrpos($link, '/') + 1);
        $this->post(route('directors.verify.store', $token), [
            'id_number' => '22222222', 'dob' => '1985-02-01', 'consent' => 1,
            'director_id_front' => UploadedFile::fake()->image('front.jpg'), 'director_selfie' => UploadedFile::fake()->image('me.jpg'),
        ])->assertSessionHasNoErrors();
    }

    private function statement(): void
    {
        BusinessDocument::create(['business_id' => $this->business->id, 'type' => 'bank_statement', 'path' => 'b/st.pdf', 'original_name' => 'st.pdf', 'mime' => 'application/pdf', 'size' => 10]);
    }

    private function approveForm(array $extra = []): array
    {
        return $extra + ['credit_limit' => 200000, 'checks' => array_fill_keys(array_keys(Business::checks()), 1)];
    }

    public function test_the_plan_rules_hold_the_approval_until_they_are_met(): void
    {
        $blockers = app(BusinessApproval::class)->blockers($this->business, 200000);
        $this->assertCount(2, $blockers);   // statements, director (shareholding is 100%)
        $this->assertStringContainsString('Bank statements', $blockers[0]);
        $this->assertStringContainsString('Peter Mwangi', $blockers[1]);

        $this->actingAs($this->staff)->post(route('business-reviews.approve', $this->business), $this->approveForm())->assertSessionHasErrors('checks');
        $this->assertSame('submitted', $this->business->fresh()->status);

        $this->statement();
        $this->verifyDirector();
        $this->director->update(['ownership_pct' => 30]);
        $this->actingAs($this->staff)->post(route('business-reviews.approve', $this->business), $this->approveForm())->assertSessionHasErrors('checks');
        $this->director->update(['ownership_pct' => 40]);

        $this->actingAs($this->staff)->post(route('business-reviews.approve', $this->business), $this->approveForm(['credit_limit' => null]))
            ->assertSessionHasErrors('credit_limit');
        $this->actingAs($this->staff)->post(route('business-reviews.approve', $this->business), $this->approveForm())->assertSessionHasNoErrors();
        $b = $this->business->fresh();
        $this->assertSame('approved', $b->status);
        $this->assertSame(200000.0, (float) $b->credit_limit);
        $this->assertTrue($b->checks['statements']);
    }

    public function test_a_registration_number_on_another_business_stops_approval(): void
    {
        $this->statement();
        $this->verifyDirector();
        Business::create(['owner_user_id' => $this->staff->id, 'legal_name' => 'Copy Ltd', 'registration_number' => 'PVT-2026-001', 'status' => Business::STATUS_APPROVED]);
        $this->assertStringContainsString('already on another business', app(BusinessApproval::class)->blockers($this->business, 1000)[0]);
    }

    public function test_a_director_link_checks_the_id_and_keeps_the_pictures_from_the_owner(): void
    {
        $link = app(DirectorVerification::class)->send($this->director, $this->owner);
        Notification::assertSentOnDemand(\App\Notifications\DirectorVerificationLink::class);
        $token = substr($link, strrpos($link, '/') + 1);
        $this->assertSame(hash('sha256', $token), $this->director->fresh()->verify_token_hash);
        $this->get(route('directors.verify', $token))->assertOk()->assertSee('Verify yourself, Peter');

        $this->post(route('directors.verify.store', $token), ['id_number' => '99999999', 'dob' => '1985-02-01', 'consent' => 1,
            'director_id_front' => UploadedFile::fake()->image('f.jpg'), 'director_selfie' => UploadedFile::fake()->image('s.jpg')])->assertSessionHasErrors('id_number');
        $this->assertNull($this->director->fresh()->verified_at);

        $this->verifyDirector();
        $m = $this->director->fresh();
        $this->assertNotNull($m->verified_at);
        $this->assertNull($m->verify_token_hash);
        $this->assertSame(2, $m->documents()->count());
        // the owner's list of company documents doesn't include the director's ID
        $this->assertSame(0, $this->business->documents()->whereNotNull('business_member_id')->count());
        $this->flushSession();
        $this->get(route('directors.verify', $token))->assertOk()->assertSee('expired');

        // an old link stops working
        $other = BusinessMember::create(['business_id' => $this->business->id, 'full_name' => 'Late Director', 'role' => 'director', 'email' => 'l@example.test']);
        $link = app(DirectorVerification::class)->send($other);
        Carbon::setTestNow('2026-11-05 10:00');
        $this->assertNull(app(DirectorVerification::class)->find(substr($link, strrpos($link, '/') + 1)));
    }

    public function test_the_owner_borrows_within_the_limit_one_application_at_a_time(): void
    {
        $this->statement();
        $this->verifyDirector();
        app(BusinessApproval::class)->approve($this->business, $this->staff, 50000, null);

        $this->actingAs($this->owner)->get(route('account.business.borrow', $this->business))->assertOk()->assertSee('Send application');
        $this->actingAs($this->owner)->post(route('account.business.borrow.store', $this->business), [
            'loan_type_id' => $this->product->id, 'amount' => '60,000', 'purpose' => 'Stock for December', 'consent' => 1,
        ])->assertSessionHasErrors('amount');

        $this->actingAs($this->owner)->post(route('account.business.borrow.store', $this->business), [
            'loan_type_id' => $this->product->id, 'amount' => '30,000', 'purpose' => 'Stock for December', 'consent' => 1,
        ])->assertSessionHasNoErrors();
        $loan = Loan::firstOrFail();
        $this->assertSame($this->business->id, (int) $loan->business_id);
        $this->assertSame($this->owner->id, (int) $loan->user_id);
        $this->assertSame('pending', $loan->status);
        $this->assertSame(1, DB::table('loan_cycles')->where('loan_id', $loan->id)->count());
        $this->assertSame(20000.0, app(BusinessLending::class)->available($this->business->fresh()));

        $this->actingAs($this->owner)->post(route('account.business.borrow.store', $this->business), [
            'loan_type_id' => $this->product->id, 'amount' => '10,000', 'purpose' => 'More stock please', 'consent' => 1,
        ])->assertSessionHasErrors('amount');

        // an individual product is refused
        $loan->update(['status' => 'rejected']);
        $personal = LoanType::forceCreate(['name' => 'Personal', 'audience' => 'individual', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 1]);
        $this->actingAs($this->owner)->post(route('account.business.borrow.store', $this->business), [
            'loan_type_id' => $personal->id, 'amount' => '10,000', 'purpose' => 'More stock please', 'consent' => 1,
        ])->assertSessionHasErrors('amount');
    }

    public function test_someone_else_cannot_borrow_for_the_business(): void
    {
        $other = User::factory()->create(['kyc_status' => 'approved']);
        $other->assignRole('borrower');
        $this->actingAs($other)->get(route('account.business.borrow', $this->business))->assertNotFound();
    }

    public function test_with_second_sign_offs_on_another_person_approves_the_business(): void
    {
        config(['lending.approvals.enabled' => true]);
        $this->statement();
        $this->verifyDirector();
        $this->actingAs($this->staff)->post(route('business-reviews.approve', $this->business), $this->approveForm())->assertSessionHasNoErrors();
        $this->assertSame('submitted', $this->business->fresh()->status);
        $a = DB::table('approvals')->where('action', 'approve_business')->first();
        $this->assertSame(200000.0, (float) $a->amount);

        $this->expectException(\DomainException::class);
        try {
            app(Approvals::class)->decide($a->id, $this->staff, true);
        } finally {
            app(Approvals::class)->decide($a->id, $this->checker, true);
            $this->assertSame('approved', $this->business->fresh()->status);
            $this->assertSame(200000.0, (float) $this->business->fresh()->credit_limit);
        }
    }

    public function test_a_change_request_reopens_the_business(): void
    {
        $this->statement();
        $this->verifyDirector();
        app(BusinessApproval::class)->approve($this->business, $this->staff, 50000, null);
        $this->actingAs($this->owner)->post(route('account.business.change', $this->business), ['message' => 'We have a new director, Jane.'])->assertSessionHasNoErrors();
        $r = DB::table('borrower_requests')->first();
        $this->assertSame('business_change', $r->type);
        $this->assertSame($this->business->id, (int) $r->business_id);

        $this->actingAs($this->staff)->post(route('staff.requests.borrower', $r->id), ['decision' => 'approve'])->assertSessionHas('success');
        $b = $this->business->fresh();
        $this->assertSame('rejected', $b->status);
        $this->assertTrue($b->isEditable());
        $this->assertStringContainsString('new director', $b->review_notes);
    }

    public function test_aml_screening_matches_the_list_and_must_be_cleared(): void
    {
        config(['lending.aml.enabled' => true]);
        $this->statement();
        $this->verifyDirector();
        $csv = tempnam(sys_get_temp_dir(), 'sl');
        file_put_contents($csv, "name,aliases,dob,country,reference,type\nPeter Kamau Mwangi,P. Mwangi,1970,Kenya,KEi.001,individual\nSome Other Person,,,,X1,individual\n");
        $this->assertSame(3, app(SanctionsList::class)->import($csv, 'Test list', $this->staff));

        $s = app(Screening::class);
        $this->assertNotEmpty($s->matches('Peter Mwangi'));
        $this->assertEmpty($s->matches('Amina Otieno'));

        $this->assertContains('Run the AML screening and tick the checklist before approving.', app(BusinessApproval::class)->blockers($this->business, 1000));
        $this->actingAs($this->staff)->post(route('aml.screen', ['business', $this->business->id]))->assertSessionHas('error');
        $this->assertArrayHasKey('Director: Peter Mwangi #' . $this->director->id, $this->business->fresh()->checks['aml']['hits']);

        // a match needs a reason
        $this->actingAs($this->staff)->post(route('aml.decide', ['business', $this->business->id]), ['aml_checks' => array_keys(Screening::CHECKS)])->assertSessionHasErrors('aml');
        $this->actingAs($this->staff)->post(route('aml.decide', ['business', $this->business->id]), [
            'aml_checks' => array_keys(Screening::CHECKS), 'aml_note' => 'Listed person born 1970; ours born 1985, different ID.',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($s->isClear($this->business->fresh()));

        $this->actingAs($this->staff)->post(route('business-reviews.approve', $this->business), $this->approveForm())->assertSessionHasNoErrors();
        $b = $this->business->fresh();
        $this->assertSame('approved', $b->status);
        $this->assertTrue($b->checks['aml']['clear']);   // kept through the approval
    }
}
