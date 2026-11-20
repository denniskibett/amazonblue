<?php

namespace App\Http\Controllers\Funding;

use App\Http\Controllers\Controller;
use App\Models\FinanceApplication;
use App\Models\Funding\FundingActivity;
use App\Models\Funding\FundingCampaign;
use App\Models\Funding\FundingEmail;
use App\Models\Funding\FundingProspect;
use App\Models\Funding\Suppression;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Funding\EmailEngagement;
use App\Services\Funding\Outreach;
use App\Services\Funding\ProspectImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Funding CRM (owner, 20 Nov 2026), after the scharge sales CRM: business prospects that need float or finance, the
 * pipeline, follow-ups, the playbook per segment, cold campaigns from sales@sharet.africa with open/click tracking,
 * and the suppression list. Permission "manage funding prospects". Admins see everyone's; others see their own and
 * the unassigned.
 */
class FundingCrmController extends Controller
{
    public function index(Request $request): View
    {
        $me = $request->user();
        $q = $this->scope($request)
            ->when($request->query('stage'), fn ($q, $s) => $q->where('stage', $s))
            ->when($request->query('segment'), fn ($q, $s) => $q->where('segment', $s))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('reference', 'like', "%$s%")->orWhere('town', 'like', "%$s%")))
            ->with('owner:id,name')
            ->addSelect(['last_open' => DB::table('funding_email_events')->selectRaw('max(occurred_at)')->whereColumn('prospect_id', 'funding_prospects.id')])
            ->orderByRaw("field(stage, 'interested', 'applied', 'contacted', 'new', 'approved', 'won', 'lost')")->orderBy('next_on')->latest('id');
        $emails = DB::table('funding_emails')->whereNotNull('sent_at');

        return view('staff.funding.index', [
            'prospects' => $q->paginate(40)->withQueryString(),
            'stages' => FundingProspect::counts(),
            'total' => FundingProspect::count(),
            'sent' => (clone $emails)->count(),
            'opened' => (clone $emails)->whereNotNull('opened_at')->count(),
            'clicked' => (clone $emails)->whereNotNull('clicked_at')->count(),
            'applications' => FinanceApplication::whereNotNull('prospect_id')->count(),
            'due' => FundingActivity::where('type', 'follow_up')->whereNull('done_at')->where('due_at', '<=', now()->endOfDay())->where('user_id', $me->id)->count(),
            'owners' => $this->owners(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validated($request);
        $d['owner_id'] ??= $request->user()->id;
        if (! empty($d['email']) && FundingProspect::where('email', strtolower($d['email']))->exists()) {
            return back()->withErrors(['email' => 'A prospect with that email is already in the CRM.'])->withInput();
        }
        $p = FundingProspect::create($d + ['need' => config('funding_crm.segments.' . $d['segment'] . '.need')]);
        if ($p->email && Suppression::has($p->email)) {
            $p->update(['opted_out_at' => now()]);
        }
        $p->activities()->create(['user_id' => $request->user()->id, 'type' => 'note', 'title' => 'Added from ' . config('funding_crm.sources.' . $p->source), 'done_at' => now()]);
        AuditTrail::record('funding_prospects', $p->id, 'create', null, ['reference' => $p->reference, 'by' => $request->user()->id]);

        return redirect()->route('staff.funding.show', $p)->with('success', $p->reference . ' added.');
    }

    public function import(Request $request, ProspectImporter $importer): RedirectResponse
    {
        $request->validate(['csv' => 'required|file|mimes:csv,txt|max:5120']);
        try {
            $r = $importer->import($request->file('csv')->getRealPath(), $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['csv' => $e->getMessage()]);
        }
        AuditTrail::record('funding_prospects', 0, 'create', null, ['event' => 'import', 'added' => $r['added'], 'skipped' => count($r['skipped']), 'by' => $request->user()->id]);

        return back()->with('success', 'Added ' . $r['added'] . ' prospects; skipped ' . count($r['skipped']) . ($r['skipped'] ? ' (first: line ' . array_key_first($r['skipped']) . ', ' . reset($r['skipped']) . ')' : '') . '.');
    }

    public function show(Request $request, FundingProspect $prospect): View
    {
        $this->authorizeProspect($request, $prospect);
        $draft = config('funding_crm.segments.' . $prospect->segment);

        return view('staff.funding.show', [
            'p' => $prospect->load('owner'),
            'activities' => $prospect->activities()->with('user:id,name')->latest('id')->get(),
            'emails' => $prospect->emails()->latest('id')->get(),
            'engagement' => EmailEngagement::summary($prospect->id),
            'events' => EmailEngagement::events($prospect->id, 20),
            'application' => $prospect->application_id ? FinanceApplication::find($prospect->application_id) : null,
            'book' => $draft,
            'draft' => ['subject' => $prospect->merge($draft['subject'], $prospect->owner ?? $request->user()), 'body' => $prospect->merge($draft['body'], $prospect->owner ?? $request->user())],
            'suppressed' => $prospect->suppressed(),
            'owners' => $this->owners(),
        ]);
    }

    public function update(Request $request, FundingProspect $prospect): RedirectResponse
    {
        $this->authorizeProspect($request, $prospect);
        $d = $request->validate(['stage' => 'required|in:' . implode(',', array_keys(config('funding_crm.stages'))), 'next_on' => 'nullable|date',
            'next_step' => 'nullable|string|max:255', 'est_need' => 'nullable|numeric|min:0', 'owner_id' => 'nullable|integer|exists:users,id',
            'email' => 'nullable|email:filter|max:190', 'phone' => 'nullable|string|max:40', 'contact_name' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:5000']);
        if (! $request->user()->hasAnyRole(['super-admin', 'admin'])) {
            unset($d['owner_id']);
        }
        $prospect->setStage($d['stage'], $request->user());
        $prospect->update(collect($d)->except('stage')->all());

        return back()->with('success', $prospect->reference . ' saved.');
    }

    public function activity(Request $request, FundingProspect $prospect): RedirectResponse
    {
        $this->authorizeProspect($request, $prospect);
        $d = $request->validate(['type' => 'required|in:note,call,whatsapp,meeting,follow_up', 'title' => 'required|string|max:255', 'body' => 'nullable|string|max:5000',
            'due_at' => 'nullable|required_if:type,follow_up|date']);
        $prospect->activities()->create($d + ['user_id' => $request->user()->id, 'done_at' => $d['type'] === 'follow_up' ? null : now()]);
        if ($d['type'] === 'follow_up') {
            $prospect->update(['next_on' => $d['due_at'], 'next_step' => $d['title']]);
        }

        return back()->with('success', 'Saved.');
    }

    public function done(Request $request, FundingActivity $activity): RedirectResponse
    {
        $this->authorizeProspect($request, $activity->prospect);
        $activity->update(['done_at' => now()]);

        return back()->with('success', 'Done: ' . $activity->title);
    }

    public function email(Request $request, FundingProspect $prospect, Outreach $outreach): RedirectResponse
    {
        $this->authorizeProspect($request, $prospect);
        $d = $request->validate(['subject' => 'required|string|max:180', 'body' => 'required|string|max:6000']);
        try {
            $email = $outreach->send($prospect, $request->user(), $d['subject'], $d['body']);
        } catch (\DomainException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Sent ' . $email->reference . ' to ' . $email->to . ' from ' . config('funding_crm.from.address') . '. A follow-up call is booked.');
    }

    public function followUps(Request $request): View
    {
        $all = $request->user()->hasAnyRole(['super-admin', 'admin']) && $request->boolean('all');
        $items = FundingActivity::where('type', 'follow_up')->whereNull('done_at')->with(['prospect:id,reference,name,stage,phone,email', 'user:id,name'])
            ->when(! $all, fn ($q) => $q->where('user_id', $request->user()->id))->orderBy('due_at')->limit(200)->get();
        $hot = FundingEmail::whereNotNull('sent_at')->where(fn ($q) => $q->where('opened_at', '>=', now()->subDays(3))->orWhere('clicked_at', '>=', now()->subDays(7)))
            ->with('prospect:id,reference,name,stage,owner_id,phone')->latest('opened_at')->limit(30)->get()->filter(fn ($e) => $all || ! $e->prospect?->owner_id || $e->prospect->owner_id === $request->user()->id);

        return view('staff.funding.follow-ups', ['items' => $items, 'hot' => $hot, 'all' => $all]);
    }

    public function playbook(): View
    {
        return view('staff.funding.playbook', ['segments' => config('funding_crm.segments')]);
    }

    public function campaigns(): View
    {
        $campaigns = FundingCampaign::latest('id')->get()->each(function ($c) {
            $e = DB::table('funding_emails')->where('campaign_id', $c->id)->whereNotNull('sent_at');
            $c->opened = (clone $e)->whereNotNull('opened_at')->count();
            $c->clicked = (clone $e)->whereNotNull('clicked_at')->count();
            $c->left = Outreach::eligible($c->segment)->count();
        });

        return view('staff.funding.campaigns', ['campaigns' => $campaigns, 'segments' => config('funding_crm.segments'),
            'eligible' => collect(config('funding_crm.segments'))->map(fn ($s, $k) => Outreach::eligible($k)->count())]);
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $d = $request->validate(['name' => 'required|string|max:120', 'segment' => 'nullable|in:' . implode(',', array_keys(config('funding_crm.segments'))),
            'subject' => 'required|string|max:180', 'body' => 'required|string|max:6000']);
        $c = FundingCampaign::create($d + ['created_by' => $request->user()->id]);

        return back()->with('success', $c->fresh()->reference . ' saved as a draft. ' . Outreach::eligible($c->segment)->count() . ' prospects can be emailed.');
    }

    public function sendCampaign(Request $request, FundingCampaign $campaign, Outreach $outreach): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super-admin', 'admin', 'manager']), 403);
        $r = $outreach->runCampaign($campaign, $request->user());

        return back()->with('success', $campaign->reference . ': sent ' . $r['sent'] . ($r['failed'] ? ', ' . count($r['failed']) . ' failed (' . collect($r['failed'])->first() . ')' : '') . '. ' . Outreach::eligible($campaign->segment)->count() . ' left.');
    }

    public function suppressions(): View
    {
        return view('staff.funding.suppressions', ['rows' => DB::table('funding_suppressions')->latest('id')->paginate(50)]);
    }

    public function suppress(Request $request): RedirectResponse
    {
        $d = $request->validate(['email' => 'required|email:filter|max:190', 'reason' => 'required|in:' . implode(',', array_keys(Suppression::REASONS))]);
        Suppression::add($d['email'], $d['reason'], 'staff: ' . $request->user()->name);

        return back()->with('success', $d['email'] . ' will not be emailed.');
    }

    private function scope(Request $request)
    {
        $me = $request->user();
        $all = $me->hasAnyRole(['super-admin', 'admin', 'manager']) && ! $request->boolean('mine');

        return FundingProspect::query()->select('funding_prospects.*')
            ->when(! $all, fn ($q) => $q->where(fn ($w) => $w->where('owner_id', $me->id)->orWhereNull('owner_id')));
    }

    private function authorizeProspect(Request $request, ?FundingProspect $p): void
    {
        abort_unless($p, 404);
        $me = $request->user();
        abort_unless($me->hasAnyRole(['super-admin', 'admin', 'manager']) || ! $p->owner_id || (int) $p->owner_id === (int) $me->id, 403);
    }

    private function owners()
    {
        return User::permission('manage funding prospects')->orderBy('name')->get(['id', 'name']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'segment' => 'required|in:' . implode(',', array_keys(config('funding_crm.segments'))),
            'source' => 'required|in:' . implode(',', array_keys(config('funding_crm.sources'))),
            'source_url' => 'nullable|url|max:255',
            'email' => 'nullable|email:filter|max:190',
            'phone' => 'nullable|string|max:40',
            'town' => 'nullable|string|max:80',
            'county' => 'nullable|string|max:60',
            'website' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_role' => 'nullable|string|max:255',
            'est_need' => 'nullable|numeric|min:0',
            'owner_id' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string|max:5000',
        ]);
    }
}
