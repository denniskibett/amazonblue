<?php

namespace Tests\Feature\Money;

use App\Models\Partner;
use App\Services\CustomerWallets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The wallets page check "journal row says one wallet, its posting went to another" (owner, 22 Oct 2026:
 * rewritten from one slow JSON join into two reads matched in PHP; this pins the answer).
 */
class JournalSlugMismatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_posting_to_a_different_wallet_than_the_journal_says_is_reported(): void
    {
        $p = Partner::create(['name' => 'ISIRO AGENCIES', 'email' => 'isiro@example.test', 'type' => 'business', 'account_type' => 'principal']);
        $interest = $p->createWallet(['name' => 'Interest', 'slug' => 'interest']);
        $principal = $p->createWallet(['name' => 'Principal', 'slug' => 'principal']);

        // R1: journal says interest, money went to principal (wrong). R2: journal and posting agree (right).
        $principal->deposit(500, ['reference' => 'R1']);
        $interest->deposit(200, ['reference' => 'R2']);
        foreach ([['R1', 500], ['R2', 200]] as [$ref, $amt]) {
            DB::table('partner_transactions')->insert(['partner_id' => $p->id, 'wallet_slug' => 'interest', 'type' => 'credit', 'amount' => $amt,
                'reference' => $ref, 'transaction_date' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }

        $found = app(CustomerWallets::class)->journalSlugMismatches();

        $this->assertCount(1, $found);
        $this->assertSame(['partner' => $p->id, 'journal_says' => 'interest', 'posted_to' => 'principal', 'rows' => 1], array_intersect_key($found[0], array_flip(['partner', 'journal_says', 'posted_to', 'rows'])));
        $this->assertEquals(500, $found[0]['amount']);
    }
}
