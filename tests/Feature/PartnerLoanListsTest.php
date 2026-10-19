<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * G6: a partner sees loans funded through an investment and loans brought in as a broker as two lists.
 */
class PartnerLoanListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_funded_and_brokered_loans_are_listed_separately(): void
    {
        Role::findOrCreate('partner', 'web');
        $user = User::factory()->create();
        $user->assignRole('partner');
        $partner = Partner::create(['name' => 'Broker Partner', 'email' => 'bp@example.test', 'user_id' => $user->id]);

        $investmentId = DB::table('investments')->insertGetId([
            'name' => 'Lending line', 'type' => 'other', 'country' => 'Kenya', 'initial_amount' => 100000,
            'purchase_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('investment_fundings')->insert([
            'investment_id' => $investmentId, 'partner_id' => $partner->id, 'amount_committed' => 50000,
            'amount_disbursed' => 5000, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $funded = $this->loan(['amount' => 5111]);
        DB::table('disbursements')->insert([
            'loan_id' => $funded->id, 'investment_id' => $investmentId, 'amount' => 5111, 'mode' => 'mpesa',
            'disburse_date' => now()->toDateString(), 'payment_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $brokered = $this->loan(['amount' => 7222, 'partner_id' => $partner->id]);
        $stranger = $this->loan(['amount' => 9333]);

        $this->actingAs($user)->get('/account/partner/loans')
            ->assertOk()->assertSee('Funded · 1')->assertSee('Brokered · 1')->assertSee('5,111.00')->assertDontSee('7,222.00');
        $this->actingAs($user)->get('/account/partner/loans?list=brokered')
            ->assertOk()->assertSee('Brokered loans')->assertSee('7,222.00')->assertDontSee('5,111.00');

        $this->actingAs($user)->get("/account/partner/loans/{$funded->id}")->assertOk();
        $this->actingAs($user)->get("/account/partner/loans/{$brokered->id}")->assertOk();
        $this->actingAs($user)->get("/account/partner/loans/{$stranger->id}")->assertNotFound();
    }

    private function loan(array $attributes): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);

        return Loan::create($attributes + [
            'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId, 'original_amount' => $attributes['amount'],
            'borrow_date' => now()->toDateString(), 'status' => 'disbursed', 'broker_status' => 0,
            'reason' => 'School fees for the new term', 'consent' => true, 'consent_date' => now(), 'cycle' => 1,
        ]);
    }
}
