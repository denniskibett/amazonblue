<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * KYC: three selfies (straight, left, right) are required, and the documents step comes last
 * (personal → income → contacts → documents → review).
 */
class KycSelfiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RoleSeeder::class);
    }

    public function test_documents_step_needs_three_selfies_and_then_goes_to_review(): void
    {
        $user = $this->borrower();
        $docs = [
            'id_type' => 'national_id',
            'id_front' => UploadedFile::fake()->image('front.jpg', 1000, 640),
            'id_back' => UploadedFile::fake()->image('back.jpg', 1000, 640),
            'photo' => UploadedFile::fake()->image('photo.jpg', 600, 600),
            'signature_file' => UploadedFile::fake()->image('sig.png', 600, 200),
        ];

        $this->actingAs($user)->post('/kyc/documents', $docs)
            ->assertSessionHasErrors(['selfie_center', 'selfie_left', 'selfie_right']);
        $this->assertNull($user->fresh()->selfie_center_path);

        $this->actingAs($user)->post('/kyc/documents', $docs + [
            'selfie_center' => UploadedFile::fake()->image('c.jpg', 720, 960),
            'selfie_left' => UploadedFile::fake()->image('l.jpg', 720, 960),
            'selfie_right' => UploadedFile::fake()->image('r.jpg', 720, 960),
        ])->assertSessionHasNoErrors()->assertRedirect(route('kyc.review'));

        $user->refresh();
        foreach (['selfie_center_path', 'selfie_left_path', 'selfie_right_path'] as $column) {
            $this->assertStringStartsWith('selfies/' . $user->id . '_', $user->{$column});
            Storage::disk('local')->assertExists($user->{$column});
        }
        $this->actingAs($user)->get("/files/users/{$user->id}/selfie-left")->assertOk();
    }

    public function test_documents_is_the_last_step_before_review(): void
    {
        $user = $this->borrower();

        $this->actingAs($user)->get('/kyc/documents')->assertOk()
            ->assertSee('Selfie: looking straight')->assertSee('Selfie: head turned left')->assertSee('Selfie: head turned right')
            ->assertSee('capture="user"', false)
            ->assertSee('href="' . route('kyc.contacts') . '">Back', false);
        $this->actingAs($user)->get('/kyc/income')->assertOk()
            ->assertSee('href="' . route('kyc.personal') . '">Back', false);
        $this->actingAs($user)->get('/kyc/review')->assertOk()
            ->assertSeeInOrder(['Personal details', 'Income &amp; payout', 'Next of kin &amp; guarantor', 'Identity documents'], false);
    }

    public function test_staff_can_paste_in_a_missing_selfie(): void
    {
        $customer = $this->borrower();
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)->get("/users/{$customer->id}")->assertOk()->assertSee('Selfie: turned left missing');
        $this->actingAs($manager)->postJson("/users/{$customer->id}/documents/selfie-left", ['file' => UploadedFile::fake()->image('l.jpg')])->assertOk();
        $this->assertStringStartsWith('selfies/', $customer->fresh()->selfie_left_path);
    }

    private function borrower(): User
    {
        $user = User::factory()->create(['kyc_status' => null]);
        $user->assignRole('borrower');

        return $user;
    }
}
