<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\AuditTrail;
use App\Support\Integrations;
use Illuminate\Console\Command;

/**
 * Fills the integrations table from the values in force now (.env), encrypted (owner, 3 Nov 2026). Adds missing
 * integrations and missing values only; a value already saved on /staff/integrations is kept unless --force.
 */
class IntegrationsImportEnv extends Command
{
    protected $signature = 'integrations:import-env {--force : Overwrite saved values with the .env ones}';

    protected $description = 'Copy the integration settings from .env into the integrations table (encrypted)';

    public function handle(): int
    {
        Integrations::forget();
        foreach (Integrations::CATALOGUE as $slug => $def) {
            $row = Integration::firstOrNew(['slug' => $slug]);
            $settings = $row->settings ?? [];
            $added = [];
            foreach (empty($def['managed']) ? array_keys($def['fields']) : [] as $env) {
                $value = $this->fromEnv($slug, $env);
                if ($value !== null && ($this->option('force') || ! isset($settings[$env]) || $settings[$env] === '')) {
                    $settings[$env] = $value;
                    $added[] = $env;
                }
            }
            $new = ! $row->exists;
            $row->fill(['name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'], 'logo' => $def['logo'],
                'settings' => $settings, 'enabled' => $row->exists ? $row->enabled : true])->save();
            if ($new || $added) {
                AuditTrail::record('integrations', $row->id, $new ? 'create' : 'update', null, ['event' => 'import_env', 'keys' => $added]);
            }
            $this->line(sprintf('%-18s %s, %d value(s) from .env', $slug, $new ? 'added' : 'kept', count($added)));
        }
        Integrations::forget();

        return self::SUCCESS;
    }

    /** The .env value: read from config before any saved value is applied (config:cache keeps env() out of reach). */
    private function fromEnv(string $slug, string $env): ?string
    {
        $raw = env($env);
        if ($raw !== null) {
            return is_bool($raw) ? ($raw ? '1' : '0') : (string) $raw;
        }

        return Integrations::current($slug, $env);
    }
}
