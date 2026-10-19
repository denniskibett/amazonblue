<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use App\Services\CustomerWallets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * S-P1 (interim until Phase 3): a partner can ask for earnings plus capital that is not lent out, no more.
 */
class PartnerWithdrawCapTest extends TestCase
{
    use RefreshDatabase;

    public function test_capital_lent_out_cannot_be_withdrawn(): void
    {
        Notification::fake();
        [$user, $partner] = $this->partner(capital: 400000, interest: 5000);
        $this->fund($partner, disbursed: 26000);

        $this->actingAs($user)->get('/account/partner/withdraw')
            ->assertOk()->assertSee('Capital not lent out')->assertSee('374,000.00');

        $this->actingAs($user)->post('/account/partner/withdraw', ['wallet' => 'principal', 'amount' => 380000])
            ->assertSessionHasErrors('amount');
        $this->actingAs($user)->post('/account/partner/withdraw', ['wallet' => 'principal', 'amount' => 374000])
            ->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/account/partner/withdraw', ['wallet' => 'interest', 'amount' => 5000])
            ->assertSessionHasNoErrors();
    }

    public function test_available_on_the_partner_home_leaves_out_lent_capital(): void
    {
        [$user, $partner] = $this->partner(capital: 100000, interest: 2000);
        $this->fund($partner, disbursed: 30000, returned: 10000);

        $summary = app(\App\Services\PartnerPortfolio::class)->summary($partner);
        $this->assertSame(20000.0, $summary['lent_out']);
        $this->assertSame(82000.0, $summary['available']);

        $this->actingAs($user)->get('/account/partner')
            ->assertOk()->assertSee('82,000.00')->assertSee('Not counting')->assertSee('newer earnings are being calculated');
    }

    /** @return array{0: User, 1: Partner} */
    private function partner(float $capital, float $interest): array
    {
        Role::findOrCreate('partner', 'web');
        $user = User::factory()->create();
        $user->assignRole('partner');
        $partner = Partner::create(['name' => 'Test Partner', 'email' => 'p' . $user->id . '@example.test', 'user_id' => $user->id, 'phone' => '0712345678']);
        app(CustomerWallets::class)->ensurePartnerWallets($partner);
        $partner->getWallet('principal')->depositFloat($capital);
        $partner->getWallet('interest')->depositFloat($interest);

        return [$user, $partner->fresh()];
    }

    private function fund(Partner $partner, float $disbursed, float $returned = 0): void
    {
        $investmentId = DB::table('investments')->insertGetId([
            'name' => 'Lending line', 'type' => 'other', 'country' => 'Kenya', 'initial_amount' => 100000,
            'purchase_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('investment_fundings')->insert([
            'investment_id' => $investmentId, 'partner_id' => $partner->id, 'amount_committed' => $disbursed,
            'amount_disbursed' => $disbursed, 'amount_returned' => $returned, 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
