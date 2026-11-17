{{-- Features and suggestions (owner, 17 Nov 2026), sysadmin only: the tracker of what the app does today, what needs
     work and what is missing, and the suggestions staff, borrowers and partners send from the Suggest button. --}}
@extends('layouts.app')

@section('content')
@php
  $S = \App\Http\Controllers\Staff\FeaturesController::STATUS;
  $P = \App\Http\Controllers\Staff\FeaturesController::PRIORITY;
  $R = \App\Http\Controllers\Staff\FeaturesController::REPLY;
  $tone = ['working' => 'green', 'improve' => 'amber', 'missing' => 'red', 'new' => 'blue', 'planned' => 'purple', 'done' => 'green', 'declined' => 'gray'];
  $ptone = ['now' => 'text-error-600', 'next' => 'text-amber-700 dark:text-amber-400', 'later' => 'text-gray-500'];
  $input_ = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $area_ = 'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $label = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
  $btn = 'inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-theme-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
  $primary = 'inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600';
  $areas = $features->pluck('area')->unique()->values();
  $count = fn ($k) => $features->where('status', $k)->count();
  $old = fn ($sheet, $key, $def = null) => old('_sheet') === $sheet ? old($key, $def) : $def;
  $featureFields = function (string $sheet, $f = null, ?object $s = null) use ($S, $P, $input_, $area_, $label, $old, $areas) {
    return view('staff._feature-fields', compact('sheet', 'f', 's', 'S', 'P', 'input_', 'area_', 'label', 'old', 'areas'))->render();
  };
@endphp

<x-ui.page-header title="Features and suggestions" sub="What the app does today, what needs work and what is missing; and the ideas people send from the Suggest button.">
  <button type="button" class="{{ $primary }}" @click="$dispatch('open-sheet', 'feature-new')"><x-ui.icon name="plus" class="size-4" />Add feature</button>
</x-ui.page-header>

<x-ui.metrics :items="[
  ['label' => 'Working', 'value' => $count('working'), 'icon' => 'check', 'hint' => 'features doing their job'],
  ['label' => 'Needs work', 'value' => $count('improve'), 'icon' => 'alert', 'hint' => $features->where('status', 'improve')->where('priority', 'now')->count() . ' to do now'],
  ['label' => 'Missing', 'value' => $count('missing'), 'icon' => 'plus', 'hint' => 'not built yet'],
  ['label' => 'New suggestions', 'value' => $suggestions->where('status', 'new')->count(), 'icon' => 'send', 'hint' => $suggestions->count() . ' in all'],
]" />

<x-staff.tabs :tabs="['tracker' => ['Tracker', $features->count()], 'suggestions' => ['Suggestions', $suggestions->where('status', 'new')->count()]]">
  <x-staff.tab name="tracker">
    <x-staff.table caption="Features" :headers="['Feature', 'Area', 'Status', 'Priority', 'Shortcoming', '']">
      @foreach ($features as $f)
        <tr data-feature="{{ $f->id }}">
          <td class="font-medium text-gray-800 dark:text-white/90">{{ $f->name }}@if ($f->suggestion_id)<span class="block text-theme-xs font-normal text-gray-500">from suggestion #{{ $f->suggestion_id }}</span>@endif</td>
          <td data-filter-value="{{ $f->area }}">{{ $f->area }}</td>
          <td data-filter-value="{{ $S[$f->status] ?? $f->status }}"><x-staff.pill :tone="$tone[$f->status] ?? 'gray'" dot>{{ $S[$f->status] ?? $f->status }}</x-staff.pill></td>
          <td data-sort-value="{{ array_search($f->priority, array_keys($P)) }}" data-filter-value="{{ $P[$f->priority] ?? $f->priority }}"><span class="font-medium {{ $ptone[$f->priority] ?? '' }}">{{ $P[$f->priority] ?? $f->priority }}</span></td>
          <td class="max-w-md whitespace-normal text-theme-sm text-gray-600 dark:text-gray-400">{{ $f->shortcoming ?: '—' }}</td>
          <td class="text-right"><button type="button" class="{{ $btn }}" @click="$dispatch('open-sheet', 'feature-{{ $f->id }}')">Edit</button></td>
        </tr>
      @endforeach
    </x-staff.table>
  </x-staff.tab>

  <x-staff.tab name="suggestions">
    <x-staff.table caption="Suggestions" :headers="['When', 'From', 'Suggestion', 'Status', '']">
      @forelse ($suggestions as $s)
        <tr data-suggestion="{{ $s->id }}">
          <td data-sort-value="{{ $s->created_at }}" class="whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($s->created_at)->format('j M Y') }}</td>
          <td><x-staff.person :name="$s->user_name ?: 'Someone'" :id="$s->user_id" :sub="$s->user_email" /></td>
          <td class="max-w-lg whitespace-normal">
            <x-staff.pill :tone="$s->kind === 'problem' ? 'red' : 'brand'">{{ $s->kind === 'problem' ? 'Problem' : 'Idea' }}</x-staff.pill>
            <span class="mt-1 block text-gray-800 dark:text-white/90">{{ $s->body }}</span>
            @if ($s->page)<span class="block text-theme-xs text-gray-500">sent from {{ $s->page }}</span>@endif
            @if ($s->reply)<span class="mt-1 block text-theme-xs text-gray-600 dark:text-gray-400">Reply: {{ $s->reply }}</span>@endif
          </td>
          <td data-filter-value="{{ $R[$s->status] ?? $s->status }}"><x-staff.pill :tone="$tone[$s->status] ?? 'gray'" dot>{{ $R[$s->status] ?? $s->status }}</x-staff.pill>
            @if (in_array($s->id, $linked))<span class="block pt-1 text-theme-xs text-gray-500">on the tracker</span>@endif</td>
          <td class="text-right"><div class="flex justify-end gap-2 whitespace-nowrap">
            <button type="button" class="{{ $btn }}" @click="$dispatch('open-sheet', 'reply-{{ $s->id }}')">Reply</button>
            @unless (in_array($s->id, $linked))<button type="button" class="{{ $btn }}" @click="$dispatch('open-sheet', 'promote-{{ $s->id }}')">Add to tracker</button>@endunless
          </div></td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="5"><x-staff.empty title="No suggestions yet" message="Ideas and problems sent from the Suggest button on any page land here." /></td></tr>
      @endforelse
    </x-staff.table>
  </x-staff.tab>
</x-staff.tabs>

{{-- the sheets --}}
<x-staff.sheet name="feature-new" title="Add a feature" sub="It shows on the tracker straight away." :action="route('staff.features.store')">
  {!! $featureFields('feature-new') !!}
  <x-slot:footer><button class="{{ $primary }}">Add</button></x-slot:footer>
</x-staff.sheet>
@foreach ($features as $f)
  <x-staff.sheet :name="'feature-' . $f->id" :title="'Edit ' . $f->name" :sub="$f->area" :action="route('staff.features.update', $f->id)">
    {!! $featureFields('feature-' . $f->id, $f) !!}
    <x-slot:footer><button class="{{ $primary }}">Save</button></x-slot:footer>
  </x-staff.sheet>
@endforeach
@foreach ($suggestions as $s)
  <x-staff.sheet :name="'reply-' . $s->id" title="Reply to a suggestion" :sub="\Illuminate\Support\Str::limit($s->body, 90)" :action="route('staff.suggestions.reply', $s->id)">
    <div><label class="{{ $label }}" for="r-status-{{ $s->id }}">Status</label>
      <select id="r-status-{{ $s->id }}" name="status" class="{{ $input_ }}">@foreach ($R as $k => $l)<option value="{{ $k }}" @selected($old('reply-' . $s->id, 'status', $s->status) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div><label class="{{ $label }}" for="r-reply-{{ $s->id }}">Reply <span class="font-normal text-gray-400">(optional)</span></label>
      <textarea id="r-reply-{{ $s->id }}" name="reply" rows="4" maxlength="1000" class="{{ $area_ }}">{{ $old('reply-' . $s->id, 'reply', $s->reply) }}</textarea></div>
    <x-slot:footer><button class="{{ $primary }}">Save</button></x-slot:footer>
  </x-staff.sheet>
  @unless (in_array($s->id, $linked))
    <x-staff.sheet :name="'promote-' . $s->id" title="Add this suggestion to the tracker" :sub="\Illuminate\Support\Str::limit($s->body, 90)" :action="route('staff.features.store')">
      <input type="hidden" name="suggestion_id" value="{{ $s->id }}">
      {!! $featureFields('promote-' . $s->id, null, $s) !!}
      <x-slot:footer><button class="{{ $primary }}">Add to tracker</button></x-slot:footer>
    </x-staff.sheet>
  @endunless
@endforeach
@endsection
