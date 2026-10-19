<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\StaffAlert;
use App\Support\PartnerFunding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Where partners send money: saved on /system (super-admin, current password) wins over config; blank falls back.
 */
class PartnerFundingSettingsTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'bank_name' => 'I&M Bank',
        'bank_account_name' => 'IMAGINE-NATION AGENCY LIMITED',
        'bank_account_number' => '01104493106399',
        'bank_branch' => 'Wilson',
        'bank_swift' => '',
        'mpesa_paybill' => '542542',
        'mpesa_account' => '01104493106399',
        'reference_prefix' => 'AZP-',
        'min_amount' => '2000',
    ];

    public function test_values_come_from_system_settings_then_config(): void
    {
        config(['lending.partner_funding.bank_account_number' => '01104493106350', 'lending.partner_funding.min_amount' => 1000]);
        $this->assertSame('01104493106350', PartnerFunding::all()['bank_account_number']);
        $this->assertSame(1000, PartnerFunding::minAmount());

        PartnerFunding::save(['bank_account_number' => '999888777', 'min_amount' => '2500']);
        $this->assertSame('999888777', PartnerFunding::all()['bank_account_number']);
        $this->assertSame(2500, PartnerFunding::minAmount());

        // a blank saved value hands back to config
        PartnerFunding::save(['bank_account_number' => '', 'min_amount' => '']);
        $this->assertSame('01104493106350', PartnerFunding::all()['bank_account_number']);
        $this->assertSame(1000, PartnerFunding::minAmount());
    }

    public function test_only_the_super_admin_can_change_them(): void
    {
        $this->actingAs($this->staff('admin'))
            ->put('/system/partner-funding', $this->form + ['current_password' => 'password'])
            ->assertForbidden();
        $this->assertSame([], PartnerFunding::saved());
    }

    public function test_a_wrong_password_saves_nothing(): void
    {
        $this->actingAs($this->staff('super-admin'))
            ->put('/system/partner-funding', $this->form + ['current_password' => 'not-my-password'])
            ->assertSessionHasErrorsIn('partnerFunding', 'current_password');
        $this->assertSame([], PartnerFunding::saved());
    }

    public function test_a_change_is_saved_audited_and_emailed_to_admins(): void
    {
        Notification::fake();
        $admin = $this->staff('admin');
        $super = $this->staff('super-admin');

        $this->actingAs($super)
            ->put('/system/partner-funding', $this->form + ['current_password' => 'password'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('01104493106399', PartnerFunding::all()['bank_account_number']);
        $this->assertSame(2000, PartnerFunding::minAmount());

        $audit = AuditLog::where('table_name', 'system')->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertSame('partner_funding_changed', $audit->new_values['event'] ?? null);
        $this->assertSame('01104493106399', $audit->new_values['partner_funding']['bank_account_number'] ?? null);
        $this->assertSame($super->id, $audit->user_id);

        Notification::assertSentTo([$admin, $super], StaffAlert::class,
            fn (StaffAlert $n) => str_contains($n->line, '01104493106399'));

        // saving the same values again changes nothing and emails nobody
        Notification::fake();
        $this->actingAs($super)->put('/system/partner-funding', $this->form + ['current_password' => 'password'])->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_the_system_page_shows_the_form_only_to_the_super_admin(): void
    {
        $this->actingAs($this->staff('super-admin'))->get('/system')
            ->assertOk()->assertSee('Partner funding details')->assertSee('Save partner funding details');

        // /system is super-admin only (permission:access system settings)
        $this->actingAs($this->staff('admin'))->get('/system')->assertForbidden();
        $this->actingAs($this->staff('admin'))->put('/system/update', [])->assertForbidden();
        $this->actingAs($this->staff('manager'))->get('/system')->assertForbidden();
    }

    public function test_bad_values_are_refused(): void
    {
        $this->actingAs($this->staff('super-admin'))
            ->put('/system/partner-funding', ['mpesa_paybill' => '12', 'bank_swift' => 'BAD', 'current_password' => 'password'] + $this->form)
            ->assertSessionHasErrorsIn('partnerFunding', ['mpesa_paybill', 'bank_swift']);
        $this->assertSame([], PartnerFunding::saved());
    }

    private function staff(string $role): User
    {
        if (! Role::where('name', 'super-admin')->exists()) {
            $this->seed(\Database\Seeders\RoleSeeder::class);
        }
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
