<?php

namespace Tests\Feature;

use App\Models\Borrower;
use App\Models\User;
use App\Notifications\CollectionNotice;
use Database\Seeders\LookupTablesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Borrowers data (owner, 23 Oct 2026): fill the gaps, "Complete your profile", the profile email batch, and the list. */
class BorrowerDetailsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LookupTablesSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function borrower(array $user = [], ?array $row = []): User
    {
        $u = User::factory()->create($user + ['kyc_status' => 'approved']);
        $u->assignRole('borrower');
        if ($row !== null) {
            Borrower::create($row + ['user_id' => $u->id, 'client_type' => 'individual', 'status' => 1]);
        }

        return $u->fresh();
    }

    public function test_fill_gaps_is_a_dry_run_until_apply_and_only_creates_missing_rows(): void
    {
        $has = $this->borrower([], ['income_type' => 'employed', 'net_salary' => 50000, 'employer_name' => 'Acme']);
        $none = $this->borrower([], null);

        $this->artisan('borrowers:fill-gaps')->assertSuccessful();
        $this->assertNull(Borrower::where('user_id', $none->id)->first());

        $this->artisan('borrowers:fill-gaps', ['--apply' => true])->assertSuccessful();
        $this->assertNotNull(Borrower::where('user_id', $none->id)->first());
        $this->assertSame(1, Borrower::where('user_id', $has->id)->count());
        $this->assertSame('Acme', Borrower::where('user_id', $has->id)->value('employer_name'));
    }

    public function test_a_borrower_fills_only_empty_details_and_can_always_set_the_pay_schedule(): void
    {
        $u = $this->borrower([], ['net_salary' => 30000]);
        $this->actingAs($u)->get(route('account.details'))->assertOk()->assertSee('Complete your profile');

        $this->actingAs($u)->post(route('account.details.store'), [
            'income_type' => 'employed', 'net_salary' => 999999, 'employer_name' => 'Acme Ltd', 'pay_frequency' => 'monthly', 'pay_day' => 28,
        ])->assertRedirect(route('account.home'));

        $b = $u->borrower()->first();
        $this->assertSame(30000.0, (float) $b->net_salary, 'income already on file is not raised from the account');
        $this->assertSame('employed', $b->income_type);
        $this->assertSame('Acme Ltd', $b->employer_name);
        $this->assertSame(28, (int) $b->pay_day);
        $this->assertSame([], $b->missing());
        $this->assertNotNull($b->profile_completed_at);

        $this->actingAs($u)->post(route('account.details.store'), ['pay_frequency' => 'monthly'])->assertSessionHasErrors('pay_day');
        $this->actingAs($u)->post(route('account.details.store'), ['pay_frequency' => 'weekly'])->assertRedirect();
        $this->assertSame('weekly', $b->fresh()->pay_frequency);
        $this->assertNull($b->fresh()->pay_day);
    }

    public function test_the_profile_batch_emails_only_people_still_missing_details(): void
    {
        Notification::fake();
        $gap = $this->borrower(['email' => 'gap@example.test'], ['income_type' => 'employed']);
        $done = $this->borrower([], ['income_type' => 'employed', 'net_salary' => 1, 'employer_name' => 'X', 'pay_frequency' => 'weekly']);

        $this->actingAs($this->admin)->get(route('staff.messages.create', ['kind' => 'complete_profile']))->assertOk()
            ->assertSee($gap->name)->assertDontSee($done->name);
        $this->actingAs($this->admin)->post(route('staff.messages.store'), ['kind' => 'complete_profile', 'loans' => [$gap->id], 'channels' => ['email']])->assertRedirect();
        $batch = DB::table('message_batches')->first();
        Notification::assertNothingSent();

        $this->actingAs($this->admin)->post(route('staff.messages.send', $batch->id), ['confirm' => '1'])->assertRedirect();
        Notification::assertSentTo($gap, CollectionNotice::class, fn ($n) => $n->kind === 'complete_profile');
        Notification::assertCount(1);
    }

    public function test_the_borrowers_list_shows_what_is_missing(): void
    {
        $u = $this->borrower(['name' => 'Zawadi Example'], ['income_type' => 'employed']);
        $this->actingAs($this->admin)->get(route('staff.borrowers'))->assertOk()->assertSee('Zawadi Example')->assertSee('Pay schedule');

        $teller = User::factory()->create();
        $teller->assignRole('teller');
        if (! $teller->can('view users')) {
            $this->actingAs($teller)->get(route('staff.borrowers'))->assertForbidden();
        }
    }
}
