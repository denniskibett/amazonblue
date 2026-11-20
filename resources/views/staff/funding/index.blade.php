{{-- Funding CRM: business prospects that need float or finance (20 Nov 2026). Funding\FundingCrmController@index --}}
@extends('layouts.app')

@section('content')
  @php extract(\App\Support\CrmUi::CLASSES); @endphp
  @include('staff.desk._header', ['title' => 'Funding prospects', 'sub' => 'Businesses that need float, working capital, asset or trade finance. Never individuals. Every reference starts with AIM-.', 'metrics' => false,
    'actions' => '<button type="button" onclick="document.getElementById(\'add-prospect\').showModal()" class="inline-flex h-11 items-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white">+ Prospect</button>'
      . '<button type="button" onclick="document.getElementById(\'import\').showModal()" class="inline-flex h-11 items-center rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700">Import CSV</button>'])
  @include('staff.funding._ui')

  @php $S = config('funding_crm.stages'); $pct = fn ($a, $b) => $b ? round($a / $b * 100) . '%' : '—'; @endphp
  <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3 2xl:grid-cols-6">
    @foreach ([['Prospects', $total, ($stages['new'] ?? 0) . ' not contacted'], ['Emails sent', $sent, 'from ' . config('funding_crm.from.address')], ['Opened', $opened, $pct($opened, $sent) . ' of sent'],
      ['Clicked', $clicked, $pct($clicked, $sent) . ' of sent'], ['Applications', $applications, 'started from the CRM'], ['Follow-ups due', $due, 'yours, today or late']] as [$l, $v, $s])
      <div class="{{ $card }}"><p class="text-theme-sm text-gray-500">{{ $l }}</p><p class="mt-2 font-outfit text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($v) }}</p><p class="mt-1 text-theme-xs text-gray-500">{{ $s }}</p></div>
    @endforeach
  </div>

  <div class="{{ $card }} mb-6">
    <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Pipeline</h3>
    <div class="flex flex-wrap gap-2">
      @foreach ($S as $k => $l)
        <a href="{{ route('staff.funding.index', array_filter(['stage' => request('stage') === $k ? null : $k, 'segment' => request('segment')])) }}" class="rounded-lg border px-3 py-2 text-sm {{ request('stage') === $k ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-gray-200 text-gray-700 dark:border-gray-800 dark:text-gray-300' }}">{{ $l }} <b class="font-outfit">{{ $stages[$k] ?? 0 }}</b></a>
      @endforeach
    </div>
  </div>

  <form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input class="{{ $input }} max-w-xs" type="search" name="q" value="{{ request('q') }}" placeholder="Name, email, town or AIM-P-" aria-label="Search prospects">
    <select class="{{ $input }} max-w-xs" name="segment" aria-label="Segment" onchange="this.form.submit()"><option value="">Every segment</option>
      @foreach (config('funding_crm.segments') as $k => $s)<option value="{{ $k }}" @selected(request('segment') === $k)>{{ $s['label'] }}</option>@endforeach</select>
    @if (request('stage'))<input type="hidden" name="stage" value="{{ request('stage') }}">@endif
    <button class="{{ $ghost }}">Search</button>
  </form>

  <div class="{{ $card }} !p-0 overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-b border-gray-200 dark:border-gray-800"><tr><th class="{{ $th }}">Prospect</th><th class="{{ $th }}">Segment</th><th class="{{ $th }}">Stage</th><th class="{{ $th }}">Last opened</th><th class="{{ $th }}">Next</th><th class="{{ $th }}">Owner</th></tr></thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($prospects as $p)
          <tr>
            <td class="{{ $td }}"><a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('staff.funding.show', $p) }}">{{ $p->name }}</a>
              <span class="block text-theme-xs text-gray-500">{{ $p->reference }} · {{ $p->town ?: $p->county ?: '—' }}{{ $p->opted_out_at ? ' · opted out' : '' }}</span></td>
            <td class="{{ $td }}">{{ $p->segmentLabel() }}</td>
            <td class="{{ $td }}">{{ $p->stageLabel() }}</td>
            <td class="{{ $td }}">{{ $p->last_open ? \Illuminate\Support\Carbon::parse($p->last_open)->tz('Africa/Nairobi')->format('D j M, g:ia') : '—' }}</td>
            <td class="{{ $td }}">{{ $p->next_on?->format('j M') }} {{ \Illuminate\Support\Str::limit($p->next_step, 40) }}</td>
            <td class="{{ $td }}">{{ $p->owner?->name ?? 'Unassigned' }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">No prospects yet. Add one, or import a CSV from lawful public sources (company websites, directories, tender awards, association lists).</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $prospects->links() }}</div>

  <dialog id="add-prospect" class="w-full max-w-2xl rounded-2xl p-0 backdrop:bg-gray-900/50">
    <form method="POST" action="{{ route('staff.funding.store') }}" class="grid gap-4 p-6 sm:grid-cols-2">
      @csrf
      <h3 class="text-lg font-semibold text-gray-800 sm:col-span-2">Add a business</h3>
      <p class="text-sm text-gray-500 sm:col-span-2">Only businesses, and only details the business published itself or gave us. Record where you found them.</p>
      <label class="sm:col-span-2"><span class="{{ $label }}">Business name *</span><input class="{{ $input }}" name="name" required value="{{ old('name') }}"></label>
      <label><span class="{{ $label }}">Segment *</span><select class="{{ $input }}" name="segment" required>@foreach (config('funding_crm.segments') as $k => $s)<option value="{{ $k }}">{{ $s['label'] }}</option>@endforeach</select></label>
      <label><span class="{{ $label }}">Where you found them *</span><select class="{{ $input }}" name="source" required>@foreach (config('funding_crm.sources') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
      <label class="sm:col-span-2"><span class="{{ $label }}">Source link</span><input class="{{ $input }}" name="source_url" type="url" placeholder="https://"></label>
      <label><span class="{{ $label }}">Business email</span><input class="{{ $input }}" name="email" type="email"></label>
      <label><span class="{{ $label }}">Phone</span><input class="{{ $input }}" name="phone"></label>
      <label><span class="{{ $label }}">Contact person</span><input class="{{ $input }}" name="contact_name"></label>
      <label><span class="{{ $label }}">Their role</span><input class="{{ $input }}" name="contact_role" placeholder="e.g. Finance manager"></label>
      <label><span class="{{ $label }}">Town</span><input class="{{ $input }}" name="town"></label>
      <label><span class="{{ $label }}">Likely need (KES)</span><input class="{{ $input }}" name="est_need" type="number" min="0"></label>
      <div class="flex justify-end gap-2 sm:col-span-2"><button type="button" class="{{ $ghost }}" onclick="this.closest('dialog').close()">Cancel</button><button class="{{ $btn }}">Add</button></div>
    </form>
  </dialog>
  <dialog id="import" class="w-full max-w-lg rounded-2xl p-0 backdrop:bg-gray-900/50">
    <form method="POST" action="{{ route('staff.funding.import') }}" enctype="multipart/form-data" class="grid gap-4 p-6">
      @csrf
      <h3 class="text-lg font-semibold text-gray-800">Import prospects</h3>
      <p class="text-sm text-gray-500">CSV with a header row. Needed: <code>name</code>, <code>segment</code> ({{ implode(', ', array_keys(config('funding_crm.segments'))) }}), <code>source</code> ({{ implode(', ', array_keys(config('funding_crm.sources'))) }}). Optional: email, phone, town, county, website, contact_name, contact_role, source_url, need, est_need. Duplicates are skipped; suppressed addresses are never emailed.</p>
      <input type="file" name="csv" accept=".csv,text/csv" required>
      <div class="flex justify-end gap-2"><button type="button" class="{{ $ghost }}" onclick="this.closest('dialog').close()">Cancel</button><button class="{{ $btn }}">Import</button></div>
    </form>
  </dialog>
@endsection
