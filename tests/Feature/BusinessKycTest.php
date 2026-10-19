<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Business KYC: a verified borrower submits a business; staff approve it (Concerns\BusinessOnboarding,
 * BusinessReviewController).
 */
class BusinessKycTest extends TestCase
{
    use RefreshDatabase;

    private function borrower(string $kyc = 'approved'): User
    {
        Role::findOrCreate('borrower', 'web');
        $user = User::factory()->create(['kyc_status' => $kyc, 'id_number' => '30111222']);
        $user->assignRole('borrower');

        return $user;
    }

    private function details(array $override = []): array
    {
        return $override + [
            'registration_type' => 'limited_company', 'legal_name' => 'Test Traders Ltd', 'registration_number' => 'PVT-TEST123',
            'kra_pin' => 'P051234567X', 'industry' => 'Wholesale & retail', 'date_registered' => '2019-01-10',
            'address_line_1' => 'Moi Avenue', 'city' => 'Nairobi', 'county' => 'Nairobi', 'phone' => '0712345678', 'your_role' => 'director',
        ];
    }

    public function test_unverified_borrower_cannot_add_a_business(): void
    {
        $user = $this->borrower('submitted');

        $this->actingAs($user)->get('/account/business/new')->assertRedirect('/account/business/start');
        $this->actingAs($user)->post('/account/business/new', $this->details())->assertRedirect('/account/business/start');
        $this->assertSame(0, Business::count());
    }

    public function test_borrower_submits_and_staff_approve(): void
    {
        Storage::fake('local');
        Notification::fake();
        $user = $this->borrower();

        $this->actingAs($user)->post('/account/business/new', $this->details())->assertRedirect();
        $business = Business::firstOrFail();
        $this->assertSame('draft', $business->status);
        $this->assertSame(1, $business->members()->count());

        // ownership can't go over 100%
        $own = $business->members()->first();
        $this->actingAs($user)->post("/account/business/{$business->id}/members", ['member_id' => $own->id, 'role' => 'director', 'ownership_pct' => 70, 'is_signatory' => 1]);
        $this->actingAs($user)->post("/account/business/{$business->id}/members", ['full_name' => 'Jane Doe', 'id_number' => '22334455', 'role' => 'shareholder', 'ownership_pct' => 40])
            ->assertSessionHasErrors('ownership_pct');
        $this->actingAs($user)->post("/account/business/{$business->id}/members", ['full_name' => 'Jane Doe', 'id_number' => '22334455', 'role' => 'shareholder', 'ownership_pct' => 30])
            ->assertSessionHasNoErrors();

        // missing documents are reported; then all required ones
        $this->actingAs($user)->post("/account/business/{$business->id}/documents", ['docs' => ['certificate' => UploadedFile::fake()->create('c.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('docs.cr12');
        $this->actingAs($user)->post("/account/business/{$business->id}/documents", ['docs' => [
            'cr12' => UploadedFile::fake()->create('cr12.pdf', 10, 'application/pdf'),
            'kra_pin_certificate' => UploadedFile::fake()->create('kra.pdf', 10, 'application/pdf'),
        ]])->assertRedirect("/account/business/{$business->id}/financials");

        $this->actingAs($user)->post("/account/business/{$business->id}/financials", ['monthly_revenue' => 300000, 'employees' => 4, 'bank_name' => 'Other', 'bank_other' => 'Test Bank', 'bank_account' => '0123456789'])
            ->assertRedirect("/account/business/{$business->id}/review");
        $this->actingAs($user)->post("/account/business/{$business->id}/review", ['confirm_true' => 1, 'confirm_authorised' => 1, 'confirm_checks' => 1])
            ->assertRedirect("/account/business/{$business->id}/submitted");
        $this->assertSame('submitted', $business->fresh()->status);

        // locked while under review
        $this->actingAs($user)->post("/account/business/{$business->id}/financials", ['monthly_revenue' => 1, 'employees' => 1, 'bank_name' => 'X', 'bank_account' => '1'])
            ->assertSessionHasErrors('business');

        // another borrower sees nothing
        $other = $this->borrower();
        $this->actingAs($other)->get("/account/business/{$business->id}")->assertNotFound();
        $doc = $business->documents()->first();
        $this->actingAs($other)->get("/files/businesses/{$business->id}/{$doc->id}")->assertNotFound();

        // staff: every check is required to approve
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('view users', 'web');
        Permission::findOrCreate('edit users', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo(['view users', 'edit users']);

        $this->actingAs($admin)->get("/business-reviews/{$business->id}")->assertOk();
        $this->actingAs($admin)->post("/business-reviews/{$business->id}/approve", ['checks' => ['registration' => 1]])->assertSessionHasErrors();
        $this->actingAs($admin)->post("/business-reviews/{$business->id}/approve", ['checks' => array_fill_keys(array_keys(Business::CHECKS), 1)])
            ->assertRedirect('/business-reviews');

        $business->refresh();
        $this->assertSame('approved', $business->status);
        $this->assertSame($admin->id, $business->reviewed_by);
        $this->assertNotNull($business->handle);
        $this->assertDatabaseHas('audit_logs', ['table_name' => 'businesses', 'record_id' => $business->id]);
    }
}
