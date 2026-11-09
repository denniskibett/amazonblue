<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\DebtRecoveryCase;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use App\Support\NavMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Header search (owner, 3 Nov 2026; grouped like a quick switcher, 9 Nov 2026).
 * GET /staff/search?q= → { results: [{ group, label, sub, url, icon }] }
 *
 * "Go to": the pages of Support\NavMenu this person can open (the sidebar's own permission checks). Then records,
 * each group only with the permission its page needs and linking to that page: loans, people, partners, recovery
 * cases, businesses, investments, open requests and payouts, capital prospects. Staff only; brokers and
 * customers get nothing (they never see this header). Five per group; one broken group never breaks the box.
 */
class SearchController extends Controller
{
    private const PER_GROUP = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = trim((string) $request->input('q', ''));
        if (! $user->isStaff()) {
            return $this->json([]);
        }
        $results = $this->pages($user, $q);

        if (mb_strlen($q) >= 2) {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $digits = preg_replace('/\D/', '', $q);
            $phone = strlen($digits) >= 4 ? '%'.substr($digits, -9).'%' : null;
            // a record number: 547, LN-00547, #547
            $number = preg_match('/^(?:#|[A-Z]{2,3}-?)?0*(\d{1,9})$/i', $q, $m) ? (int) $m[1] : null;
            $person = function ($w) use ($like, $phone) {
                $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('id_number', 'like', $like);
                if ($phone) {
                    $w->orWhere('phone', 'like', $phone);
                }
            };
            $kes = fn ($n) => 'KES '.number_format((float) $n);
            $words = fn ($s) => ucfirst(str_replace(['_', '-'], ' ', (string) $s));
            $loanNo = fn ($id) => 'LN-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);

            $groups = [
                // loans by number or by the borrower's name, phone, email or ID
                [$user->can('view loans'), 'Loans', 'briefcase', fn () => Loan::query()->with('user:id,name')
                    ->where(fn ($w) => $w->when($number, fn ($n) => $n->whereKey($number))->orWhereHas('user', $person))
                    ->orderByRaw('id = ? desc', [$number ?? 0])->latest('id')->limit(self::PER_GROUP)->get()
                    ->map(fn ($l) => ['Loan '.$loanNo($l->id), ($l->user->name ?? '—').' · '.$kes($l->amount).' · '.$words($l->status), route('loans.show', $l)])],

                [$user->can('view users'), 'People', 'user', fn () => User::query()->where($person)
                    ->orderBy('name')->limit(self::PER_GROUP)->get(['id', 'name', 'email', 'phone', 'role'])
                    ->map(fn ($p) => [$p->name, trim($words($p->role).' · '.($p->phone ?: $p->email), ' ·'), route('users.show', $p)])],

                [$user->can('view partners'), 'Partners', 'coins', fn () => Partner::query()
                    ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('company_name', 'like', $like)->orWhere('email', 'like', $like)
                        ->orWhere('handle', 'like', $like)->when($phone, fn ($p) => $p->orWhere('phone', 'like', $phone)))
                    ->orderBy('name')->limit(self::PER_GROUP)->get(['id', 'name', 'type', 'status', 'phone', 'email'])
                    ->map(fn ($p) => [$p->name, trim($words($p->type).' partner · '.($p->phone ?: $p->email ?: $words($p->status)), ' ·'), route('partners.show', $p)])],

                [$user->can('view recovery cases'), 'Recovery cases', 'shield', fn () => DebtRecoveryCase::query()->with('user:id,name')
                    ->where(fn ($w) => $w->where('case_number', 'like', $like)->when($number, fn ($n) => $n->orWhere('loan_id', $number))->orWhereHas('user', $person))
                    ->latest('id')->limit(self::PER_GROUP)->get(['id', 'user_id', 'loan_id', 'case_number', 'total_debt_amount'])
                    ->map(fn ($c) => [$c->case_number ?: 'Case #'.$c->id, ($c->user->name ?? '—').' · '.$kes($c->total_debt_amount), route('cases.show', $c->id)])],

                [$user->can('view users'), 'Businesses', 'building', fn () => Business::query()
                    ->where(fn ($w) => $w->where('legal_name', 'like', $like)->orWhere('trading_name', 'like', $like)
                        ->orWhere('registration_number', 'like', $like)->orWhere('kra_pin', 'like', $like))
                    ->orderBy('legal_name')->limit(self::PER_GROUP)->get()
                    ->map(fn ($b) => [$b->displayName(), trim(($b->registration_number ?: '').' · '.$words($b->status), ' ·'), route('business-reviews.show', $b)])],

                [$user->can('view investments'), 'Investments', 'layers', fn () => Investment::query()
                    ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('company_name', 'like', $like))
                    ->orderBy('name')->limit(self::PER_GROUP)->get(['id', 'name', 'type', 'status'])
                    ->map(fn ($i) => [$i->name, trim($words($i->type).' · '.$words($i->status), ' ·'), route('investments.show', $i)])],

                // the Requests page lists open requests only, to who may decide them
                [$user->canAny(['rollover loans', 'edit partners']), 'Borrower requests', 'mail', fn () => DB::table('borrower_requests as r')->join('users as u', 'u.id', '=', 'r.user_id')
                    ->where('r.status', 'open')->whereNull('u.deleted_at')
                    ->where(fn ($w) => $w->where('u.name', 'like', $like)->when($number, fn ($n) => $n->orWhere('r.loan_id', $number)))
                    ->orderBy('r.id')->limit(self::PER_GROUP)->get(['r.id', 'r.type', 'r.loan_id', 'u.name'])
                    ->map(fn ($r) => [$words($r->type).' · '.$r->name, ($r->loan_id ? 'Loan '.$loanNo($r->loan_id).' · ' : '').'Open request', route('staff.requests')])],

                // partner money waiting (as the sidebar's Partner Payouts): the same page, also needs partner statements
                [$user->canAny(['rollover loans', 'edit partners']) && $user->can('view partner statements'), 'Partner payouts & funding', 'hand-coins', fn () => DB::table('partner_requests as r')->join('partners as p', 'p.id', '=', 'r.partner_id')
                    ->whereIn('r.status', ['pending', 'prepared'])
                    ->where(fn ($w) => $w->where('p.name', 'like', $like)->orWhere('r.reference', 'like', $like))
                    ->orderBy('r.id')->limit(self::PER_GROUP)->get(['r.id', 'r.type', 'r.amount', 'r.status', 'p.name'])
                    ->map(fn ($r) => [($r->type === 'withdrawal' ? 'Payout' : $words($r->type)).' · '.$r->name, $kes($r->amount).' · '.$words($r->status), route('staff.requests')])],

                // admins see every manager's prospects, an investment manager only their own (as on the Capital page)
                [$user->can('manage investor pipeline'), 'Capital prospects', 'trending-up', fn () => DB::table('investor_prospects')
                    ->when(! $user->hasAnyRole(['super-admin', 'admin']), fn ($w) => $w->where('manager_id', $user->id))
                    ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('contact', 'like', $like))
                    ->orderBy('name')->limit(self::PER_GROUP)->get(['id', 'name', 'stage', 'ticket'])
                    ->map(fn ($p) => [$p->name, (CapitalController::STAGES[$p->stage] ?? $words($p->stage)).($p->ticket ? ' · '.$kes($p->ticket) : ''), route('staff.capital')])],
            ];

            foreach ($groups as [$allowed, $group, $icon, $query]) {
                if (! $allowed) {
                    continue;
                }
                try {
                    foreach ($query() as [$label, $sub, $url]) {
                        $results[] = compact('group', 'label', 'sub', 'url', 'icon');
                    }
                } catch (\Throwable $e) {
                    report($e); // one broken group must not break the search box
                }
            }
        }

        return $this->json($results);
    }

    /** The sidebar's pages (and module tabs) this person can open, matched on the page or its section. */
    private function pages(User $user, string $q): array
    {
        $needle = mb_strtolower($q);
        $out = [];
        foreach (NavMenu::groups($user, false) as $section => $items) {
            foreach ($items as $i) {
                $rows = isset($i['tabs']) ? array_map(fn ($t) => [$t[0], $t[1], $i[0]], $i['tabs']) : [[$i[0], $i[1], $section]];
                foreach ($rows as [$label, $route, $sub]) {
                    $url = route($route);
                    if (isset($out[$url]) || ($needle !== '' && ! str_contains(mb_strtolower($label.' '.$sub), $needle))) {
                        continue;
                    }
                    $out[$url] = ['group' => 'Go to', 'label' => $label, 'sub' => $sub, 'url' => $url, 'icon' => $i[2]];
                }
            }
        }

        return array_slice(array_values($out), 0, $q === '' ? 8 : 6);
    }

    private function json(array $results): JsonResponse
    {
        return response()->json(['results' => $results])->header('Cache-Control', 'private, no-store');
    }
}
