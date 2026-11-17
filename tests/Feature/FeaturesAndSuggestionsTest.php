<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Features and suggestions (owner, 17 Nov 2026): the sysadmin's tracker, and the Suggest button for everyone signed in. */
class FeaturesAndSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_the_tracker_starts_with_the_audit_list_and_only_the_sysadmin_sees_it(): void
    {
        $this->assertGreaterThan(30, DB::table('feature_items')->count());
        $admin = $this->userWith('super-admin');
        $this->actingAs($admin)->get(route('staff.features'))->assertOk()
            ->assertSee('Features and suggestions')->assertSee('One record of who funded each loan')->assertSee('data-sheet="feature-new"', false);
        $this->actingAs($this->userWith('admin'))->get(route('staff.features'))->assertForbidden();
    }

    public function test_anyone_signed_in_can_suggest_and_the_sysadmin_can_reply_and_track_it(): void
    {
        $borrower = $this->userWith('borrower');
        $this->actingAs($borrower)->postJson(route('suggestions.store'), ['kind' => 'idea', 'body' => 'Show my due date on the home page', 'page' => 'https://x.test/dashboard?a=1'])->assertOk();
        $s = DB::table('suggestions')->first();
        $this->assertSame('/dashboard', $s->page);
        $this->assertSame($borrower->id, (int) $s->user_id);
        $this->actingAs($borrower)->postJson(route('suggestions.store'), ['kind' => 'idea', 'body' => 'x'])->assertStatus(422);

        $admin = $this->userWith('super-admin');
        $this->actingAs($admin)->get(route('staff.features', ['tab' => 'suggestions']))->assertOk()->assertSee('Show my due date on the home page');
        $this->actingAs($admin)->post(route('staff.suggestions.reply', $s->id), ['status' => 'planned', 'reply' => 'Coming next week'])->assertSessionHas('success');
        $this->assertSame('planned', DB::table('suggestions')->value('status'));

        $this->actingAs($admin)->post(route('staff.features.store'), ['suggestion_id' => $s->id, 'area' => 'Borrowers', 'name' => 'Due date on home',
            'status' => 'missing', 'priority' => 'next', 'shortcoming' => 'Asked by a borrower'])->assertSessionHas('success');
        $this->assertSame($s->id, (int) DB::table('feature_items')->where('name', 'Due date on home')->value('suggestion_id'));
        $f = DB::table('feature_items')->where('name', 'Due date on home')->value('id');
        $this->actingAs($admin)->post(route('staff.features.update', $f), ['area' => 'Borrowers', 'name' => 'Due date on home', 'status' => 'working', 'priority' => 'later'])->assertSessionHas('success');
        $this->assertSame('working', DB::table('feature_items')->where('id', $f)->value('status'));

        $this->actingAs($this->userWith('admin'))->post(route('staff.features.update', $f), ['area' => 'A', 'name' => 'B', 'status' => 'missing', 'priority' => 'now'])->assertForbidden();
    }
}
