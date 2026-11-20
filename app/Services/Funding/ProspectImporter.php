<?php

namespace App\Services\Funding;

use App\Models\Funding\FundingProspect;
use App\Models\Funding\Suppression;
use App\Models\User;

/**
 * Loads business prospects from a CSV (header row): name, segment, email, phone, town, county, website, contact_name,
 * contact_role, source, source_url, need, est_need. Every row needs a name, a known segment and a lawful source
 * (config funding_crm.sources). Skips duplicates by email or by name and town. Suppressed addresses are kept but
 * marked opted out, so they are never emailed.
 */
class ProspectImporter
{
    /** @return array{added: int, skipped: array<int, string>} */
    public function import(string $path, ?User $owner = null, bool $dryRun = false): array
    {
        $fh = fopen($path, 'r');
        if (! $fh) {
            throw new \RuntimeException('Cannot read ' . $path);
        }
        $head = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($fh) ?: []);
        if (! in_array('name', $head, true) || ! in_array('source', $head, true) || ! in_array('segment', $head, true)) {
            throw new \DomainException('The CSV needs at least the columns name, segment and source.');
        }
        $added = 0;
        $skipped = [];
        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $r = array_map(fn ($v) => trim((string) $v), array_combine($head, array_pad(array_slice($row, 0, count($head)), count($head), '')));
            $email = strtolower($r['email'] ?? '');
            $why = match (true) {
                $r['name'] === '' => 'no name',
                ! config('funding_crm.segments.' . $r['segment']) => 'unknown segment "' . $r['segment'] . '"',
                ! config('funding_crm.sources.' . $r['source']) => 'source "' . $r['source'] . '" is not a lawful public source we accept',
                $email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL) => 'bad email',
                $email !== '' && FundingProspect::where('email', $email)->exists() => 'already have ' . $email,
                FundingProspect::where('name', $r['name'])->where('town', $r['town'] ?? null)->exists() => 'already have ' . $r['name'],
                default => null,
            };
            if ($why) {
                $skipped[$line] = $why;

                continue;
            }
            if (! $dryRun) {
                FundingProspect::create([
                    'name' => mb_substr($r['name'], 0, 255), 'segment' => $r['segment'], 'email' => $email ?: null, 'phone' => ($r['phone'] ?? '') ?: null,
                    'town' => ($r['town'] ?? '') ?: null, 'county' => ($r['county'] ?? '') ?: null, 'website' => ($r['website'] ?? '') ?: null,
                    'contact_name' => ($r['contact_name'] ?? '') ?: null, 'contact_role' => ($r['contact_role'] ?? '') ?: null,
                    'source' => $r['source'], 'source_url' => ($r['source_url'] ?? '') ?: null,
                    'need' => config('funding_crm.needs.' . ($r['need'] ?? '')) ? $r['need'] : config('funding_crm.segments.' . $r['segment'] . '.need'),
                    'est_need' => is_numeric($r['est_need'] ?? null) ? $r['est_need'] : null,
                    'owner_id' => $owner?->id, 'opted_out_at' => $email && Suppression::has($email) ? now() : null,
                ]);
            }
            $added++;
        }
        fclose($fh);

        return ['added' => $added, 'skipped' => $skipped];
    }
}
