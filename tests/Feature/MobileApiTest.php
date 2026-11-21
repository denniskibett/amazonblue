<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Mobile test app API (owner, 11 Nov 2026): /api/mobile/v1 with a "Mobile app" key and a token per phone. */
class MobileApiTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\ClearsKycHold;

    private string $key;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearKycHold();
        config(['mobile.enabled' => true]);
        [, $this->key] = ApiKey::issue('Test APK', null, 'mobile');
        Role::findOrCreate('borrower', 'web');
        $this->user = User::factory()->create(['email' => 'wanjiru@example.test', 'phone' => '0712345678', 'password' => bcrypt('secret-pass'), 'kyc_status' => 'approved']);
        $this->user->assignRole('borrower');
    }

    private function api(): self
    {
        return $this->withHeaders(['X-Api-Key' => $this->key, 'X-Install-Id' => 'install-1']);
    }

    private function signIn(): string
    {
        return $this->api()->postJson('/api/mobile/v1/auth/login', ['login' => '+254712345678', 'password' => 'secret-pass', 'install_id' => 'install-1', 'device_name' => 'Tecno Spark 20'])
            ->assertOk()->json('token');
    }

    public function test_only_a_mobile_key_opens_the_mobile_api(): void
    {
        $this->getJson('/api/mobile/v1/ping')->assertUnauthorized();
        [, $other] = ApiKey::issue('Accounting', null, 'system');
        $this->withHeaders(['X-Api-Key' => $other])->getJson('/api/mobile/v1/ping')->assertUnauthorized();
        $this->api()->getJson('/api/mobile/v1/ping')->assertOk()->assertJson(['ok' => true]);
        // and the mobile key does not open the other systems' API
        $this->flushHeaders()->withToken($this->key)->getJson('/api/v1/ping')->assertUnauthorized();
    }

    public function test_the_switch_keeps_it_closed(): void
    {
        config(['mobile.enabled' => false]);
        $this->api()->postJson('/api/mobile/v1/auth/login', ['login' => 'wanjiru@example.test', 'password' => 'secret-pass', 'install_id' => 'x'])->assertStatus(503);
    }

    public function test_sign_in_by_phone_gives_one_token_per_phone_and_logout_ends_it(): void
    {
        $this->api()->postJson('/api/mobile/v1/auth/login', ['login' => 'wanjiru@example.test', 'password' => 'wrong', 'install_id' => 'install-1'])->assertUnprocessable();
        $this->signIn();
        $token = $this->signIn();
        $this->assertSame(1, $this->user->tokens()->count());

        $this->api()->withToken($token)->getJson('/api/mobile/v1/me')->assertOk()
            ->assertJsonPath('user.first_name', $this->user->first_name ?? explode(' ', $this->user->name)[0])
            ->assertJsonPath('verified', true);
        $this->api()->withToken($token)->postJson('/api/mobile/v1/auth/logout')->assertOk();
        $this->assertSame(0, $this->user->tokens()->count());
    }

    public function test_the_app_gets_its_own_photo_and_the_open_loans_balance(): void
    {
        $token = $this->signIn();
        $this->api()->withToken($token)->getJson('/api/mobile/v1/me')->assertOk()->assertJsonPath('user.has_photo', false);
        $this->api()->withToken($token)->get('/api/mobile/v1/me/photo')->assertNotFound();
        $this->flushHeaders()->get('/api/mobile/v1/me/photo')->assertUnauthorized();

        $loan = $this->loan($this->user, 'disbursed');
        DB::table('loan_cycles')->insert(['loan_id' => $loan->id, 'cycle_number' => 1, 'previous_balance' => 0, 'principal' => 5000, 'interest_capitalized' => 1000,
            'new_balance' => 6000, 'interest_rate' => 20, 'penalty_rate' => 10, 'start_date' => now()->subDays(3)->toDateString(), 'due_date' => now()->addDays(7)->toDateString(), 'status' => 'active']);
        $me = $this->api()->withToken($token)->getJson('/api/mobile/v1/me')->assertOk();
        $this->assertSame($loan->id, $me->json('open_loan.id'));
        $this->assertNotNull($me->json('open_loan.due_date'));
        $this->assertArrayHasKey('progress', $me->json('open_loan'));
    }

    public function test_a_token_without_the_key_is_refused(): void
    {
        $token = $this->signIn();
        $this->flushHeaders()->withToken($token)->getJson('/api/mobile/v1/me')->assertUnauthorized();
    }

    public function test_loans_are_the_borrowers_own_and_a_blocked_application_says_why(): void
    {
        $token = $this->signIn();
        $mine = $this->loan($this->user, 'disbursed');
        $other = $this->loan(User::factory()->create(), 'disbursed');

        $this->api()->withToken($token)->getJson('/api/mobile/v1/loans')->assertOk()->assertJsonCount(1, 'loans')->assertJsonPath('loans.0.id', $mine->id);
        $this->api()->withToken($token)->getJson('/api/mobile/v1/loans/' . $other->id)->assertNotFound();
        $this->api()->withToken($token)->postJson('/api/mobile/v1/loans/' . $other->id . '/repay/claim', ['code' => 'QJK8X2LM4P', 'amount' => 10])->assertNotFound();

        $before = Loan::count();
        $this->api()->withToken($token)->postJson('/api/mobile/v1/loans', ['amount' => 1000, 'consent' => true])
            ->assertStatus(422)->assertJsonStructure(['title', 'message']);
        $this->assertSame($before, Loan::count());
    }

    public function test_the_phone_is_noted_only_as_far_as_the_borrower_agreed(): void
    {
        $token = $this->signIn();
        $this->api()->withToken($token)->postJson('/api/mobile/v1/devices', ['install_id' => 'install-1', 'platform' => 'android',
            'model' => 'Tecno Spark 20', 'os_version' => '14', 'app_version' => '0.1.0', 'consents' => ['device' => true, 'area' => false]])->assertOk();
        $this->assertDatabaseHas('mobile_devices', ['user_id' => $this->user->id, 'model' => 'Tecno Spark 20']);

        $this->api()->withToken($token)->postJson('/api/mobile/v1/devices/sighting', ['event' => 'signed_in', 'area' => 'Kitengela'])->assertOk();
        $this->assertSame([null], DB::table('device_sightings')->pluck('area')->all());

        $this->api()->withToken($token)->postJson('/api/mobile/v1/devices', ['install_id' => 'install-1', 'platform' => 'android', 'model' => 'Tecno Spark 20',
            'consents' => ['device' => false, 'area' => true]])->assertOk();
        $this->assertDatabaseHas('mobile_devices', ['user_id' => $this->user->id, 'model' => null]);
        $this->api()->withToken($token)->postJson('/api/mobile/v1/devices/sighting', ['event' => 'paid', 'area' => 'Kitengela'])->assertOk();
        $this->assertDatabaseHas('device_sightings', ['event' => 'paid', 'area' => 'Kitengela']);
    }

    public function test_the_download_page_shows_without_a_file_and_serves_one_when_uploaded(): void
    {
        config(['mobile.apk.file' => 'phpunit-test.apk']);
        $path = \App\Http\Controllers\MobileAppController::path();
        @unlink($path);
        $this->get('/app')->assertRedirect(route('site.white-label') . '#app');
        $this->get('/white-label')->assertOk()->assertSee('White label &amp; API', false)->assertSee('not up yet')->assertSee('Never');
        $this->get('/app/download')->assertNotFound();

        @mkdir(dirname($path), 0775, true);
        file_put_contents($path, 'not really an apk');
        try {
            $this->get('/white-label')->assertOk()->assertSee('Download for Android')->assertSee(hash('sha256', 'not really an apk'));
            $this->get('/app/download')->assertOk()->assertHeader('Content-Type', 'application/vnd.android.package-archive');
        } finally {
            @unlink($path);
        }
    }

    public function test_staff_put_the_apk_up_from_the_api_keys_page_and_take_it_down(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('super-admin');
        config(['mobile.apk.file' => 'phpunit-upload.apk']);
        $path = \App\Http\Controllers\MobileAppController::path();
        $meta = \App\Http\Controllers\MobileAppController::metaPath();
        $keepMeta = is_file($meta) ? file_get_contents($meta) : null;
        @unlink($path);

        try {
            $this->get('/')->assertSee('White label &amp; API', false);
            $fake = \Illuminate\Http\UploadedFile::fake()->createWithContent('notes.txt', 'hello');
            $this->actingAs($staff)->post(route('staff.api-keys.apk'), ['apk' => $fake, 'version' => '0.1.1'])->assertSessionHasErrors('apk');
            $this->assertFileDoesNotExist($path);

            $apk = \Illuminate\Http\UploadedFile::fake()->createWithContent('app-release.apk', "PK\x03\x04 pretend apk");
            $this->actingAs($staff)->post(route('staff.api-keys.apk'), ['apk' => $apk, 'version' => '0.1.1', 'notes' => 'Faster sign in.'])
                ->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertFileExists($path);
            $this->actingAs($staff)->get(route('staff.api-keys'))->assertOk()->assertSee('Version 0.1.1');
            $this->get('/white-label')->assertOk()->assertSee('0.1.1')->assertSee('Faster sign in.')->assertSee(hash('sha256', "PK\x03\x04 pretend apk"));
            $this->get('/app/download')->assertOk()->assertHeader('Content-Disposition', 'attachment; filename=aimzone-test-0.1.1.apk');
            $this->get(route('site.page', 'contact'))->assertSee('White label &amp; API', false);

            // the same from the server, for files bigger than a web upload (owner, 15 Nov 2026)
            $big = tempnam(sys_get_temp_dir(), 'apk');
            file_put_contents($big, "PK\x03\x04 bigger build");
            $this->artisan('mobile:apk', ['file' => $big, '--app-version' => '0.1.2', '--notes' => 'From the server'])->assertSuccessful();
            $this->get('/white-label')->assertSee('0.1.2')->assertSee('From the server')->assertSee('test version for invited testers');
            @unlink($big);
            $notApk = tempnam(sys_get_temp_dir(), 'txt');
            file_put_contents($notApk, 'hello');
            $this->artisan('mobile:apk', ['file' => $notApk, '--app-version' => '0.1.3'])->assertFailed();
            @unlink($notApk);
            $this->actingAs($staff)->delete(route('staff.api-keys.apk.remove'))->assertSessionHas('success');
            $this->assertFileDoesNotExist($path);
            $this->get('/white-label')->assertSee('not up yet');
            $this->assertDatabaseHas('audit_logs', ['table_name' => 'mobile_apk', 'action' => 'delete']);
        } finally {
            @unlink($path);
            $keepMeta === null ? @unlink($meta) : file_put_contents($meta, $keepMeta);
        }
    }

    private function loan(User $user, string $status): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId(['name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10]);

        return Loan::create(['user_id' => $user->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000,
            'borrow_date' => now()->subDays(3)->toDateString(), 'status' => $status, 'broker_status' => 0,
            'reason' => 'Stock for the shop', 'consent' => true, 'consent_date' => now(), 'cycle' => 1]);
    }
}
