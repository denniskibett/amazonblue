<?php

namespace Tests\Feature;

use App\Services\BorrowerAnalytics;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Behaviour profiles and the amount bands (owner, 23 Oct 2026). Pure rules: no database. */
class BorrowerAnalyticsTest extends TestCase
{
    private function loan(float $amount, string $outcome, int $daysLate, string $sentOn): object
    {
        $d = Carbon::parse($sentOn);

        return (object) ['taken' => true, 'principal' => $amount, 'asked' => $amount, 'outcome' => $outcome, 'days_late' => $daysLate,
            'sent_on' => $d, 'weekday' => (int) $d->isoWeekday(), 'paid_cash' => 0.0, 'approved' => true, 'rejected' => false];
    }

    public function test_amounts_fall_into_the_right_band(): void
    {
        $this->assertSame(0, BorrowerAnalytics::band(5000 - 0.01));
        $this->assertSame(1, BorrowerAnalytics::band(5000));
        $this->assertSame(6, BorrowerAnalytics::band(1_000_000));
    }

    public function test_a_reliable_borrower_gets_a_higher_suggested_limit_capped_at_good_standing(): void
    {
        config(['lending.first_time_limit' => 5000, 'lending.good_standing_limit' => 50000]);
        $p = BorrowerAnalytics::profile(collect([
            $this->loan(10000, 'on_time', 0, '2026-06-01'), $this->loan(20000, 'on_time', 0, '2026-07-01'), $this->loan(30000, 'on_time', 0, '2026-08-03'),
        ]));
        $this->assertSame(100.0, $p['on_time_rate']);
        $this->assertSame(30000.0, $p['comfort_amount']);
        $this->assertGreaterThanOrEqual(80, $p['score']);
        $this->assertSame(37500, $p['suggested_limit']);    // 1.25 × 30,000
        $this->assertContains('Never late', array_column($p['flags'], 0));
    }

    public function test_weekend_loans_that_go_late_are_flagged_and_bad_history_stays_on_the_starter_limit(): void
    {
        config(['lending.first_time_limit' => 5000, 'lending.good_standing_limit' => 50000]);
        $p = BorrowerAnalytics::profile(collect([
            $this->loan(10000, 'on_time', 0, '2026-06-02'),   // Tuesday
            $this->loan(10000, 'late', 12, '2026-06-12'),     // Friday
            $this->loan(10000, 'late', 20, '2026-07-04'),     // Saturday
            $this->loan(10000, 'written_off', 0, '2026-08-02'), // Sunday
        ]));
        $this->assertSame(5000, $p['suggested_limit']);
        $texts = array_column($p['flags'], 0);
        $this->assertContains('Has a loan that went bad', $texts);
        $this->assertContains('Pays late on Fri–Sun loans: avoid weekend lending', $texts);
    }

    public function test_the_behaviour_limit_only_ever_lowers_the_ladder_limit(): void
    {
        config(['lending.first_time_limit' => 5000, 'lending.good_standing_limit' => 50000, 'lending.risk_limit_caps' => []]);
        $credit = app(\App\Services\CreditAssessment::class);
        $factors = ['last_outcome' => 'on_time', 'ever_written_off' => false, 'ever_in_recovery' => false];
        $this->assertSame(20000, $credit->limit([], null, $factors + ['behaviour_limit' => 20000], null, 20)['limit']);
        $this->assertSame('behaviour', $credit->limit([], null, $factors + ['behaviour_limit' => 20000], null, 20)['capped_by']);
        $this->assertSame(50000, $credit->limit([], null, $factors + ['behaviour_limit' => 90000], null, 20)['limit']);
        $this->assertSame(50000, $credit->limit([], null, $factors + ['behaviour_limit' => null], null, 20)['limit']);
    }
}
