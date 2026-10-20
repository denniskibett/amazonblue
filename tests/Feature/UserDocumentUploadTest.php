<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Staff add a missing passport photo / ID / signature on the user page (paste, drop or choose a file),
 * and a user with no photo gets initials drawn by the app, not by an outside service.
 */
class UserDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RoleSeeder::class);
    }

    public function test_staff_with_edit_users_can_add_a_missing_document(): void
    {
        $customer = $this->user('borrower');
        $manager = $this->user('manager');

        $this->actingAs($manager)->get("/users/{$customer->id}")->assertOk()
            ->assertSee('ID front missing')->assertSee('Front of the national ID')->assertSee('paste');

        $this->actingAs($manager)->postJson("/users/{$customer->id}/documents/id-front", ['file' => UploadedFile::fake()->image('id.jpg', 800, 500)])
            ->assertOk()->assertJsonPath('success', true);

        $path = $customer->fresh()->id_front_path;
        $this->assertStringStartsWith('id_cards/' . $customer->id . '_', $path);
        Storage::disk('local')->assertExists($path);
        $this->actingAs($manager)->get("/files/users/{$customer->id}/id-front")->assertOk();
        $this->assertDatabaseHas('audit_logs', ['table_name' => 'users', 'record_id' => $customer->id, 'action' => 'update']);

        // replacing removes the old file
        $this->actingAs($manager)->postJson("/users/{$customer->id}/documents/id-front", ['file' => UploadedFile::fake()->image('id2.png')])->assertOk();
        Storage::disk('local')->assertMissing($path);

        // the photo goes to the KYC photo column
        $this->actingAs($manager)->postJson("/users/{$customer->id}/documents/photo", ['file' => UploadedFile::fake()->image('me.webp')])->assertOk();
        $this->assertStringStartsWith('avatars/', $customer->fresh()->avatar);
    }

    public function test_only_images_and_only_staff_who_can_edit_users(): void
    {
        $customer = $this->user('borrower');

        $this->actingAs($this->user('manager'))
            ->postJson("/users/{$customer->id}/documents/signature", ['file' => UploadedFile::fake()->create('sig.pdf', 10, 'application/pdf')])
            ->assertStatus(422)->assertJsonValidationErrors('file');

        foreach (['teller', 'broker', 'borrower'] as $role) {
            $this->actingAs($this->user($role))
                ->postJson("/users/{$customer->id}/documents/signature", ['file' => UploadedFile::fake()->image('s.png')])
                ->assertForbidden();
        }
        $this->assertNull($customer->fresh()->signature);
    }

    public function test_no_photo_means_local_initials(): void
    {
        $user = User::factory()->create(['name' => 'Émile wanjiru Kamau']);

        $this->assertSame('ÉW', $user->getInitials());
        $uri = $user->getAvatarUrl();
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        $this->assertStringContainsString('>ÉW</text>', base64_decode(substr($uri, strlen('data:image/svg+xml;base64,'))));
        $this->assertStringNotContainsString('ui-avatars', $uri);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
