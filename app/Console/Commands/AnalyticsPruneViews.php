<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Site analytics retention (owner, 1 Nov 2026): deletes page_views rows older than lending.analytics.keep_days
 * (180 by default), in small chunks so the table is never locked for long. --dry-run counts only.
 */
class AnalyticsPruneViews extends Command
{
    protected $signature = 'analytics:prune-views {--dry-run : Count what would be deleted, delete nothing}';

    protected $description = 'Delete site analytics page views past the retention period (lending.analytics.keep_days)';

    public function handle(): int
    {
        if (! Schema::hasTable('page_views')) {
            $this->info('No page_views table yet; nothing to prune.');

            return self::SUCCESS;
        }
        $days = max(1, (int) config('lending.analytics.keep_days', 180));
        $cutoff = now()->subDays($days);
        $count = DB::table('page_views')->where('occurred_at', '<', $cutoff)->count();
        if (! $this->option('dry-run')) {
            do {
                $deleted = DB::table('page_views')->where('occurred_at', '<', $cutoff)->orderBy('id')->limit(5000)->delete();
            } while ($deleted > 0);
        }
        $this->info(($this->option('dry-run') ? '[dry run] would delete ' : 'Deleted ') . $count . " page view(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
