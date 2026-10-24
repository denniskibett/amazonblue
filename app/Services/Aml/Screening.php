<?php

namespace App\Services\Aml;

use App\Models\Business;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Support\Facades\DB;

/**
 * AML screening (owner, 23 Oct 2026; switch lending.aml.enabled). No provider is contracted, so screening is:
 *  1. the names (a business and its directors, or a partner) are matched against the sanctions lists staff
 *     uploaded (SanctionsList);
 *  2. staff tick the checklist below and clear any match with a note.
 * The result is kept on the record it belongs to: businesses.checks['aml'] or users.kyc_checks['aml'].
 * When on, a business or a partner can't be approved until the screening is clear. A provider can replace step 1
 * later by returning the same "hits" shape.
 */
class Screening
{
    public const CHECKS = [
        'sanctions' => 'Sanctions lists checked: no match, or every match below is a different person',
        'pep' => 'Not a politically exposed person (or a manager approved a PEP)',
        'adverse_media' => 'Searched the names online: nothing that stops us',
        'source_of_funds' => 'Where the money comes from is believable for them',
    ];

    public static function enabled(): bool
    {
        return (bool) config('lending.aml.enabled');
    }

    /**
     * Listed names that look like this name. A match needs every word of the listed name in this name (or the
     * other way round) with at least two words, or most of the words of both when they share two or more.
     *
     * @return list<array{list: string, name: string, kind: string, reference: ?string, dob: ?string, country: ?string, score: float}>
     */
    public function matches(?string $name): array
    {
        $q = SanctionsList::tokens($name);
        if (! $q) {
            return [];
        }
        $probe = $q;
        usort($probe, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $probe = array_slice(array_filter($probe, fn ($t) => mb_strlen($t) >= 3), 0, 2) ?: [$probe[0]];

        $rows = DB::table('sanctions_entries')->where(function ($w) use ($probe, $q) {
            $w->where('name_key', SanctionsList::key(implode(' ', $q)));
            foreach ($probe as $t) {
                $w->orWhere('name_key', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $t) . '%');
            }
        })->limit(2000)->get();

        $hits = [];
        foreach ($rows as $r) {
            $e = explode(' ', $r->name_key);
            $overlap = count(array_intersect($q, $e));
            $score = $overlap / max(count($q), count($e));
            $subset = (count($e) >= 2 && $overlap === count($e)) || (count($q) >= 2 && $overlap === count($q));
            $single = count($e) === 1 && count($q) === 1 && $overlap === 1;
            if ($subset || $single || ($overlap >= 2 && $score >= 0.66)) {
                $hits[$r->list_name . '|' . ($r->reference ?: $r->id)] = [
                    'list' => $r->list_name, 'name' => $r->name, 'kind' => $r->kind, 'reference' => $r->reference,
                    'dob' => $r->dob, 'country' => $r->country, 'score' => round($subset ? max($score, 0.9) : $score, 2),
                ];
            }
        }
        usort($hits, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_values($hits), 0, 20);
    }

    /** The names to screen for a record: label => name. */
    public function names(Business|User $subject): array
    {
        if ($subject instanceof User) {
            return ['Partner' => $subject->name];
        }
        $names = ['Business' => $subject->legal_name];
        if ($subject->trading_name && $subject->trading_name !== $subject->legal_name) {
            $names['Trading name'] = $subject->trading_name;
        }
        foreach ($subject->members as $m) {
            $names[$m->roleLabel() . ': ' . $m->full_name . ' #' . $m->id] = $m->full_name;
        }

        return $names;
    }

    /** Run the lists over the record's names and keep the result with it (the checklist ticks are kept as they were). */
    public function screen(Business|User $subject, User $by): array
    {
        $lists = app(SanctionsList::class)->lists();
        $hits = [];
        foreach ($this->names($subject) as $label => $name) {
            if ($m = $this->matches($name)) {
                $hits[$label] = $m;
            }
        }
        $aml = array_merge($this->current($subject), [
            'screened_at' => now()->toDateTimeString(), 'screened_by' => $by->id, 'screened_by_name' => $by->name,
            'lists' => $lists->mapWithKeys(fn ($l) => [$l->list_name => (int) $l->names])->all(),
            'hits' => $hits,
        ]);
        $this->store($subject, $aml);
        AuditTrail::record($this->table($subject), $subject->id, 'update', null, ['event' => 'aml_screened', 'names' => count($this->names($subject)), 'hits' => count($hits)]);

        return $aml;
    }

    /** Staff tick the checklist and, when there were matches, say why they are someone else. */
    public function decide(Business|User $subject, array $ticked, ?string $note, User $by): array
    {
        $aml = $this->current($subject);
        if (empty($aml['screened_at'])) {
            throw new \DomainException('Run the screening first.');
        }
        if (! empty($aml['hits']) && mb_strlen(trim((string) $note)) < 10) {
            throw new \DomainException('There were matches: say why they are not this person or business (at least 10 characters).');
        }
        $aml['checks'] = collect(self::CHECKS)->mapWithKeys(fn ($l, $k) => [$k => in_array($k, $ticked, true)])->all();
        $aml['note'] = $note ? trim($note) : null;
        $aml['decided_at'] = now()->toDateTimeString();
        $aml['decided_by'] = $by->id;
        $aml['decided_by_name'] = $by->name;
        $aml['clear'] = ! in_array(false, $aml['checks'], true);
        $this->store($subject, $aml);
        AuditTrail::record($this->table($subject), $subject->id, 'update', null, ['event' => 'aml_decided', 'clear' => $aml['clear'], 'note' => $aml['note']]);

        return $aml;
    }

    public function current(Business|User $subject): array
    {
        $checks = $subject instanceof User ? ($subject->kyc_checks ?? []) : ($subject->checks ?? []);

        return is_array($checks) ? (array) ($checks['aml'] ?? []) : [];
    }

    public function isClear(Business|User $subject): bool
    {
        $aml = $this->current($subject);

        return ! empty($aml['clear']) && ! empty($aml['screened_at']);
    }

    /** Null when the record may be approved, else why not (only when screening is switched on). */
    public function blocker(Business|User $subject): ?string
    {
        if (! self::enabled() || $this->isClear($subject)) {
            return null;
        }

        return empty($this->current($subject)['screened_at'])
            ? 'Run the AML screening and tick the checklist before approving.'
            : 'Finish the AML checklist (every box ticked) before approving.';
    }

    private function store(Business|User $subject, array $aml): void
    {
        if ($subject instanceof User) {
            $checks = is_array($subject->kyc_checks) ? $subject->kyc_checks : [];
            $checks['aml'] = $aml;
            $subject->forceFill(['kyc_checks' => $checks])->saveQuietly();
        } else {
            $checks = is_array($subject->checks) ? $subject->checks : [];
            $checks['aml'] = $aml;
            $subject->forceFill(['checks' => $checks])->saveQuietly();
        }
    }

    private function table(Business|User $subject): string
    {
        return $subject instanceof User ? 'users' : 'businesses';
    }
}
