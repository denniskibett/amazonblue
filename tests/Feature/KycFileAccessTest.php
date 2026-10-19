<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * G1: KYC files (ID, photo, signature, business documents) are for the owner and KYC reviewers only.
 * Roles come from the real RoleSeeder, so a grant added there later is caught here.
 */
class KycFileAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $borrower;

    private Business $business;

    private int $docId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RoleSeeder::class);

        $this->borrower = User::factory()->create(['id_front_path' => 'kyc/id-front.jpg']);
        $this->borrower->assignRole('borrower');
        Storage::disk('local')->put('kyc/id-front.jpg', 'x');

        $this->business = Business::create(['owner_user_id' => $this->borrower->id, 'legal_name' => 'Acme Ltd', 'status' => 'submitted']);
        Storage::disk('local')->put('business/cr12.pdf', 'x');
        $this->docId = $this->business->documents()->create(['type' => 'cr12', 'path' => 'business/cr12.pdf'])->id;
    }

    public function test_broker_gets_404_on_user_and_business_files(): void
    {
        $broker = $this->staff('broker');

        $this->assertFalse($broker->can('view users'));
        $this->actingAs($broker)->get("/files/users/{$this->borrower->id}/id-front")->assertNotFound();
        $this->actingAs($broker)->get("/files/businesses/{$this->business->id}/{$this->docId}")->assertNotFound();
        $this->actingAs($broker)->get('/business-reviews')->assertForbidden();
    }

    public function test_teller_with_view_loans_only_gets_404(): void
    {
        $this->actingAs($this->staff('teller'))->get("/files/users/{$this->borrower->id}/id-front")->assertNotFound();
    }

    public function test_kyc_reviewers_and_the_owner_can_open_the_files(): void
    {
        foreach (['admin', 'manager', 'recovery-officer', 'auditor', 'super-admin'] as $role) {
            $staff = $this->staff($role);
            $this->actingAs($staff)->get("/files/users/{$this->borrower->id}/id-front")->assertOk();
            $this->actingAs($staff)->get("/files/businesses/{$this->business->id}/{$this->docId}")->assertOk();
        }

        $this->actingAs($this->borrower)->get("/files/users/{$this->borrower->id}/id-front")->assertOk();
        $this->actingAs($this->borrower)->get("/files/businesses/{$this->business->id}/{$this->docId}")->assertOk();
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
