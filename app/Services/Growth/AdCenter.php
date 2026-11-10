<?php

namespace App\Services\Growth;

/**
 * The local ad center on the Growth page: what to push (from the hub's numbers), ready ad copy that matches what
 * this site really offers, and the pages a UTM link can point to.
 *
 * Truthful copy only (owner, 10 Nov 2026): logbook loans against a motorbike or e-motorcycle the rider owns, on the
 * terms published on /asset-finance (up to 70% of the forced sale value, up to 3 months, keep riding), and emergency
 * cash to M-Pesa with no rate quoted. Financing to BUY a new bike is not open yet: no ad may promise it.
 */
class AdCenter
{
    public const MIN_VISITS = 100;

    public const MIN_CONVERSIONS = 10;

    /** a page or source needs this many visits before its rate is compared */
    public const MIN_ROW_VISITS = 20;

    /** @return array{enough: bool, notes: array<int, array{tone: string, title: string, text: string}>} */
    public function suggestions(?array $site): array
    {
        if (! $site) {
            return ['enough' => false, 'notes' => []];
        }
        $t = $site['totals'];
        $enough = $t['visits'] >= self::MIN_VISITS && $t['conversions'] >= self::MIN_CONVERSIONS;
        $notes = [];
        if (! $enough) {
            $notes[] = ['tone' => 'info', 'title' => 'Not enough data yet',
                'text' => 'There were ' . number_format($t['visits']) . ' visits and ' . number_format($t['conversions']) . ' conversions in this period. Comparisons need at least ' . self::MIN_VISITS . ' visits and ' . self::MIN_CONVERSIONS . ' conversions, so treat the hints below as early signs only.'];
        }

        $rated = fn (array $rows) => collect($rows)->filter(fn ($r) => (int) ($r['visits'] ?? 0) >= self::MIN_ROW_VISITS);

        $page = $rated($site['landing_pages'])->filter(fn ($r) => (int) ($r['conversions'] ?? 0) > 0)->sortByDesc('conversion_rate')->first();
        if ($page) {
            $notes[] = ['tone' => 'up', 'title' => 'Send ads to ' . $page['path'],
                'text' => 'It turns ' . $this->pct($page['conversion_rate'] ?? null) . ' of its ' . number_format($page['visits']) . ' visits into a conversion, the best of the landing pages with enough traffic.'];
        }

        $source = $rated($site['sources'])->filter(fn ($r) => (int) ($r['conversions'] ?? 0) > 0)->sortByDesc('conversion_rate')->first();
        if ($source) {
            $label = trim(($source['source'] ?? 'direct') . (! empty($source['medium']) ? ' / ' . $source['medium'] : '') . (! empty($source['campaign']) ? ' · ' . $source['campaign'] : ''));
            $notes[] = ['tone' => 'up', 'title' => 'Spend more on ' . $label,
                'text' => 'It converts ' . $this->pct($source['conversion_rate'] ?? null) . ' of ' . number_format($source['visits']) . ' visits.'];
        }

        foreach ($rated($site['landing_pages'])->filter(fn ($r) => (int) ($r['conversions'] ?? 0) === 0)->sortByDesc('visits')->take(3) as $dead) {
            $notes[] = ['tone' => 'down', 'title' => $dead['path'] . ' gets traffic but no conversions',
                'text' => number_format($dead['visits']) . ' visits and nobody signed up, applied or enquired. Check the call to action on that page, or stop sending paid traffic there.'];
        }

        if (count($notes) === ($enough ? 0 : 1)) {
            $notes[] = ['tone' => 'info', 'title' => 'Nothing stands out yet',
                'text' => 'No landing page or source has ' . self::MIN_ROW_VISITS . '+ visits to compare. Tag your ads with the link builder below so each one shows up here.'];
        }

        return ['enough' => $enough, 'notes' => $notes];
    }

    /** Pages a campaign may land on: name => absolute URL. */
    public function pages(): array
    {
        return [
            'Boda boda loans' => route('site.page', 'boda-boda-loans'),
            'Electric bike loans' => route('site.page', 'electric-bike-loans'),
            'Motorbike loans in Kenya' => route('site.page', 'motorbike-loans-kenya'),
            'Bike loan requirements' => route('site.page', 'bike-loan-requirements'),
            'Asset finance' => route('site.page', 'asset-finance'),
            'Emergency solutions' => route('site.page', 'emergency-solutions'),
            'Home page' => route('home'),
            'Create an account' => route('register'),
            'Enquiry: bike finance' => route('site.page', 'contact') . '?topic=bike',
        ];
    }

    /** utm_* added to one of our own pages; anything else gives null. */
    public function link(string $page, string $source, string $medium, string $campaign, string $content = ''): ?string
    {
        if (! in_array($page, $this->pages(), true)) {
            return null;
        }
        $clean = fn ($v) => trim(preg_replace('/[^a-z0-9_.-]+/', '-', strtolower(trim($v))), '-');
        $utm = array_filter([
            'utm_source' => $clean($source), 'utm_medium' => $clean($medium),
            'utm_campaign' => $clean($campaign), 'utm_content' => $clean($content),
        ], fn ($v) => $v !== '');
        if (! isset($utm['utm_source'], $utm['utm_medium'], $utm['utm_campaign'])) {
            return null;
        }

        return $page . (str_contains($page, '?') ? '&' : '?') . http_build_query($utm);
    }

    /**
     * Ready ad copy per channel. Google: headlines up to 30 characters, descriptions up to 90 (checked by tests).
     *
     * @return array<string, array{label: string, landing: string, utm: array, parts: array<string, array<int, string>|string>}>
     */
    public function copy(): array
    {
        $boda = route('site.page', 'boda-boda-loans');
        $ebike = route('site.page', 'electric-bike-loans');

        return [
            'google' => [
                'label' => 'Google Search',
                'landing' => $boda,
                'utm' => ['google', 'cpc', 'boda-logbook'],
                'parts' => [
                    'Keywords' => ['boda boda loans', 'motorbike logbook loan', 'logbook loan kenya', 'e-bike loan kenya', 'emergency loan mpesa'],
                    'Headlines' => [
                        'Boda Boda Logbook Loans',
                        'Keep Riding While You Repay',
                        'Up to 70% of Your Bike Value',
                        'E-Motorcycle Logbook Loans',
                        'Emergency Cash to M-Pesa',
                        'Apply Online in Minutes',
                        'Valuation Usually in 48 Hrs',
                    ],
                    'Descriptions' => [
                        'Borrow against your motorbike logbook: up to 70% of its forced sale value.',
                        'Repay over up to 3 months, monthly or at the end. The bike stays with you.',
                        'Verify once online, then apply for emergency cash straight to your M-Pesa.',
                    ],
                    'Negative keywords' => ['buy motorbike on loan', 'hire purchase', 'bike deposit', 'new boda boda'],
                ],
            ],
            'meta' => [
                'label' => 'Facebook & Instagram',
                'landing' => $boda,
                'utm' => ['facebook', 'paid-social', 'boda-logbook'],
                'parts' => [
                    'Primary text' => 'Your bike is your business. Borrow against its logbook, up to 70% of its forced sale value, and keep riding while you repay over up to 3 months. Need something smaller? Verify once and apply for emergency cash to M-Pesa.',
                    'Headline' => 'Boda boda logbook loans in Kenya',
                    'Description' => 'Keep riding while you repay',
                    'Button' => 'Apply now',
                ],
            ],
            'tiktok' => [
                'label' => 'TikTok',
                'landing' => $ebike,
                'utm' => ['tiktok', 'paid-social', 'ebike-logbook'],
                'parts' => [
                    'Ad text' => 'Riding electric? Your e-motorcycle logbook can get you a loan, and you keep riding. Up to 70% of the bike\'s forced sale value.',
                    'Video idea' => 'A rider parks, shows the logbook on a phone, the valuer checks the bike, the rider rides off. Caption: "Keep riding while you repay."',
                    'Button' => 'Learn more',
                ],
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'landing' => route('site.page', 'bike-loan-requirements'),
                'utm' => ['whatsapp', 'share', 'bike-requirements'],
                'parts' => [
                    'Message' => 'Do you ride a boda boda or an e-motorcycle? Aimzone Ventures lends against your bike\'s logbook (up to 70% of its forced sale value, up to 3 months) and you keep riding. Emergency cash to M-Pesa too. What you need and how to apply:',
                ],
            ],
        ];
    }

    /** Lines every ad must respect (shown on the page). */
    public function rules(): array
    {
        return [
            'Never promise financing to buy a new motorbike or e-bike: it is not open yet. Point those people to the bike enquiry.',
            'Quote only the published logbook terms: up to 70% of the forced sale value, up to 3 months, valuation usually within 48 hours.',
            'No interest rate or loan amount for the emergency cash loan in ads: the offer in the app shows it before signing.',
            'No "guaranteed approval", "no checks" or "no CRB": every applicant is verified.',
        ];
    }

    private function pct(?float $v): string
    {
        return $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.') . '%';
    }
}
