<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanGuarantee;
use App\Models\User;
use App\Services\Loans\Guarantees;
use App\Support\PrivateFiles;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/**
 * Owner, 21 Nov 2026: the guarantor signs a guarantee and release, with their details and ID, from a texted link
 * before the borrower's money goes out.
 */
class GuarantorSignoffTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private Guarantees $g;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.approvals.enabled' => false, 'lending.notifications.sms.driver' => 'log']);
        Storage::fake(PrivateFiles::DISK);
        $this->loanTypes();
        Carbon::setTestNow('2026-11-21 10:00');
        $this->g = app(Guarantees::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** An approved loan whose borrower named a guarantor at KYC. */
    private function loan(int $id = 2001, string $status = 'approved', ?User $borrower = null): Loan
    {
        $this->loanRow($id, 1, 5000, 5000, '2026-11-21', '2026-12-01', $status, 1, 30);
        $this->cycleRow($id, $id, 1, 0, 1000, 6000, 20, '2026-11-21', '2026-12-01', 'active', 'Initial');
        $loan = Loan::findOrFail($id);
        if ($borrower) {
            $loan->update(['user_id' => $borrower->id]);
        }
        $loan->user->assignRole('borrower');
        $loan->user->update(['name' => 'Mary Wanjiku', 'id_number' => '11111111']);
        $type = DB::table('contact_types')->insertGetId(['name' => 'Friend', 'slug' => 'friend-' . $id, 'created_at' => now(), 'updated_at' => now()]);
        if (! $loan->user->contacts()->where('relationship_specific', 'Guarantor')->exists()) {
            $loan->user->contacts()->create(['contact_type_id' => $type, 'name' => 'John Otieno', 'phone' => '+254722000111', 'relationship_specific' => 'Guarantor']);
        }

        return $loan->fresh();
    }

    /** Opens the guarantee and returns a working link token. */
    private function token(Loan $loan): string
    {
        $row = $this->g->open($loan);
        $this->assertNotNull($row);
        $url = $this->g->send($row->fresh());

        return Str::afterLast($url, '/');
    }

    private function signature(): string
    {
        $img = imagecreatetruecolor(40, 20);
        ob_start();
        imagepng($img);

        return 'data:image/png;base64,' . base64_encode(ob_get_clean());
    }

    private function form(array $over = []): array
    {
        return $over + [
            'id_number' => '22334455', 'dob' => '1985-04-02', 'residence' => 'Nakuru, Milimani', 'occupation' => 'Teacher',
            'id_front' => UploadedFile::fake()->image('front.jpg'), 'id_back' => UploadedFile::fake()->image('back.jpg'),
            'signature_data' => $this->signature(), 'agree' => '1',
        ];
    }

    private function staff(string $role = 'admin'): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $u->assignRole($role);
        $u->givePermissionTo('create disbursements');

        return $u;
    }

    private function disburse(Loan $loan, string $ref)
    {
        return $this->postJson(route('disbursements.store'), ['loan_id' => $loan->id, 'amount' => 5000, 'disburse_date' => '2026-11-21',
            'transaction' => $ref, 'mode' => 'mpesa']);
    }

    public function test_a_new_loan_opens_the_guarantee_from_the_kyc_guarantor_and_texts_the_link(): void
    {
        $row = $this->g->open($this->loan(status: 'pending'));

        $this->assertSame(['waiting', 'John Otieno', '+254722000111', 'Friend'], [$row->status, $row->name, $row->phone, $row->relationship]);
        $this->assertNotNull($row->fresh()->token_hash);
        $this->assertTrue($row->fresh()->link_expires_at->isSameDay(now()->addDays(14)));
    }

    public function test_the_guarantor_reads_the_guarantee_signs_and_uploads_their_id(): void
    {
        $loan = $this->loan();
        $token = $this->token($loan);

        $this->get(route('guarantor.sign', $token))->assertOk()
            ->assertSee("Sign as Mary's guarantor", false)
            ->assertSee('KES 5,000.00')
            ->assertSee('KES 6,000.00 with interest')
            ->assertSee('I agree that Aimzone may now release the money to Mary.');

        $this->post(route('guarantor.sign.store', $token), $this->form())->assertRedirect(route('guarantor.sign', $token));

        $row = LoanGuarantee::sole();
        $this->assertSame(['signed', '22334455', 'Nakuru, Milimani', 'Teacher'], [$row->status, $row->id_number, $row->residence, $row->occupation]);
        $this->assertSame(hash('sha256', $row->terms), $row->terms_sha256);
        $this->assertStringContainsString('ID or passport 22334455', $row->terms);
        foreach (['id_front_path', 'id_back_path', 'signature_path'] as $col) {
            Storage::disk(PrivateFiles::DISK)->assertExists($row->{$col});
        }
        $this->assertNull($row->token_hash);
        $this->get(route('guarantor.sign', $token))->assertOk()->assertSee('Thank you, John')->assertSee('send Mary');
        // used once: the link now says so
        $this->get(route('guarantor.sign', $token))->assertOk()->assertSee('This link no longer works');
    }

    public function test_the_form_needs_a_signature_an_adult_and_someone_other_than_the_borrower(): void
    {
        $token = $this->token($this->loan());

        $this->post(route('guarantor.sign.store', $token), $this->form(['signature_data' => '']))->assertSessionHasErrors('signature_data');
        $this->post(route('guarantor.sign.store', $token), $this->form(['dob' => now()->subYears(17)->toDateString()]))->assertSessionHasErrors('dob');
        $this->post(route('guarantor.sign.store', $token), $this->form(['signature_data' => 'data:image/png;base64,bm90IGFuIGltYWdl']))->assertSessionHasErrors('guarantee');
        $this->post(route('guarantor.sign.store', $token), $this->form(['id_number' => '11111111']))
            ->assertSessionHasErrors(['guarantee' => 'A guarantor must be someone other than the borrower.']);
        $this->post(route('guarantor.sign.store', $token), $this->form(['id_front' => null]))->assertSessionHasErrors('id_front');
        $this->assertSame('waiting', LoanGuarantee::sole()->status);
    }

    public function test_a_bad_or_old_link_shows_nothing_of_the_loan(): void
    {
        $token = $this->token($this->loan());
        $this->get(route('guarantor.sign', str_repeat('x', 48)))->assertOk()->assertSee('This link no longer works')->assertDontSee('Mary');

        Carbon::setTestNow(now()->addDays(15));
        $this->get(route('guarantor.sign', $token))->assertSee('This link no longer works');
        $this->post(route('guarantor.sign.store', $token), $this->form())->assertNotFound();
    }

    public function test_no_money_goes_out_until_the_guarantor_signs(): void
    {
        $loan = $this->loan();
        $token = $this->token($loan);
        $this->actingAs($this->staff());

        $this->disburse($loan, 'QGUAR00001')->assertStatus(422)->assertJsonValidationErrors('loan_id')
            ->assertJsonFragment(['The guarantor, John Otieno, has not signed yet. Send them the link from the loan page, or a manager can waive it.']);
        $this->assertDatabaseCount('disbursements', 0);

        auth()->logout();
        $this->post(route('guarantor.sign.store', $token), $this->form());
        $this->actingAs($this->staff());
        $this->disburse($loan, 'QGUAR00002')->assertCreated();
    }

    public function test_m_pesa_and_sharet_payouts_refuse_too(): void
    {
        $loan = $this->loan();
        $this->g->open($loan);

        $this->expectException(\DomainException::class);
        $this->g->assertReleased($loan);
    }

    public function test_a_manager_can_waive_it_with_a_reason_and_an_officer_cannot(): void
    {
        $loan = $this->loan();
        $this->g->open($loan);

        $officer = User::factory()->create(['email_verified_at' => now()]);
        $officer->givePermissionTo('view loans');
        $officer->assignRole('loan-officer');
        $officer->revokePermissionTo('approve loans');
        if (! $officer->can('approve loans')) {
            $this->actingAs($officer)->post(route('loans.guarantee.waive', $loan), ['reason' => 'Signed on paper'])->assertForbidden();
        }

        $this->actingAs($this->staff())->post(route('loans.guarantee.waive', $loan), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($this->staff())->post(route('loans.guarantee.waive', $loan), ['reason' => 'Signed the paper guarantee in the office'])->assertRedirect();
        $this->assertSame('waived', LoanGuarantee::sole()->status);
        $this->disburse($loan, 'QGUAR00003')->assertCreated();
    }

    public function test_older_loans_and_borrowers_without_a_guarantor_are_not_held(): void
    {
        $loan = $this->loan();
        $this->actingAs($this->staff());
        $this->disburse($loan, 'QGUAR00004')->assertCreated();

        $this->loanRow(2002, 1, 5000, 5000, '2026-11-21', '2026-12-01', 'pending', 1, 30);
        $this->assertNull($this->g->open(Loan::findOrFail(2002)));
    }

    public function test_the_borrower_sees_the_wait_and_can_only_text_the_link_again(): void
    {
        $loan = $this->loan(status: 'pending');
        $this->g->open($loan);
        $borrower = $loan->user;
        $this->partialMock(\App\Services\KycHold::class, fn ($m) => $m->shouldReceive('status')->andReturn([
            'state' => 'clear', 'sections' => [], 'done' => 4, 'total' => 4, 'next' => '/kyc/review', 'notes' => null, 'emailConfirmed' => true,
        ]));
        $borrower->update(['email_verified_at' => now()]);

        $this->actingAs($borrower)->get(route('account.loan', $loan))->assertOk()
            ->assertSee('Waiting for your guarantor to sign')->assertSee('+2547•••111')->assertDontSee('/guarantee/');

        // sent minutes ago: not again yet
        $this->actingAs($borrower)->post(route('account.loan.guarantor.remind', $loan))->assertSessionHas('alert', fn ($a) => $a['title'] === 'Already sent');
        Carbon::setTestNow(now()->addMinutes(11));
        $before = LoanGuarantee::sole()->token_hash;
        $this->actingAs($borrower)->post(route('account.loan.guarantor.remind', $loan))->assertSessionHas('alert', fn ($a) => $a['title'] === 'Sent');
        $this->assertNotSame($before, LoanGuarantee::sole()->token_hash);

        $other = User::factory()->create(['email_verified_at' => now()]);
        $other->assignRole('borrower');
        $this->actingAs($other)->post(route('account.loan.guarantor.remind', $loan))->assertNotFound();
    }

    public function test_the_same_guarantor_only_signs_next_time(): void
    {
        $first = $this->loan(2003, 'repaid');
        DB::table('loans')->where('id', 2003)->update(['status' => 'approved']);
        $token = $this->token($first->fresh());
        $this->post(route('guarantor.sign.store', $token), $this->form())->assertSessionHasNoErrors();
        $this->assertSame('signed', LoanGuarantee::sole()->status);
        $this->get(route('guarantor.sign', $token))->assertSee('Thank you');
        DB::table('loans')->where('id', 2003)->update(['status' => 'repaid']);

        $second = $this->loan(2004, 'pending', $first->user);
        $token = $this->token($second);
        $this->get(route('guarantor.sign', $token))->assertSee('Your ID is on file')->assertSee('22334455');
        $this->post(route('guarantor.sign.store', $token), $this->form(['id_front' => null, 'id_back' => null]))->assertRedirect();

        $rows = LoanGuarantee::orderBy('id')->get();
        $this->assertSame('signed', $rows[1]->status);
        $this->assertSame($rows[0]->id_front_path, $rows[1]->id_front_path);
    }

    public function test_staff_see_the_signed_guarantee_and_id_and_a_borrower_cannot(): void
    {
        $loan = $this->loan();
        $this->post(route('guarantor.sign.store', $this->token($loan)), $this->form());
        $staff = $this->staff();

        $this->actingAs($staff)->followingRedirects()->get(route('loans.show', $loan))->assertOk()->assertSee('Signed guarantee')->assertSee('22334455');
        $this->actingAs($staff)->get(route('loans.guarantee.document', $loan))->assertOk()->assertSee('Guarantee and release')->assertSee(LoanGuarantee::sole()->terms_sha256);
        $this->actingAs($staff)->get(route('loans.guarantee.file', [$loan, 'id-front']))->assertOk();

        // a borrower is not staff: sent away, never served the file
        $res = $this->actingAs($loan->user)->get(route('loans.guarantee.file', [$loan, 'id-front']));
        $this->assertContains($res->status(), [302, 403]);
        $this->assertStringNotContainsString('image', (string) $res->headers->get('Content-Type'));
    }

    public function test_staff_can_open_the_signing_page_for_a_guarantor_in_the_office(): void
    {
        $loan = $this->loan();
        $this->g->open($loan, null, false);

        $res = $this->actingAs($this->staff())->post(route('loans.guarantee.sign-here', $loan));
        $res->assertRedirectContains('/guarantee/');
        auth()->logout();
        $this->get($res->headers->get('Location'))->assertOk()->assertSee("Sign as Mary's guarantor", false);
    }

    public function test_the_link_also_goes_to_the_guarantors_email_when_given(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $loan = $this->loan();
        $loan->user->contacts()->where('relationship_specific', 'Guarantor')->update(['email' => 'john@example.com']);

        $token = $this->token($loan);

        \Illuminate\Support\Facades\Notification::assertSentOnDemand(\App\Notifications\GuarantorSignLink::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'john@example.com' && str_contains($n->url, $token));
        $this->get(route('guarantor.sign', $token))->assertSee('john@example.com');
    }
}
