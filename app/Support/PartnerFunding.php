<?php

namespace App\Support;

use App\Helpers\SystemHelper;
use App\Models\Partner;
use App\Models\System;
use App\Services\AuditTrail;
use Illuminate\Support\Facades\DB;

/**
 * Where partners send money (account › Partner › Add funds). One place to read it from:
 * each value comes from system.settings.partner_funding (edited on /system by the super-admin) when filled,
 * else from config('lending.partner_funding') (.env / defaults). A value empty in both shows the screen's
 * placeholder note. Changing these redirects partner money, so every save is audited and emailed to admins.
 */
class PartnerFunding
{
    /** Editable keys, in form order. */
    public const KEYS = [
        'bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch', 'bank_swift',
        'mpesa_paybill', 'mpesa_account', 'reference_prefix', 'min_amount',
    ];

    public static function all(): array
    {
        $saved = self::saved();
        $config = (array) config('lending.partner_funding', []);
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = filled($saved[$key] ?? null) ? $saved[$key] : ($config[$key] ?? null);
        }
        $values['min_amount'] = (int) ($values['min_amount'] ?: 1000);
        $values['reference_prefix'] = $values['reference_prefix'] ?: 'AZP-';

        return $values;
    }

    public static function minAmount(): int
    {
        return self::all()['min_amount'];
    }

    /** What the partner quotes when sending money, e.g. AZP-0009. */
    public static function reference(Partner $partner): string
    {
        return self::all()['reference_prefix'] . str_pad((string) $partner->id, 4, '0', STR_PAD_LEFT);
    }

    /** Values saved on /system only (no config fallback); [] when none. */
    public static function saved(): array
    {
        try {
            $settings = SystemHelper::settings()->settings ?? [];
        } catch (\Throwable) {
            return [];
        }

        return (array) (is_array($settings) ? ($settings['partner_funding'] ?? []) : []);
    }

    /**
     * Replace the saved values; an empty field is stored as null so the config value applies again.
     * Returns ['changed' => keys, 'old' => values, 'new' => values] for the changed keys only.
     */
    public static function save(array $input): array
    {
        $new = [];
        foreach (self::KEYS as $key) {
            $value = $input[$key] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $new[$key] = filled($value) ? ($key === 'min_amount' ? (int) $value : $value) : null;
        }

        return DB::transaction(function () use ($new) {
            $system = System::query()->lockForUpdate()->first() ?? System::settings();
            $settings = is_array($system->settings) ? $system->settings : [];
            $old = (array) ($settings['partner_funding'] ?? []);
            $changed = array_values(array_filter(self::KEYS, fn ($key) => (string) ($old[$key] ?? '') !== (string) ($new[$key] ?? '')));
            if (! $changed) {
                return ['changed' => [], 'old' => [], 'new' => []];
            }

            $settings['partner_funding'] = $new;
            $system->settings = $settings;
            $system->save();
            SystemHelper::clearCache();

            $only = array_flip($changed);
            $result = [
                'changed' => $changed,
                'old' => array_intersect_key(array_replace(array_fill_keys(self::KEYS, null), $old), $only),
                'new' => array_intersect_key($new, $only),
            ];
            AuditTrail::record('system', $system->id, 'update',
                ['partner_funding' => $result['old']],
                ['partner_funding' => $result['new'], 'event' => 'partner_funding_changed']);

            return $result;
        });
    }
}
