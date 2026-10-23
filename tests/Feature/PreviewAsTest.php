<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Preview;
use Database\Seeders\LookupTablesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Sysadmin "view as": the app answers as the preview, and nothing done in it is kept (owner, 23 Oct 2026). */
class PreviewAsTest extends TestCase
{
    use RefreshDatabase;

    private User $sysadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LookupTablesSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->sysadmin = User::factory()->create(['email_verified_at' => now()]);
        $this->sysadmin->assignRole('super-admin');
    }

    public function test_only_the_sysadmin_can_preview(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('preview.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('preview.start'), ['kind' => 'role', 'role' => 'teller'])->assertForbidden();
    }

    public function test_a_teller_preview_sees_teller_pages_only(): void
    {
        $this->actingAs($this->sysadmin)->post(route('preview.start'), ['kind' => 'role', 'role' => 'teller'])->assertRedirect(route('dashboard'));
        $this->get(route('staff.repayments.receive'))->assertOk()->assertSee('Previewing as');
        $this->assertNotSame(200, $this->get(route('staff.roles'))->status());   // tellers can't open roles
        $this->get(route('preview.index'))->assertOk();               // the switch page still works as the sysadmin
    }

    public function test_nothing_done_in_a_preview_is_kept(): void
    {
        $this->actingAs($this->sysadmin)->post(route('preview.start'), ['kind' => 'role', 'role' => 'admin']);
        $borrower = User::factory()->create();
        $before = DB::table('audit_logs')->count();
        $users = DB::table('users')->count();
        // an admin action that writes: a message batch draft
        $this->post(route('staff.messages.store'), ['kind' => 'overdue', 'loans' => [999999], 'channels' => ['email']])->assertRedirect();
        $this->assertSame(0, DB::table('message_batches')->count(), 'the draft was rolled back');
        $this->assertSame($before, DB::table('audit_logs')->count(), 'not even the audit row was kept');
        $this->assertNotNull($borrower->fresh());
        $this->assertSame($users, DB::table('users')->count(), 'the stand-in made for the save was rolled back too');

        $this->post(route('preview.stop'))->assertRedirect(route('preview.index'));
        $this->assertNull(session(Preview::SESSION));
        $this->get(route('staff.roles'))->assertOk();                 // back to the sysadmin
    }

    public function test_a_customer_preview_shows_their_account(): void
    {
        $borrower = User::factory()->create(['name' => 'Wanjiku Preview', 'email_verified_at' => now()]);
        $borrower->assignRole('borrower');
        $this->actingAs($this->sysadmin)->post(route('preview.start'), ['kind' => 'user', 'user_id' => $borrower->id])->assertRedirect(route('account.home'));
        $this->get(route('account.home'))->assertOk()->assertSee('Wanjiku');
        // a staff account can't be previewed as a customer
        $staff = User::factory()->create();
        $staff->assignRole('manager');
        $this->post(route('preview.start'), ['kind' => 'user', 'user_id' => $staff->id])->assertSessionHas('error');
    }
}
