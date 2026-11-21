<?php

namespace Tests\Feature;

use App\Models\KinConfirmation;
use App\Models\User;
use App\Services\Kyc\KinConfirmations;
use App\Support\PrivateFiles;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Owner, 21 Nov 2026: "and also for kin to prove they're the kin". */
class KinConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private KinConfirmations $kin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.notifications.sms.driver' => 'log']);
        Storage::fake(PrivateFiles::DISK);
        $this->kin = app(KinConfirmations::class);
    }

    private function client(array $attrs = []): User
    {
        $u = User::factory()->create($attrs + ['name' => 'Mary Wanjiku', 'id_number' => '11111111', 'kin_name' => 'Peter Kamau',
            'kin_relation' => 'Sibling', 'kin_phone' => '+254733000222', 'kyc_status' => 'submitted', 'kyc_submitted_at' => now(), 'email_verified_at' => now()]);
        $u->assignRole('borrower');

        return $u;
    }

    private function token(User $u): string
    {
        return Str::afterLast($this->kin->send($this->kin->open($u, null, false)), '/');
    }

    private function staff(): User
    {
        $s = User::factory()->create(['email_verified_at' => now()]);
        $s->assignRole('admin');

        return $s;
    }

    public function test_the_kin_confirms_with_their_id_and_staff_need_not_call(): void
    {
        $u = $this->client();
        $token = $this->token($u);

        $this->get(route('kin.confirm', $token))->assertOk()->assertSee('Do you know Mary Wanjiku?')->assertSee('(sibling)');
        $this->post(route('kin.confirm.store', $token), ['answer' => 'yes', 'relation' => 'Sibling', 'id_number' => '33445566',
            'id_front' => UploadedFile::fake()->image('front.jpg'), 'agree' => '1'])->assertRedirect(route('kin.confirm', $token));

        $row = KinConfirmation::sole();
        $this->assertSame(['confirmed', 'Sibling', '33445566'], [$row->status, $row->stated_relation, $row->id_number]);
        Storage::disk(PrivateFiles::DISK)->assertExists($row->id_front_path);
        $this->get(route('kin.confirm', $token))->assertSee('Thank you, Peter');
        $this->get(route('kin.confirm', $token))->assertSee('This link no longer works');

        $staff = $this->staff();
        $this->actingAs($staff)->get(route('kyc-reviews.show', $u))->assertOk()->assertSee('Confirmed online')->assertSee('33445566')
            ->assertSee('The next of kin confirmed online with their ID');
        $this->actingAs($staff)->get(route('kyc-reviews.kin.file', [$u, 'id-front']))->assertOk();
        // approving needs only the guarantor call now
        $this->actingAs($staff)->post(route('kyc-reviews.approve', $u), ['called_guarantor' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $u->fresh()->kyc_status);
    }

    public function test_yes_needs_an_id_and_the_kin_cannot_use_the_clients_id(): void
    {
        $token = $this->token($this->client());

        $this->post(route('kin.confirm.store', $token), ['answer' => 'yes', 'relation' => 'Sibling'])->assertSessionHasErrors(['id_number', 'id_front', 'agree']);
        $this->post(route('kin.confirm.store', $token), ['answer' => 'yes', 'relation' => 'Sibling', 'id_number' => '11111111',
            'id_front' => UploadedFile::fake()->image('f.jpg'), 'agree' => '1'])->assertSessionHasErrors('kin');
        $this->assertSame('waiting', KinConfirmation::sole()->status);
    }

    public function test_i_dont_know_this_person_is_flagged_to_staff_and_the_call_is_still_needed(): void
    {
        $u = $this->client();
        $token = $this->token($u);
        $this->post(route('kin.confirm.store', $token), ['answer' => 'no'])->assertRedirect();
        $this->assertSame('denied', KinConfirmation::sole()->status);

        $staff = $this->staff();
        $this->actingAs($staff)->get(route('kyc-reviews.show', $u))->assertSee('Said they do NOT know Mary');
        $this->actingAs($staff)->post(route('kyc-reviews.approve', $u), ['called_guarantor' => '1'])->assertSessionHasErrors('called_next_of_kin');
    }

    public function test_submitting_kyc_texts_the_kin_and_staff_can_send_it_again(): void
    {
        $u = $this->client();
        $row = $this->kin->open($u);
        $this->assertNotNull($row->fresh()->token_hash);
        $this->assertNotNull($row->fresh()->link_sent_at);

        $before = $row->fresh()->token_hash;
        $this->actingAs($this->staff())->post(route('kyc-reviews.kin.send', $u))->assertSessionHas('success');
        $this->assertNotSame($before, $row->fresh()->token_hash);
        $this->assertSame(1, KinConfirmation::count());
    }

    public function test_a_changed_kin_phone_starts_afresh(): void
    {
        $u = $this->client();
        $this->post(route('kin.confirm.store', $this->token($u)), ['answer' => 'no']);
        $u->update(['kin_name' => 'Ann Njeri', 'kin_phone' => '+254744000333']);

        $row = $this->kin->open($u, null, false);
        $this->assertSame(['waiting', 'Ann Njeri'], [$row->status, $row->name]);
        $this->assertSame(2, KinConfirmation::count());
    }
}
