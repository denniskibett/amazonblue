<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Header search (9 Nov 2026): pages and records, each only for who may open them. */
class HeaderSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->super = User::factory()->create();
        $this->super->assignRole('super-admin');
    }

    private function search(User $user, string $q)
    {
        return $this->actingAs($user)->getJson(route('staff.search', ['q' => $q]))->assertOk();
    }

    private function loanFor(User $borrower): int
    {
        $typeId = DB::table('loan_types')->insertGetId(['name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10]);

        return DB::table('loans')->insertGetId(['user_id' => $borrower->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'borrow_date' => now()->toDateString(),
            'status' => 'disbursed', 'broker_status' => 0, 'reason' => 'Search fixture', 'consent' => 1, 'cycle' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_a_page_name_jumps_to_the_page(): void
    {
        $res = $this->search($this->super, 'portfolio')->json('results');
        $this->assertContains(['group' => 'Go to', 'label' => 'Loan Portfolio', 'sub' => 'Lending', 'url' => route('loans.index'), 'icon' => 'briefcase'], $res);

        // an empty box lists a few pages to start from
        $this->assertNotEmpty($this->search($this->super, '')->json('results'));
    }

    public function test_records_are_found_by_number_name_and_partner(): void
    {
        $borrower = User::factory()->create(['name' => 'Wanjiku Kamau', 'phone' => '0712345678']);
        $loanId = $this->loanFor($borrower);
        Partner::create(['name' => 'Savanna Capital Ltd', 'email' => 's@example.test', 'type' => 'corporate']);
        $loanNo = 'LN-'.str_pad((string) $loanId, 5, '0', STR_PAD_LEFT);

        $this->search($this->super, $loanNo)->assertJsonFragment(['group' => 'Loans', 'label' => 'Loan '.$loanNo, 'url' => route('loans.show', $loanId)]);
        $this->search($this->super, 'Wanjiku')->assertJsonFragment(['group' => 'People', 'label' => 'Wanjiku Kamau'])
            ->assertJsonFragment(['group' => 'Loans', 'label' => 'Loan '.$loanNo]);
        $this->search($this->super, '712345678')->assertJsonFragment(['label' => 'Wanjiku Kamau']);
        $this->search($this->super, 'Savanna')->assertJsonFragment(['group' => 'Partners', 'label' => 'Savanna Capital Ltd']);
        // LIKE wildcards are matched literally
        $this->search($this->super, '%%')->assertJsonMissing(['group' => 'People']);
    }

    public function test_restricted_roles_get_only_what_they_may_open(): void
    {
        $borrower = User::factory()->create(['name' => 'Wanjiku Kamau']);
        $loanId = $this->loanFor($borrower);
        Partner::create(['name' => 'Savanna Capital Ltd', 'email' => 's@example.test', 'type' => 'corporate']);

        // an investment manager: partners yes, loans, people and lending pages no
        $im = User::factory()->create();
        $im->assignRole('investment-manager');
        $this->search($im, 'Savanna')->assertJsonFragment(['group' => 'Partners']);
        $this->search($im, 'Wanjiku')->assertJsonMissing(['group' => 'Loans'])->assertJsonMissing(['group' => 'People']);
        $this->search($im, (string) $loanId)->assertJsonMissing(['group' => 'Loans']);
        $this->search($im, 'portfolio')->assertJsonMissing(['label' => 'Loan Portfolio']);
        $this->search($im, 'user')->assertJsonMissing(['label' => 'User Management']);

        // a teller: loans yes, people and partners no
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->search($teller, 'Wanjiku')->assertJsonFragment(['group' => 'Loans'])->assertJsonMissing(['group' => 'People']);
        $this->search($teller, 'Savanna')->assertJsonMissing(['group' => 'Partners']);

        // customers never get here (and would get nothing)
        $borrower->assignRole('borrower');
        $this->actingAs($borrower)->getJson(route('staff.search', ['q' => 'Savanna']))->assertForbidden();
    }

    public function test_the_header_renders_the_search_box_for_staff(): void
    {
        $this->actingAs($this->super)->get(route('dashboard'))->assertOk()
            ->assertSee('Search or jump to…')->assertSee('azSearch(', false)->assertSee('header-search-results', false);
    }
}
