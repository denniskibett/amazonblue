<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Frontend audit, 20 Nov 2026: every page crawled as each role. These keep the bugs it found from coming back.
 */
class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    /** A directive glued to a word ("Payment@if") is not compiled while its @endif is: a parse error on the page. */
    public function test_every_blade_view_compiles_to_valid_php(): void
    {
        $bad = [];
        foreach (File::allFiles(resource_path('views')) as $f) {
            if (! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }
            try {
                token_get_all(Blade::compileString($f->getContents()), TOKEN_PARSE);
            } catch (\ParseError $e) {
                $bad[] = $f->getRelativePathname() . ': ' . $e->getMessage();
            }
        }
        $this->assertSame([], $bad);
    }

    public function test_a_partner_login_without_a_partner_record_sees_its_home(): void
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create();
        $u->assignRole('partner');
        $this->actingAs($u)->get(route('account.home'))->assertOk();
    }

    public function test_the_wallets_note_shows_no_raw_template_code(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $html = $this->actingAs($admin)->get('/staff/wallets')->assertOk()->getContent();
        $this->assertStringNotContainsString('journal@if', $html);
    }

    public function test_old_loan_addresses_open_the_working_pages(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $borrower = User::factory()->create();
        $borrower->assignRole('borrower');
        $typeId = \Illuminate\Support\Facades\DB::table('loan_types')->insertGetId(['name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10]);
        $loan = Loan::create(['user_id' => $borrower->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000, 'borrow_date' => now()->toDateString(),
            'status' => 'pending', 'broker_status' => 0, 'reason' => 'Stock for the shop', 'consent' => true, 'consent_date' => now(), 'cycle' => 1]);
        $this->actingAs($admin)->get(route('loans.rollover.statement', $loan))->assertRedirect(route('users.loans.statement', [$loan->user_id, $loan->id]));
        $this->actingAs($admin)->get(route('users.loans.edit', [$loan->user_id, $loan->id]))->assertRedirect(route('loans.edit', $loan));
    }

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        $a = User::factory()->create();
        $a->assignRole('super-admin');

        return $a;
    }

    private function loanFor(): Loan
    {
        $b = User::factory()->create();
        $b->assignRole('borrower');
        $typeId = \Illuminate\Support\Facades\DB::table('loan_types')->insertGetId(['name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10]);

        return Loan::create(['user_id' => $b->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000, 'borrow_date' => now()->toDateString(),
            'status' => 'pending', 'broker_status' => 0, 'reason' => 'Stock for the shop', 'consent' => true, 'consent_date' => now(), 'cycle' => 1]);
    }

    public function test_editing_an_investment_keeps_fields_the_modal_left_empty_and_accepts_fixed_return(): void
    {
        $admin = $this->admin();
        $inv = \App\Models\Investment::create(['name' => 'Line A', 'type' => 'other', 'facility_type' => 'long_term', 'country' => 'Kenya', 'initial_amount' => 100000,
            'purchase_date' => '2026-01-01', 'maturity_date' => '2027-01-01', 'status' => 'active', 'return_type' => 'fixed_return']);
        $this->actingAs($admin)->putJson(route('investments.update', $inv), ['name' => 'Line A2', 'maturity_date' => '', 'exit_date' => '', 'return_type' => 'fixed_return'])->assertOk();
        $inv->refresh();
        $this->assertSame('Line A2', $inv->name);
        $this->assertSame('2027-01-01', $inv->maturity_date?->toDateString() ?? (string) $inv->maturity_date);
        $this->assertSame('fixed_return', $inv->return_type);
    }

    public function test_a_loan_edit_without_the_consent_field_keeps_the_signed_consent(): void
    {
        $admin = $this->admin();
        $loan = $this->loanFor();
        $this->actingAs($admin)->put(route('loans.update', $loan), ['loan_type_id' => $loan->loan_type_id, 'amount' => 5000, 'borrow_date' => now()->toDateString(),
            'status' => 'pending', 'broker_status' => 0, 'reason' => 'Stock for the shop, restocking']);
        $this->assertTrue((bool) $loan->fresh()->consent);
    }

    public function test_editing_a_repayment_without_the_mode_keeps_its_mode(): void
    {
        $admin = $this->admin();
        $loan = $this->loanFor();
        $r = \App\Models\Repayment::create(['loan_id' => $loan->id, 'amount' => 500, 'repayment_date' => now()->toDateString(), 'transaction' => 'UJ7CQ9KQCR', 'mode' => 'mpesa']);
        $this->actingAs($admin)->putJson(route('repayments.update', $r), ['amount' => 600, 'repayment_date' => now()->toDateString(), 'transaction' => 'UJ7CQ9KQCR'])->assertOk();
        $this->assertSame('mpesa', $r->fresh()->mode);
        $this->assertEquals(600, (float) $r->fresh()->amount);
    }

    public function test_rejecting_a_kyc_keeps_the_aml_screening(): void
    {
        $admin = $this->admin();
        $u = User::factory()->create(['kyc_status' => 'submitted', 'kyc_checks' => ['aml' => ['result' => 'clear', 'at' => '2026-11-20']]]);
        $u->assignRole('borrower');
        $this->actingAs($admin)->post(route('kyc-reviews.reject', $u), ['notes' => 'The back of your ID is blurry, please retake it.']);
        $this->assertSame('rejected', $u->fresh()->kyc_status);
        $this->assertSame('clear', $u->fresh()->kyc_checks['aml']['result'] ?? null);
    }

    public function test_the_phone_repair_changes_only_certain_numbers_and_keeps_an_undo_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        // rows saved before phones were normalised on save: written straight to the table
        [$a, $b, $c] = [User::factory()->create(), User::factory()->create(), User::factory()->create()];
        \Illuminate\Support\Facades\DB::table('users')->where('id', $a->id)->update(['phone' => '+254712000001', 'kin_phone' => '0722 000 002']);
        \Illuminate\Support\Facades\DB::table('users')->where('id', $b->id)->update(['phone' => '0712000001', 'kin_phone' => '28021398']);
        \Illuminate\Support\Facades\DB::table('users')->where('id', $c->id)->update(['phone' => '+255712000003']);

        $this->artisan('data:repair-phones')->assertSuccessful();
        $this->assertSame('0722 000 002', $a->fresh()->getRawOriginal('kin_phone'), 'a dry run changes nothing');

        $this->artisan('data:repair-phones', ['--apply' => true])->assertSuccessful();
        $this->assertSame('+254722000002', $a->fresh()->getRawOriginal('kin_phone'));
        $this->assertSame('0712000001', $b->fresh()->getRawOriginal('phone'), 'it would collide with another user: listed, not changed');
        $this->assertSame('28021398', $b->fresh()->getRawOriginal('kin_phone'), 'unreadable: listed, not changed');
        $this->assertSame('+255712000003', $c->fresh()->getRawOriginal('phone'), 'a foreign number stays');
        $this->assertCount(1, \Illuminate\Support\Facades\Storage::disk('local')->files('repairs'));
    }
}
