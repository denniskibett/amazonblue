{{-- Funding CRM playbook: how to approach each kind of business, what to offer, objections, the starter email. --}}
@extends('layouts.app')

@section('content')
  @php extract(\App\Support\CrmUi::CLASSES); @endphp
  @include('staff.desk._header', ['title' => 'Playbook', 'sub' => 'Who we call, what they need, and what to say. Never promise approval, an amount or a rate: we assess every request.', 'metrics' => false])
  @include('staff.funding._ui')

  <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    @foreach ($segments as $key => $s)
      <section id="{{ $key }}" class="{{ $card }}">
        <h3 class="font-semibold text-gray-800 dark:text-white/90">{{ $s['label'] }}</h3>
        <p class="mb-3 text-theme-xs text-gray-500">Usual need: {{ config('funding_crm.needs.' . $s['need']) }}</p>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Approach</p>
        <ul class="mb-3 list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-400">@foreach ($s['approach'] as $l)<li>{{ $l }}</li>@endforeach</ul>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">What to offer</p>
        <ul class="mb-3 list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-400">@foreach ($s['offers'] as $l)<li>{{ $l }}</li>@endforeach</ul>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Objections</p>
        <dl class="mb-3 space-y-2 text-sm">@foreach ($s['objections'] as $q => $a)<div><dt class="font-medium text-gray-700 dark:text-gray-300">"{{ $q }}"</dt><dd class="text-gray-600 dark:text-gray-400">{{ $a }}</dd></div>@endforeach</dl>
        <details><summary class="cursor-pointer text-sm text-brand-500">Starter email</summary>
          <p class="mt-2 text-sm font-medium">{{ $s['subject'] }}</p>
          <pre class="mt-1 whitespace-pre-wrap rounded-lg bg-gray-50 p-3 font-sans text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $s['body'] }}</pre></details>
      </section>
    @endforeach
  </div>
@endsection
