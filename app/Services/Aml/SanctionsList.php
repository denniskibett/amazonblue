<?php

namespace App\Services\Aml;

use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The sanctions lists staff upload for AML screening (owner, 23 Oct 2026). One row per listed name in
 * sanctions_entries; an alias is a row of its own with the same reference. Uploading a list replaces that list.
 *
 * Accepted files:
 *  - the UN Security Council consolidated list as XML (scsanctions.un.org › consolidated.xml), read as is
 *  - any CSV with a header row and a name column (name / full_name / whole_name), and optionally aliases
 *    (separated by ;), dob, country (or nationality), reference (or ref / id) and type (individual / entity)
 */
class SanctionsList
{
    /** Name to its match key: lower case, letters and digits only, words sorted (so word order doesn't matter). */
    public static function key(?string $name): string
    {
        $tokens = self::tokens($name);
        sort($tokens);

        return implode(' ', $tokens);
    }

    /** @return list<string> */
    public static function tokens(?string $name): array
    {
        $plain = mb_strtolower(trim((string) $name));
        $plain = strtr($plain, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ï' => 'i', 'ó' => 'o', 'ö' => 'o', 'ô' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', "'" => '']);
        $plain = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $plain);
        // words that say nothing about who it is
        $skip = ['ltd', 'limited', 'co', 'company', 'the', 'and', 'of', 'al', 'el', 'bin', 'ibn', 'mr', 'mrs', 'ms', 'dr'];

        return array_values(array_unique(array_filter(explode(' ', (string) $plain), fn ($t) => mb_strlen($t) > 1 && ! in_array($t, $skip, true))));
    }

    /**
     * Replace a list with the file's entries. Returns how many names were stored.
     *
     * @throws \DomainException when the file can't be read or has no names
     */
    public function import(string $path, string $listName, ?User $by = null): int
    {
        $listName = trim($listName);
        if ($listName === '') {
            throw new \DomainException('Give the list a name, e.g. "UN consolidated".');
        }
        $head = (string) file_get_contents($path, false, null, 0, 512);
        $rows = str_contains(ltrim($head), '<') ? $this->fromUnXml($path) : $this->fromCsv($path);
        if (! $rows) {
            throw new \DomainException('No names found in that file. Upload the UN consolidated XML or a CSV with a "name" column.');
        }

        $now = now();
        $count = DB::transaction(function () use ($rows, $listName, $by, $now) {
            DB::table('sanctions_entries')->where('list_name', $listName)->delete();
            $n = 0;
            foreach (array_chunk($rows, 500) as $chunk) {
                $insert = [];
                foreach ($chunk as $r) {
                    $key = self::key($r['name']);
                    if ($key === '') {
                        continue;
                    }
                    $insert[] = [
                        'list_name' => $listName, 'reference' => mb_substr((string) ($r['reference'] ?? ''), 0, 60) ?: null,
                        'kind' => ($r['kind'] ?? 'individual') === 'entity' ? 'entity' : 'individual',
                        'name' => mb_substr(trim($r['name']), 0, 255), 'name_key' => mb_substr($key, 0, 255),
                        'dob' => mb_substr((string) ($r['dob'] ?? ''), 0, 40) ?: null, 'country' => mb_substr((string) ($r['country'] ?? ''), 0, 80) ?: null,
                        'uploaded_by' => $by?->id, 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                DB::table('sanctions_entries')->insert($insert);
                $n += count($insert);
            }

            return $n;
        });
        AuditTrail::record('sanctions_entries', 0, 'create', null, ['event' => 'list_uploaded', 'list' => $listName, 'names' => $count]);

        return $count;
    }

    /** The lists on file: name, how many names, when uploaded. */
    public function lists(): Collection
    {
        return DB::table('sanctions_entries')->selectRaw('list_name, count(*) as names, max(created_at) as uploaded_at')
            ->groupBy('list_name')->orderBy('list_name')->get();
    }

    public function remove(string $listName): int
    {
        $n = DB::table('sanctions_entries')->where('list_name', $listName)->delete();
        AuditTrail::record('sanctions_entries', 0, 'delete', ['list' => $listName, 'names' => $n], null);

        return $n;
    }

    /** @return list<array{name: string, kind: string, reference: ?string, dob: ?string, country: ?string}> */
    private function fromUnXml(string $path): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($previous);
        if (! $xml) {
            throw new \DomainException('That XML file could not be read.');
        }
        $rows = [];
        $add = function (string $name, string $kind, ?string $ref, ?string $dob, ?string $country) use (&$rows) {
            $name = trim(preg_replace('/\s+/', ' ', $name));
            if ($name !== '') {
                $rows[] = compact('name', 'kind', 'dob', 'country') + ['reference' => $ref];
            }
        };
        foreach ($xml->xpath('//INDIVIDUAL') ?: [] as $p) {
            $name = implode(' ', array_filter([(string) $p->FIRST_NAME, (string) $p->SECOND_NAME, (string) $p->THIRD_NAME, (string) $p->FOURTH_NAME]));
            $ref = (string) ($p->REFERENCE_NUMBER ?: $p->DATAID);
            $dob = (string) ($p->INDIVIDUAL_DATE_OF_BIRTH->DATE ?? '') ?: (string) ($p->INDIVIDUAL_DATE_OF_BIRTH->YEAR ?? '');
            $country = (string) ($p->NATIONALITY->VALUE ?? '');
            $add($name, 'individual', $ref, $dob, $country);
            foreach ($p->INDIVIDUAL_ALIAS ?? [] as $alias) {
                $add((string) $alias->ALIAS_NAME, 'individual', $ref, $dob, $country);
            }
        }
        foreach ($xml->xpath('//ENTITY') ?: [] as $e) {
            $ref = (string) ($e->REFERENCE_NUMBER ?: $e->DATAID);
            $add((string) $e->FIRST_NAME, 'entity', $ref, null, null);
            foreach ($e->ENTITY_ALIAS ?? [] as $alias) {
                $add((string) $alias->ALIAS_NAME, 'entity', $ref, null, null);
            }
        }

        return $rows;
    }

    private function fromCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (! $fh) {
            throw new \DomainException('That file could not be read.');
        }
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), fgetcsv($fh) ?: []);
        $col = function (array $names) use ($header) {
            foreach ($names as $n) {
                $i = array_search($n, $header, true);
                if ($i !== false) {
                    return $i;
                }
            }

            return null;
        };
        $name = $col(['name', 'full_name', 'whole_name', 'full name']);
        if ($name === null) {
            fclose($fh);
            throw new \DomainException('The CSV needs a header row with a "name" column.');
        }
        [$aliases, $dob, $country, $ref, $type] = [$col(['aliases', 'alias']), $col(['dob', 'date_of_birth']), $col(['country', 'nationality']),
            $col(['reference', 'ref', 'id']), $col(['type', 'kind'])];
        $rows = [];
        while (($r = fgetcsv($fh)) !== false) {
            $base = [
                'kind' => $type !== null && str_contains(strtolower((string) ($r[$type] ?? '')), 'entit') ? 'entity' : 'individual',
                'reference' => $ref !== null ? ($r[$ref] ?? null) : null,
                'dob' => $dob !== null ? ($r[$dob] ?? null) : null,
                'country' => $country !== null ? ($r[$country] ?? null) : null,
            ];
            if (trim((string) ($r[$name] ?? '')) !== '') {
                $rows[] = ['name' => $r[$name]] + $base;
            }
            foreach ($aliases !== null ? explode(';', (string) ($r[$aliases] ?? '')) : [] as $alias) {
                if (trim($alias) !== '') {
                    $rows[] = ['name' => $alias] + $base;
                }
            }
        }
        fclose($fh);

        return $rows;
    }
}
