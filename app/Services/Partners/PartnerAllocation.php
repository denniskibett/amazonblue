<?php

namespace App\Services\Partners;

use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;

/**
 * Where a partner's money is (owner, 17 Nov 2026), for the Sankey on the partner page: what came in (money they put
 * in, and what it earned) flows through each facility to where it is now: each loan it is still out on, paid to
 * other investors, paid back to them, or free. Per facility, money in + earned = still out + paid to others + paid
 * back + free (FacilityStatement), so every band adds up. A share of a loan without a line is a facility of its own.
 * Also lays the chart out: three columns, node heights to scale, bands as curves.
 */
class PartnerAllocation
{
    public const KINDS = ['out' => 'Still out on a loan', 'paid' => 'Paid back to them', 'other' => 'Paid to other investors', 'free' => 'Free in the facility'];

    public function __construct(private FacilityStatement $statements) {}

    /** @return array{nodes: array, links: array, total: float, height: int} */
    public function for(Partner $partner, int $width = 960, int $maxLoans = 8): array
    {
        $flows = [];   // [from, to, amount]
        $kind = [];    // destination node => kind
        $add = function (string $from, string $to, float $v) use (&$flows) {
            if ($v >= 0.005) {
                $flows[] = [$from, $to, round($v, 2)];
            }
        };
        $lines = Investment::whereIn('return_type', ['loan_share', 'fixed_return'])->whereHas('fundings', fn ($q) => $q->where('partner_id', $partner->id))->orderBy('id')->get();
        $out = [];
        foreach ($lines as $l) {
            $st = $this->statements->for($l);
            $f = 'Facility #' . $l->id;
            $add('Money they put in', $f, (float) $st['rows']->where('kind', 'in')->sum('credit'));
            $add('What it earned', $f, (float) $st['rows']->where('kind', 'earned')->sum('credit'));
            foreach ($st['rows']->where('kind', 'lent')->groupBy('loan_id') as $loanId => $rows) {
                $still = (float) $rows->sum('debit') - (float) $st['rows']->where('kind', 'back')->where('loan_id', $loanId)->sum('credit');
                if ($still >= 0.005) {
                    $out[] = [$f, (int) $loanId, $still];
                }
            }
            $add($f, self::KINDS['other'], (float) $st['rows']->where('kind', 'other')->sum('debit'));
            $add($f, self::KINDS['paid'], (float) $st['rows']->where('kind', 'paid')->sum('debit'));
            $add($f, self::KINDS['free'], max(0, $st['balance']));
        }
        // their share of a loan with no line
        $final = Loan::STATUS_FINAL_LIST;
        foreach (DB::table('loan_fundings as f')->join('loans as l', 'l.id', '=', 'f.loan_id')->where('f.partner_id', $partner->id)->whereNull('f.investment_id')
            ->get(['f.loan_id', 'f.amount', 'f.settled_on', 'l.status']) as $s) {
            $f = 'Loan #' . $s->loan_id . ' share';
            $add('Money they put in', $f, (float) $s->amount);
            if (! $s->settled_on && ! in_array($s->status, $final, true)) {
                $out[] = [$f, (int) $s->loan_id, (float) $s->amount];
            } else {
                $add($f, self::KINDS['paid'], (float) $s->amount);
            }
        }
        // the loans still out: the biggest by name, the rest together
        $byLoan = collect($out)->groupBy(1)->map(fn ($g) => $g->sum(2))->sortDesc();
        $named = $byLoan->keys()->take($maxLoans)->all();
        $more = $byLoan->count() - count($named);
        $names = Loan::with('user:id,name')->whereIn('id', $named ?: [0])->get()->mapWithKeys(fn ($l) => [$l->id => 'Loan #' . $l->id . ($l->user ? ' · ' . $l->user->name : '')]);
        foreach ($out as [$f, $loanId, $v]) {
            $to = in_array($loanId, $named, true) ? ($names[$loanId] ?? 'Loan #' . $loanId) : $more . ' more ' . ($more === 1 ? 'loan' : 'loans');
            $add($f, $to, $v);
            $kind[$to] = 'out';
        }
        foreach (self::KINDS as $k => $label) {
            if ($k !== 'out') {
                $kind[$label] = $k;
            }
        }
        // merge flows between the same two nodes
        $flows = collect($flows)->groupBy(fn ($x) => $x[0] . '|' . $x[1])->map(fn ($g) => [$g[0][0], $g[0][1], round($g->sum(2), 2)])->values()->all();

        return $this->layout($flows, $kind, $width);
    }

    private function layout(array $flows, array $kind, int $width): array
    {
        if (! $flows) {
            return ['nodes' => [], 'links' => [], 'total' => 0.0, 'height' => 0];
        }
        $sources = ['Money they put in', 'What it earned'];
        $col = [];
        foreach ($flows as [$a, $b]) {
            $col[$a] ??= in_array($a, $sources, true) ? 0 : 1;
            $col[$b] = in_array($a, $sources, true) ? 1 : 2;
        }
        $in = $outV = [];
        foreach ($flows as [$a, $b, $v]) {
            $outV[$a] = ($outV[$a] ?? 0) + $v;
            $in[$b] = ($in[$b] ?? 0) + $v;
        }
        $value = fn ($n) => max($in[$n] ?? 0, $outV[$n] ?? 0);
        $cols = [[], [], []];
        foreach ($col as $n => $c) {
            $cols[$c][] = $n;
        }
        // destinations: loans first (biggest first), then paid to others, paid back, free
        $order = ['out' => 0, 'other' => 1, 'paid' => 2, 'free' => 3];
        usort($cols[2], fn ($x, $y) => [$order[$kind[$x] ?? 'out'], -$value($x)] <=> [$order[$kind[$y] ?? 'out'], -$value($y)]);
        usort($cols[0], fn ($x, $y) => array_search($x, $sources) <=> array_search($y, $sources));

        $gap = 14;
        $minH = 3;
        $total = max(array_map(fn ($c) => array_sum(array_map($value, $c)), $cols));
        $maxN = max(array_map('count', $cols));
        $plot = max(220, min(560, 36 * $maxN));
        $scale = ($plot - $gap * ($maxN - 1)) / max($total, 0.01);
        $nodeW = 12;
        $xs = [170, (int) ($width / 2) - 6, $width - 250];
        $nodes = [];
        foreach ($cols as $c => $names) {
            $colH = array_sum(array_map(fn ($n) => max($minH, $value($n) * $scale), $names)) + $gap * (count($names) - 1);
            $y = ($plot - $colH) / 2;
            foreach ($names as $n) {
                $h = max($minH, $value($n) * $scale);
                $nodes[$n] = ['name' => $n, 'col' => $c, 'x' => $xs[$c], 'y' => round($y, 2), 'w' => $nodeW, 'h' => round($h, 2), 'value' => round($value($n), 2),
                    'kind' => $c === 2 ? ($kind[$n] ?? 'out') : ($c === 0 ? 'source' : 'facility'), 'outY' => $y, 'inY' => $y];
                $y += $h + $gap;
            }
        }
        // bands, in the order of their targets so they don't cross more than needed
        usort($flows, fn ($p, $q) => [$nodes[$p[0]]['y'], $nodes[$p[1]]['y']] <=> [$nodes[$q[0]]['y'], $nodes[$q[1]]['y']]);
        $links = [];
        foreach ($flows as [$a, $b, $v]) {
            $t = max(1, $v * $scale);
            $sa = &$nodes[$a];
            $tb = &$nodes[$b];
            $x0 = $sa['x'] + $sa['w'];
            $x1 = $tb['x'];
            $y0 = $sa['outY'];
            $y1 = $tb['inY'];
            $sa['outY'] += $t;
            $tb['inY'] += $t;
            $mx = ($x0 + $x1) / 2;
            $f = fn ($n) => round($n, 2);
            $links[] = ['from' => $a, 'to' => $b, 'value' => $v, 'kind' => $tb['kind'] === 'facility' ? 'in' : $tb['kind'],
                'd' => "M{$f($x0)},{$f($y0)} C{$f($mx)},{$f($y0)} {$f($mx)},{$f($y1)} {$f($x1)},{$f($y1)} L{$f($x1)},{$f($y1 + $t)} C{$f($mx)},{$f($y1 + $t)} {$f($mx)},{$f($y0 + $t)} {$f($x0)},{$f($y0 + $t)} Z"];
            unset($sa, $tb);
        }

        return ['nodes' => array_values($nodes), 'links' => $links, 'total' => round($total, 2), 'height' => (int) ceil($plot)];
    }
}
