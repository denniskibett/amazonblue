<?php

namespace App\Support;

/**
 * Structured data (schema.org JSON-LD) for the public website. Only facts already published on the site:
 * the name, the Nairobi office, phone and email from the footer, and the loan terms printed on the product pages.
 * No ratings, reviews or prices that are not on the page.
 */
class Seo
{
    public const NAME = 'Aimzone Ventures';

    public const ALTERNATE_NAME = 'Sharet Loans';

    public const PHONE = '+254701607959';

    public const EMAIL = 'hello@aimzoneventures.com';

    /** Public website pages that may be indexed; everything else answers with X-Robots-Tag: noindex. */
    public const PUBLIC_ROUTES = ['home', 'site.page', 'robots', 'sitemap'];

    /** One <script type="application/ld+json"> block; safe inside HTML (no "</script>" can break out). */
    public static function script(array $data): string
    {
        $json = json_encode(
            ['@context' => 'https://schema.org'] + $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    public static function organizationId(): string
    {
        return url('/') . '#organization';
    }

    public static function address(): array
    {
        return [
            '@type' => 'PostalAddress',
            'streetAddress' => 'The Atrium, 1st Floor, Lenana Road',
            'addressLocality' => 'Nairobi',
            'addressCountry' => 'KE',
        ];
    }

    /** Organization, part of Sharet Africa Ltd. */
    public static function organization(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => self::organizationId(),
            'name' => self::NAME,
            'alternateName' => self::ALTERNATE_NAME,
            'url' => url('/'),
            'logo' => asset('assets/images/logo/icon-512.png'),
            'email' => self::EMAIL,
            'telephone' => self::PHONE,
            'address' => self::address(),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'telephone' => self::PHONE,
                'email' => self::EMAIL,
                'areaServed' => 'KE',
                'availableLanguage' => 'en',
            ],
            'parentOrganization' => [
                '@type' => 'Organization',
                'name' => 'Sharet Africa Ltd',
                'url' => 'https://sharet.africa',
            ],
        ];
    }

    /** The lender as a FinancialService (home and the bike loan pages). */
    public static function financialService(?string $description = null): array
    {
        return array_filter([
            '@type' => 'FinancialService',
            '@id' => url('/') . '#lender',
            'name' => self::NAME,
            'alternateName' => self::ALTERNATE_NAME,
            'url' => url('/'),
            'description' => $description,
            'image' => asset('assets/images/og-aimzone.jpg'),
            'telephone' => self::PHONE,
            'email' => self::EMAIL,
            'address' => self::address(),
            'areaServed' => ['@type' => 'Country', 'name' => 'Kenya'],
            'currenciesAccepted' => 'KES',
            'parentOrganization' => ['@id' => self::organizationId()],
        ]);
    }

    /**
     * The logbook loan (asset finance against a motorbike or e-bike) on the terms printed on /asset-finance:
     * up to 70% of the forced sale value, up to 3 months, you keep using the bike.
     */
    public static function logbookLoan(string $name, string $url, string $description): array
    {
        return [
            '@type' => 'LoanOrCredit',
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'provider' => ['@id' => self::organizationId()],
            'areaServed' => ['@type' => 'Country', 'name' => 'Kenya'],
            'currency' => 'KES',
            'loanTerm' => ['@type' => 'QuantitativeValue', 'maxValue' => 3, 'unitCode' => 'MON'],
            'requiredCollateral' => 'Logbook of the motorbike or e-bike, with comprehensive insurance',
        ];
    }

    /** @param array<int, array{0: string, 1: string}> $faqs question, answer (plain text) */
    public static function faqPage(array $faqs): array
    {
        return [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq[1]],
            ], array_values($faqs)),
        ];
    }

    /** @param array<string, string> $trail name => absolute url, in order, home first */
    public static function breadcrumbs(array $trail): array
    {
        $items = [];
        $i = 0;
        foreach ($trail as $name => $url) {
            $items[] = ['@type' => 'ListItem', 'position' => ++$i, 'name' => $name, 'item' => $url];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
