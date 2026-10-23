{{-- Choose who gets a notice (owner, 23 Oct 2026). Everyone who should get it is ticked; untick anyone you want to leave
     out. "Preview" shows the exact email and SMS for that borrower. Saving makes a draft; nothing is sent yet. --}}
@extends('layouts.app')

@php
  $kinds = \App\Services\Messaging\CollectionNotices::KINDS;
  $tabCls = '-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-theme-sm font-medium';
  $tabOn = 'border-brand-500 text-brand-500 dark:border-brand-400 dark:text-brand-400';
  $tabOff = 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200';
  $ready = $rows->reject(fn ($r) => $r->skip);
  $person = in_array($kind, \App\Services\Messaging\CollectionNotices::PERSON_KINDS, true);
  $keyOf = fn ($r) => $r->loan ? $r->loan->id : $r->key;
  $field = 'h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'New batch: ' . $kinds[$kind]['label'], 'sub' => $kinds[$kind]['about'], 'metrics' => false,
    'actions' => '<a href="' . route('staff.messages.index') . '" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">All batches</a>'])

  <script>
    window.__azOwed = @js($rows->mapWithKeys(fn ($r) => [$keyOf($r) => $r->owed ?? 0]));
    window.batchPicker = function (ids, previewUrl, kind) {
      return {
        picked: new Set(ids), open: false, src: 'about:blank', who: '', sms: '', smsLen: 0,
        toggle(id) { const s = new Set(this.picked); s.has(id) ? s.delete(id) : s.add(id); this.picked = s; },
        all(on) { this.picked = new Set(on ? ids : []); },
        total() { return [...this.picked].reduce((t, id) => t + Number(window.__azOwed[id] || 0), 0); },
        kes(v) { return 'KES ' + Number(v || 0).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
        async show(id, name) {
          this.who = name; this.src = previewUrl + '?' + new URLSearchParams({kind, loan: id}); this.open = true; this.sms = '…'; this.smsLen = 0;
          try {
            const r = await fetch(previewUrl + '?' + new URLSearchParams({kind, loan: id, sms: 1}), {headers: {Accept: 'application/json'}});
            const j = await r.json(); this.sms = j.sms; this.smsLen = j.length;
          } catch (_) { this.sms = 'Could not load the SMS.'; }
        },
      };
    };
  </script>
  <div x-data="batchPicker(@js($ready->map(fn ($r) => $keyOf($r))->values()), @js(route('staff.messages.preview')), @js($kind))" @az-preview.window="show($event.detail.id, $event.detail.name)">
    <form method="POST" action="{{ route('staff.messages.store') }}" data-no-swap>
      @csrf
      <input type="hidden" name="kind" value="{{ $kind }}">
      <template x-for="id in [...picked]" :key="id"><input type="hidden" name="loans[]" :value="id"></template>

      <div class="mb-6 grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 lg:grid-cols-3">
        <div>
          <p class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">Send by</p>
          <label class="mr-4 inline-flex items-center gap-2 text-theme-sm"><input type="checkbox" name="channels[]" value="email" checked class="size-4 accent-brand-500"> Email</label>
          <label class="inline-flex items-center gap-2 text-theme-sm"><input type="checkbox" name="channels[]" value="sms" class="size-4 accent-brand-500" @checked($health['sms']['ok'])> SMS</label>
          @unless ($health['sms']['ok'])<p class="mt-1 text-theme-xs text-amber-700 dark:text-amber-400">SMS is not set up yet: ticked SMS are only written to the log.</p>@endunless
          @unless ($health['mail']['ok'])<p class="mt-1 text-theme-xs text-amber-700 dark:text-amber-400">Email is not set up on this system: messages are written to the log.</p>@endunless
        </div>
        <div>
          <label for="note" class="mb-2 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">Note for the team (optional)</label>
          <input id="note" name="note" maxlength="500" class="{{ $field }} w-full" placeholder="e.g. October arrears push">
        </div>
        <div class="flex flex-col justify-end gap-2">
          <p class="text-theme-sm text-gray-600 dark:text-gray-400"><b class="font-outfit text-gray-800 dark:text-white/90" x-text="picked.size"></b> of {{ $ready->count() }} ticked<span @class(["hidden" => $person])> · <span x-text="kes(total())"></span></span></p>
          <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50" :disabled="!picked.size">Save draft (nothing is sent yet)</button>
        </div>
      </div>
    </form>

    <div id="pick-tools" class="hidden">
      <form method="GET" action="{{ route('staff.messages.create') }}" class="flex flex-wrap items-center gap-2" data-no-swap>
        <input type="hidden" name="kind" value="{{ $kind }}">
        @if ($kind === 'due_soon')
          <label class="text-theme-sm text-gray-600 dark:text-gray-400" for="days">Due within</label>
          <select id="days" name="days" class="{{ $field }}" onchange="this.form.submit()">@foreach ([0, 1, 2, 3, 5, 7, 14] as $d)<option value="{{ $d }}" @selected($days === $d)>{{ $d === 0 ? 'today' : $d . ' days' }}</option>@endforeach</select>
        @endif
        <label class="inline-flex items-center gap-2 text-theme-sm text-gray-600 dark:text-gray-400"><input type="checkbox" name="forbearance" value="1" @checked($forbearance) onchange="this.form.submit()" class="size-4 accent-brand-500"> Include forbearance</label>
        <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300" @click="all(true)">Tick all</button>
        <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300" @click="all(false)">Untick all</button>
      </form>
    </div>

    @if ($person)
      <x-staff.table title="Recipients" caption="Recipients" data-table-buttons="#pick-tools > *" data-table-page="25" :headers="['', 'Borrower', ['Missing', null, 'mobile'], 'Last sent', '']">
        <x-slot:tabs>
          @foreach ($kinds as $k => $info)
            <a href="{{ route('staff.messages.create', ['kind' => $k]) }}" role="tab" aria-selected="{{ $k === $kind ? 'true' : 'false' }}" class="{{ $tabCls }} {{ $k === $kind ? $tabOn : $tabOff }}">{{ $info['label'] }}</a>
          @endforeach
        </x-slot:tabs>
        @forelse ($rows as $r)
          <tr @class(['opacity-60' => $r->skip])>
            <td class="w-8">@if ($r->skip)<span class="text-gray-300" title="{{ $r->skip }}">—</span>@else<input type="checkbox" class="size-4 accent-brand-500" :checked="picked.has({{ $r->key }})" @change="toggle({{ $r->key }})" aria-label="Send to {{ $r->user->name }}">@endif</td>
            <td data-sort-value="{{ $r->user->name }}"><x-staff.person :user="$r->user" :sub="$r->user->email ?: 'no email'" /></td>
            <td class="!whitespace-normal"><div class="flex flex-wrap gap-1">@foreach ($r->missing as $m)<x-staff.pill tone="amber">{{ ucfirst($m) }}</x-staff.pill>@endforeach</div></td>
            <td class="text-theme-xs text-gray-500 dark:text-gray-400">@if ($r->skip)<x-staff.pill tone="gray">{{ $r->skip }}</x-staff.pill>@elseif ($r->last_sent){{ \Illuminate\Support\Carbon::parse($r->last_sent)->format('j M') }}@else Never @endif</td>
            <td class="text-right" data-actions><button type="button" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" onclick="window.dispatchEvent(new CustomEvent('az-preview', {detail: {id: {{ $r->key }}, name: {{ \Illuminate\Support\Js::from($r->user->name) }}}}))">Preview</button></td>
          </tr>
        @empty
          <tr data-table-skip><td colspan="5"><x-staff.empty title="Every verified borrower's profile is complete" /></td></tr>
        @endforelse
      </x-staff.table>
    @else
    <x-staff.table title="Recipients" caption="Recipients" data-table-buttons="#pick-tools > *" data-table-page="25"
        :headers="['', 'Borrower', 'Loan', ['Owed today', 'num', 'mobile'], $kind === 'due_soon' ? 'Due' : 'Days late', 'Last sent', '']">
        <x-slot:tabs>
          @foreach ($kinds as $k => $info)
            <a href="{{ route('staff.messages.create', ['kind' => $k]) }}" role="tab" aria-selected="{{ $k === $kind ? 'true' : 'false' }}" class="{{ $tabCls }} {{ $k === $kind ? $tabOn : $tabOff }}">{{ $info['label'] }}</a>
          @endforeach
        </x-slot:tabs>
        @forelse ($rows as $r)
          <tr @class(['opacity-60' => $r->skip])>
            <td class="w-8">
              @if ($r->skip)
                <span class="text-gray-300" title="{{ $r->skip }}">—</span>
              @else
                <input type="checkbox" class="size-4 accent-brand-500" :checked="picked.has({{ $r->loan->id }})" @change="toggle({{ $r->loan->id }})" aria-label="Send to {{ $r->user->name }}">
              @endif
            </td>
            <td data-sort-value="{{ $r->user->name }}"><x-staff.person :user="$r->user" :sub="$r->user->email ?: 'no email'" /></td>
            <td><a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('users.loans.show', [$r->loan->user_id, $r->loan->id]) }}">Loan #{{ $r->loan->id }}</a><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $r->loan->loanType->name ?? '' }} · {{ ucfirst($r->status) }}</span></td>
            <td class="num" data-sort-value="{{ $r->owed }}"><x-staff.money :amount="$r->owed" class="text-gray-800 dark:text-white/90" /></td>
            <td data-sort-value="{{ $kind === 'due_soon' ? $r->due_in : $r->days_late }}">
              @if ($kind === 'due_soon')
                <x-staff.pill tone="blue">{{ $r->due_in === 0 ? 'Today' : 'In ' . $r->due_in . ' ' . \Illuminate\Support\Str::plural('day', $r->due_in) }}</x-staff.pill>
              @else
                <x-staff.pill :tone="$r->days_late > 30 ? 'red' : 'amber'">{{ $r->days_late }} days</x-staff.pill>
              @endif
              <span class="block text-theme-xs text-gray-500 dark:text-gray-400">due {{ $r->due_date->format('j M Y') }}</span>
            </td>
            <td class="text-theme-xs text-gray-500 dark:text-gray-400">@if ($r->skip)<x-staff.pill tone="gray">{{ $r->skip }}</x-staff.pill>@elseif ($r->last_sent){{ \Illuminate\Support\Carbon::parse($r->last_sent)->format('j M') }}@else Never @endif</td>
            <td class="text-right" data-actions>
              <button type="button" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" onclick="window.dispatchEvent(new CustomEvent('az-preview', {detail: {id: {{ $r->loan->id }}, name: {{ \Illuminate\Support\Js::from($r->user->name) }}}}))">Preview</button>
            </td>
          </tr>
        @empty
          <tr data-table-skip><td colspan="7"><x-staff.empty title="Nobody to send to" message="No open loan fits this notice today." /></td></tr>
        @endforelse
      </x-staff.table>
    @endif

    {{-- preview: the exact email and SMS for one borrower --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" @keydown.escape.window="open = false">
      <div class="absolute inset-0 bg-gray-900/50" @click="open = false"></div>
      <div class="relative flex max-h-full w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-theme-xl dark:bg-gray-900">
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 dark:border-gray-800">
          <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">What <span x-text="who"></span> will get</h3>
          <button type="button" class="rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800" @click="open = false" aria-label="Close">✕</button>
        </div>
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3 text-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
          <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">SMS (<span x-text="smsLen"></span> characters)</p>
          <p class="mt-1 text-gray-800 dark:text-white/90" x-text="sms"></p>
        </div>
        <iframe :src="src" class="h-[70vh] w-full bg-white" title="Email preview"></iframe>
      </div>
    </div>
  </div>

@endsection
