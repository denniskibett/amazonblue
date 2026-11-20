<?php

namespace App\Console\Commands;

use App\Models\Funding\FundingCampaign;
use App\Models\User;
use App\Services\Funding\Outreach;
use Illuminate\Console\Command;

class FundingSendCampaign extends Command
{
    protected $signature = 'funding:send-campaign {campaign : id or AIM-C- reference} {--as= : user id who sends} {--limit= : at most this many (capped by FUNDING_CRM_BATCH)} {--dry-run}';

    protected $description = 'Send the next batch of a funding campaign from sales@sharet.africa';

    public function handle(Outreach $outreach): int
    {
        $c = FundingCampaign::where('id', $this->argument('campaign'))->orWhere('reference', $this->argument('campaign'))->firstOrFail();
        $eligible = Outreach::eligible($c->segment)->count();
        if ($this->option('dry-run')) {
            $this->info($c->reference . ': ' . $eligible . ' prospects can be emailed now.');

            return self::SUCCESS;
        }
        $by = User::findOrFail($this->option('as') ?: $c->created_by);
        $r = $outreach->runCampaign($c, $by, $this->option('limit') ? (int) $this->option('limit') : null);
        $this->info($c->reference . ': sent ' . $r['sent'] . ', failed ' . count($r['failed']) . ', ' . max(0, $eligible - $r['sent']) . ' left.');

        return self::SUCCESS;
    }
}
