<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Support\Currency;
use App\Support\FxRates;
use App\Support\Money;
use App\Support\PageMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Display currency (20 Nov 2026): one rate source (Sharet's FX rates), the rate date shown, the last known rate
 * when Sharet is down, nothing guessed, and stored amounts never touched.
 */
class DisplayCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['currency.rates.url' => 'https://fx.example.test/fx/rates', 'currency.rates.store' => storage_path('framework/testing/fx-rates.json')]);
        @unlink(config('currency.rates.store'));
        Cache::flush();
    }

    private function sharetRates(): void
    {
        Http::fake(['fx.example.test/*' => Http::response(['data' => ['date' => '2026-11-20', 'pairs' => ['KES/USD' => '0.0077', 'KES/UGX' => '28.65']]])]);
    }

    public function test_totals_convert_at_sharets_rate_with_the_date_shown(): void
    {
        $this->sharetRates();
        config(['currency.display' => 'USD']);
        $this->assertSame('USD', Currency::display());
        $this->assertSame('USD 7,700.00', Money::show(1000000));
        $this->assertSame('UGX 28,650', Money::show(1000, 'KES', 'UGX'));
        $this->assertSame('KES 1,000,000.00', Money::format(1000000));
        $this->assertEqualsWithDelta(28.65 / 0.0077, FxRates::rate('USD', 'UGX'), 0.001);
        $this->assertSame("Shown in USD at Sharet's rate of 20 Nov 2026 (1 KES = 0.0077 USD).", Money::note());
        $this->assertSame('USD 7,700.00', PageMetrics::shown(1000000));
        $this->assertSame('KES 1,000,000.00', PageMetrics::kes(1000000), 'cash counts stay in KES');
        $t = Money::total([[1000000, 'KES'], [100, 'USD'], [5, 'XYZ']], 'USD');
        $this->assertEquals(7800.0, $t['amount']);
        $this->assertSame(['XYZ'], $t['missing'], 'an unknown currency is left out, never read as KES');
    }

    public function test_when_sharet_is_down_the_last_known_rate_is_used_and_flagged(): void
    {
        Http::fakeSequence('fx.example.test/*')->push(['data' => ['date' => '2026-11-20', 'pairs' => ['KES/USD' => '0.0077']]])->push('down', 503)->push('down', 503);
        config(['currency.display' => 'USD']);
        FxRates::refresh();
        Cache::flush();
        $this->assertSame('USD 77.00', Money::show(10000));
        $this->assertStringContainsString('last known rate', Money::note());
    }

    public function test_with_no_rate_at_all_figures_stay_in_kes(): void
    {
        Http::fake(['fx.example.test/*' => Http::response('down', 503)]);
        config(['currency.display' => 'USD']);
        $this->assertSame('KES 10,000.00', Money::show(10000));
        $this->assertStringContainsString('No Sharet rate', Money::note());
        config(['currency.display' => 'XYZ']);
        $this->assertSame('KES', Currency::display(), 'an unknown code falls back to the book currency');
    }

    public function test_changing_the_display_currency_never_changes_stored_amounts(): void
    {
        $this->sharetRates();
        $before = \Illuminate\Support\Facades\DB::table('loans')->sum('amount');
        config(['currency.display' => 'USD']);
        Money::show(5000);
        PageMetrics::shown(5000);
        $this->assertSame($before, \Illuminate\Support\Facades\DB::table('loans')->sum('amount'));
        $this->assertSame('KES', config('currency.book'));
    }
}
