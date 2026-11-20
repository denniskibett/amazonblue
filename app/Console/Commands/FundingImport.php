<?php

namespace App\Console\Commands;

use App\Services\Funding\ProspectImporter;
use Illuminate\Console\Command;

class FundingImport extends Command
{
    protected $signature = 'funding:import {path : CSV with name, segment, source, ...} {--dry-run : Check the file and report, add nothing}';

    protected $description = 'Load business prospects for the funding CRM from a CSV (lawful public sources only)';

    public function handle(ProspectImporter $importer): int
    {
        $r = $importer->import($this->argument('path'), null, (bool) $this->option('dry-run'));
        $this->info(($this->option('dry-run') ? 'Would add ' : 'Added ') . $r['added'] . ' prospects; skipped ' . count($r['skipped']) . '.');
        foreach (array_slice($r['skipped'], 0, 30, true) as $line => $why) {
            $this->line("  line $line: $why");
        }

        return self::SUCCESS;
    }
}
