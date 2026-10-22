<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Charts for a report page, read straight from the report's own data (owner, 22 Oct 2026), so every report gets
 * charts that match its numbers instead of a per-chart partial that may not exist:
 *  - a breakdown (name => number) becomes a donut (a few parts) or a bar chart;
 *  - a breakdown of breakdowns (demographics) becomes one chart per part;
 *  - rows with a date become a line chart of their numeric columns;
 *  - a short list of named rows (by product, by channel) becomes a bar chart.
 */
class ReportCharts
{
    private const SKIP = ['summary', 'not_available', 'peak_inflow_day', 'peak_outflow_day', 'performance_metrics'];

    public static function blocks(array $data): array
    {
        $blocks = [];
        if (is_array($data['summary']['by_channel'] ?? null)) {
            $data['by_channel'] = $data['summary']['by_channel'];
        }

        foreach ($data as $key => $value) {
            if (in_array($key, self::SKIP, true)) {
                continue;
            }
            $value = $value instanceof \Illuminate\Support\Collection ? $value->toArray() : $value;
            if (! is_array($value) || $value === []) {
                continue;
            }
            $value = json_decode(json_encode($value), true);

            if (! array_is_list($value)) {
                $value = array_filter($value, fn ($v, $k) => ! preg_match('/rate|yield|percent|satisfaction/', (string) $k), ARRAY_FILTER_USE_BOTH);
                $allArrays = $value !== [] && collect($value)->every(fn ($v) => is_array($v));
                $sameShape = $allArrays && collect($value)->map(fn ($v) => implode('|', array_keys($v)))->unique()->count() === 1;
                if ($sameShape && count($value) > 1) {
                    // rows keyed by id or name (by product, by risk band): name a row by its key when it has no name
                    $value = collect($value)->map(fn ($row, $k) => (collect($row)->contains(fn ($x) => is_string($x) && ! is_numeric($x)) ? [] : ['name' => (string) $k]) + $row)->values()->all();
                } elseif (self::numericMap($value)) {
                    $blocks[] = self::breakdown($key, $value);
                    continue;
                } elseif ($allArrays && collect($value)->every(fn ($v) => self::numericMap($v))) {
                    foreach ($value as $sub => $map) {
                        $blocks[] = self::breakdown($key . '.' . $sub, $map, self::label($sub));
                    }
                    continue;
                } else {
                    continue;
                }
            }

            $first = $value[0];
            if (! is_array($first)) {
                continue;
            }
            $numeric = array_keys(array_filter($first, fn ($v, $k) => is_numeric($v) && ! preg_match('/(^|_)id$|phone|transaction|reference|percent|rate|days|age|year/', (string) $k), ARRAY_FILTER_USE_BOTH));
            // money columns first, and never money next to a count on one axis
            $money = array_values(array_filter($numeric, fn ($k) => preg_match('/amount|balance|borrowed|outstanding|recovered|paid|value|debit|credit|inflow|outflow|net|total/', $k) && ! preg_match('/count|loans$|number|customers$/', $k)));
            if (isset($first['date']) && $numeric) {
                $cols = array_values(array_diff($numeric, ['running_balance'])) ?: $numeric;
                $blocks[] = [
                    'key' => $key, 'label' => self::label($key), 'type' => 'area',
                    'labels' => array_map(fn ($r) => (string) $r['date'], $value),
                    'series' => array_map(fn ($c) => ['name' => self::label($c), 'data' => array_map(fn ($r) => round((float) ($r[$c] ?? 0), 2), $value)], $cols),
                ];
                if (in_array('running_balance', $numeric, true)) {
                    $blocks[] = ['key' => $key . '.running', 'label' => 'Running balance', 'type' => 'area',
                        'labels' => array_map(fn ($r) => (string) $r['date'], $value),
                        'series' => [['name' => 'Running balance', 'data' => array_map(fn ($r) => round((float) $r['running_balance'], 2), $value)]]];
                }
                continue;
            }
            $labelCol = collect($first)->keys()->first(fn ($k) => is_string($first[$k]) && ! is_numeric($first[$k]));
            if ($labelCol && $numeric) {
                $cols = $money ? array_slice($money, 0, 2) : [$numeric[0]];
                $title = self::label($key);
                if (count($value) > 15) {
                    usort($value, fn ($a, $b) => (float) ($b[$cols[0]] ?? 0) <=> (float) ($a[$cols[0]] ?? 0));
                    $value = array_slice($value, 0, 10);
                    $title = $title . ': top 10 by ' . Str::lower(self::label($cols[0]));
                }
                $blocks[] = [
                    'key' => $key, 'label' => $title, 'type' => 'bar',
                    'labels' => array_map(fn ($r) => (string) $r[$labelCol], $value),
                    'series' => array_map(fn ($c) => ['name' => self::label($c), 'data' => array_map(fn ($r) => round((float) ($r[$c] ?? 0), 2), $value)], $cols),
                ];
            }
        }

        return array_values(array_filter($blocks, fn ($b) => collect($b['series'])->flatMap(fn ($s) => $s['data'])->sum() != 0));
    }

    private static function numericMap(array $v): bool
    {
        return $v !== [] && collect($v)->every(fn ($x) => is_numeric($x));
    }

    private static function breakdown(string $key, array $map, ?string $label = null): array
    {
        $labels = array_map(fn ($k) => self::label((string) $k), array_keys($map));
        $values = array_map(fn ($v) => round((float) $v, 2), array_values($map));
        $parts = count($map) <= 6 && min($values) >= 0;

        return ['key' => $key, 'label' => $label ?? self::label($key), 'type' => $parts ? 'donut' : 'bar', 'labels' => $labels,
            'series' => $parts ? [['name' => 'Value', 'data' => $values]] : [['name' => self::label($key), 'data' => $values]]];
    }

    private static function label(string $k): string
    {
        $k = preg_replace('/^by_/', 'by ', $k);
        if (preg_match('/^(\d+)_(\d+|plus)$/', $k, $m)) {
            return $m[2] === 'plus' ? 'Over ' . number_format((int) $m[1]) : number_format((int) $m[1]) . ' to ' . number_format((int) $m[2]);
        }

        return Str::ucfirst(str_replace('_', ' ', $k));
    }
}
