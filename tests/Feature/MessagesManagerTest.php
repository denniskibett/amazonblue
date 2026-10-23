<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CollectionNotice;
use App\Services\Messaging\CollectionNotices;
use Database\Seeders\LookupTablesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/** Email and SMS manager: notices go out only in a batch a manager saves and then sends (owner, 23 Oct 2026). */
class MessagesManagerTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-23 10:00');
        config(['lending.engine' => 'v2', 'lending.penalty.cap' => 'in_duplum']);
        $this->seed(LookupTablesSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // 901 due in 2 days · 902 five days late · 903 sixty days late
        foreach ([[901, '2026-10-15', '2026-10-25'], [902, '2026-10-08', '2026-10-18'], [903, '2026-08-14', '2026-08-24']] as [$id, $start, $due]) {
            $this->loanRow($id, 1, 10000, 10000, $start, $due, 'disbursed', 1, 90);
            $this->cycleRow($id, $id, 1, 0, 2000, 12000, 20, $start, $due, 'active', 'Initial');
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_each_kind_picks_the_right_loans(): void
    {
        $n = app(CollectionNotices::class);
        $this->assertSame([901], $n->candidates('due_soon', 3)->map(fn ($r) => $r->loan->id)->all());
        $this->assertSame([902], $n->candidates('overdue')->map(fn ($r) => $r->loan->id)->all());
        $this->assertSame([903], $n->candidates('final_notice')->map(fn ($r) => $r->loan->id)->all());
    }

    public function test_a_draft_sends_nothing_and_sending_reaches_each_ticked_borrower_once(): void
    {
        Notification::fake();
        $this->actingAs($this->admin)->post(route('staff.messages.store'), ['kind' => 'final_notice', 'loans' => [903], 'channels' => ['email']])
            ->assertRedirect();
        $batch = DB::table('message_batches')->first();
        $this->assertSame('draft', $batch->status);
        $this->assertSame(1, (int) $batch->recipients);
        Notification::assertNothingSent();

        $this->actingAs($this->admin)->post(route('staff.messages.send', $batch->id))->assertSessionHasErrors('confirm');
        Notification::assertNothingSent();

        $this->actingAs($this->admin)->post(route('staff.messages.send', $batch->id), ['confirm' => '1'])->assertRedirect();
        $borrower = User::find(DB::table('loans')->where('id', 903)->value('user_id'));
        Notification::assertSentTo($borrower, CollectionNotice::class, fn ($n) => $n->kind === 'final_notice' && str_contains($n->email['subject'], 'Final notice'));
        Notification::assertCount(1);
        $this->assertSame('sent', DB::table('message_batches')->value('status'));
        $this->assertSame('sent', DB::table('message_batch_items')->value('status'));

        // sent once: a second send of the same batch is refused
        $this->actingAs($this->admin)->post(route('staff.messages.send', $batch->id), ['confirm' => '1'])->assertStatus(409);
    }

    public function test_a_loan_paid_after_the_draft_is_skipped_when_it_sends(): void
    {
        Notification::fake();
        $this->actingAs($this->admin)->post(route('staff.messages.store'), ['kind' => 'overdue', 'loans' => [902], 'channels' => ['email']]);
        DB::table('repayments')->insert(['loan_id' => 902, 'loan_cycle_id' => 902, 'amount' => 20000, 'repayment_date' => '2026-10-23',
            'transaction' => 'QKPAID902', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin)->post(route('staff.messages.send', DB::table('message_batches')->value('id')), ['confirm' => '1']);
        Notification::assertNothingSent();
        $this->assertSame('skipped', DB::table('message_batch_items')->value('status'));
    }

    public function test_only_managers_and_admins_open_the_manager(): void
    {
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->actingAs($teller)->get(route('staff.messages.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('staff.messages.index'))->assertOk()->assertSee('Final notice');
    }
}
