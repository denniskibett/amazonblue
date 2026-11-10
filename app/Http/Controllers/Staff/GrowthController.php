<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Growth\AdCenter;
use App\Services\Growth\GrowthHub;
use Illuminate\Http\Request;

/**
 * Growth (10 Nov 2026): this site's visits, conversions, channels and SEO checks from the Sharet growth hub, plus a
 * small ad center (what to push, ready copy, a UTM link builder). Read-only; same access as the other reports.
 */
class GrowthController extends Controller
{
    public function index(Request $request, GrowthHub $hub, AdCenter $ads)
    {
        abort_unless($request->user()->can('view reports'), 403);

        $days = (int) $request->query('days', 30);
        $days = in_array($days, GrowthHub::PERIODS, true) ? $days : 30;
        $summary = $hub->summary($days);

        $pages = $ads->pages();
        $builder = [
            'page' => (string) $request->query('page', reset($pages)),
            'source' => (string) $request->query('source', ''),
            'medium' => (string) $request->query('medium', ''),
            'campaign' => (string) $request->query('campaign', ''),
            'content' => (string) $request->query('content', ''),
        ];
        $built = $request->filled('source')
            ? $ads->link($builder['page'], $builder['source'], $builder['medium'], $builder['campaign'], $builder['content'])
            : null;

        return view('staff.growth.index', [
            'days' => $days,
            'summary' => $summary,
            'site' => $summary['site'],
            'push' => $ads->suggestions($summary['site']),
            'copy' => collect($ads->copy())->map(fn ($c) => $c + ['link' => $ads->link($c['landing'], ...$c['utm'])])->all(),
            'rules' => $ads->rules(),
            'pages' => $pages,
            'builder' => $builder,
            'built' => $built,
            'builderError' => $request->filled('source') && ! $built,
        ]);
    }
}
