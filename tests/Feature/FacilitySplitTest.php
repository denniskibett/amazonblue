<?php

namespace Tests\Feature;

use App\Models\FacilitySplit;
use App\Models\FacilitySplitParty;
use App\Models\Partner;
use App\Services\Loans\FacilitySplitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A facility's own split (owner, 14 Nov 2026), with the figures agreed for loan 458, the ISIRO facility. */
class FacilitySplitTest extends TestCase
{
    use RefreshDatabase;

    private Partner $house;
    private Partner $isiro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->house = Partner::create(['name' => 'Aimzone (house)', 'email' => 'house@example.test', 'is_house_account' => true]);
        $this->isiro = Partner::create(['name' => 'ISIRO AGENCIES', 'email' => 'isiro@example.test']);
    }

    /** Unsaved, like the command's preview: no payments yet since counted_from. */
    private function split(): FacilitySplit
    {
        $s = new FacilitySplit(['loan_id' => 999999, 'pi_target' => 421632, 'total_cap' => 1220000, 'counted_from' => '2026-11-14']);
        $s->setRelation('parties', collect([
            ['partner_id' => $this->house->id, 'principal' => 200000, 'pi_entitled' => 345600, 'penalty_pct' => 50, 'received_before' => 150000, 'p' => $this->house],
            ['partner_id' => $this->isiro->id, 'principal' => 44000, 'pi_entitled' => 76032, 'penalty_pct' => 50, 'received_before' => 70000, 'p' => $this->isiro],
        ])->map(fn ($a) => (new FacilitySplitParty(collect($a)->except('p')->all()))->setRelation('partner', $a['p'])));

        return $s;
    }

    public function test_the_next_201632_catches_each_side_up_to_its_principal_plus_interest(): void
    {
        $f = app(FacilitySplitter::class);
        $at = $f->cumulative($this->split(), 201632);
        $this->assertEquals([195600.0, 0.0], $at[$this->house->id]);
        $this->assertEquals([6032.0, 0.0], $at[$this->isiro->id]);
    }

    public function test_after_that_the_penalty_is_shared_50_50_up_to_the_total(): void
    {
        $f = app(FacilitySplitter::class);
        $all = $f->cumulative($this->split(), 1000000);
        $this->assertEquals([195600.0, 399184.0], $all[$this->house->id]);
        $this->assertEquals([6032.0, 399184.0], $all[$this->isiro->id]);
        // nothing above the total is shared
        $this->assertEquals($all, $f->cumulative($this->split(), 1100000));
    }

    public function test_a_payment_across_both_bands_posts_principal_interest_and_penalty(): void
    {
        $rows = collect(app(FacilitySplitter::class)->shares($this->split(), 0, 250000))
            ->mapWithKeys(fn ($r) => [$r[0] . '|' . $r[1] => $r[3]]);
        $i = $this->isiro->id;
        $h = $this->house->id;
        // ISIRO: 6,032 of principal plus interest, of which 44,000/76,032 is principal; then half of 48,368 penalty
        $this->assertEquals(3490.74, $rows["$i|principal_returned"]);
        $this->assertEquals(2541.26, $rows["$i|profit_distribution"]);
        $this->assertEquals(24184.0, $rows["$i|penalty_share"]);
        // the house: its interest part of 195,600 (principal is not posted for the house) and the other half of the penalty
        $this->assertEquals(round(195600 - 195600 * 200000 / 345600, 2), $rows["$h|profit_distribution"]);
        $this->assertEquals(24184.0, $rows["$h|penalty_share"]);
        $this->assertArrayNotHasKey("$h|principal_returned", $rows->all());
    }

    public function test_status_shows_what_each_side_still_has_to_come(): void
    {
        $st = collect(app(FacilitySplitter::class)->status($this->split())['parties'])->keyBy('partner_id');
        $this->assertEquals(744784.0, $st[$this->house->id]['entitled']);
        $this->assertEquals(594784.0, $st[$this->house->id]['to_come']);
        $this->assertEquals(475216.0, $st[$this->isiro->id]['entitled']);
        $this->assertEquals(405216.0, $st[$this->isiro->id]['to_come']);
    }
}
