{{-- One funding prospect: details, stage, engagement, email (playbook draft), activity and follow-ups. --}}
@extends('layouts.app')

@section('content')
  @php extract(\App\Support\CrmUi::CLASSES); @endphp
  @include('staff.desk._header', ['title' => $p->name, 'sub' => $p->reference . ' · ' . $p->segmentLabel() . ' · ' . ($p->town ?: $p->county ?: 'Kenya') . ' · from ' . strtolower(config('funding_crm.sources.' . $p->source, $p->source)), 'metrics' => false])
  @include('staff.funding._ui')

  <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
      <div class="{{ $card }}">
        <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Email engagement</h3>
        @if ($engagement)
          <p class="text-sm text-gray-700 dark:text-gray-300"><b>{{ $engagement['opens'] }}</b> open{{ $engagement['opens'] === 1 ? '' : 's' }} · <b>{{ $engagement['clicks'] }}</b> click{{ $engagement['clicks'] === 1 ? '' : 's' }} · last {{ $engagement['last']->format('D j M, g:ia') }}
            @if ($engagement['place']) · {{ $engagement['place'] }}@endif @if ($engagement['device']) · {{ $engagement['device'] }}@endif</p>
          @if ($engagement['best'])<p class="mt-1 text-sm text-gray-500">Best time to call: {{ $engagement['best'] }}.</p>@endif
          @if ($engagement['hidden'])<p class="mt-1 text-theme-xs text-gray-500">Their mail app hides where they read (Gmail or Apple Mail privacy), so town and device may be missing.</p>@endif
          <ul class="mt-3 divide-y divide-gray-100 text-sm dark:divide-gray-800">
            @foreach ($events as $e)
              <li class="flex justify-between gap-3 py-1.5"><span>{{ ucfirst($e->kind) }} · {{ $e->at->format('D j M, g:ia') }}</span><span class="text-gray-500">{{ \App\Services\Funding\EmailEngagement::place($e) ?? ($e->via !== 'direct' ? ucfirst($e->via) . ' proxy' : '—') }} · {{ \App\Services\Funding\EmailEngagement::deviceLabel($e) ?? '—' }}</span></li>
            @endforeach
          </ul>
        @else
          <p class="text-sm text-gray-500">No opens or clicks yet.</p>
        @endif
      </div>

      <div class="{{ $card }}">
        <h3 class="mb-1 font-semibold text-gray-800 dark:text-white/90">Email them</h3>
        @if ($suppressed)
          <p class="text-sm text-error-600">{{ $p->email }} is on the do-not-email list. Call or WhatsApp instead.</p>
        @elseif (! $p->email)
          <p class="text-sm text-gray-500">No email address. Add one the business published, or call.</p>
        @else
          <p class="mb-3 text-sm text-gray-500">From {{ config('funding_crm.from.address') }} as you; the subject gets an AIM-E- reference and the footer an opt-out. Starter text from the {{ $book['label'] }} playbook.</p>
          <form method="POST" action="{{ route('staff.funding.email', $p) }}" class="grid gap-3">
            @csrf
            <label><span class="{{ $label }}">To</span><input class="{{ $input }}" value="{{ $p->email }}" disabled></label>
            <label><span class="{{ $label }}">Subject</span><input class="{{ $input }}" name="subject" value="{{ old('subject', $draft['subject']) }}" required maxlength="180"></label>
            <label><span class="{{ $label }}">Message</span><textarea class="{{ $area }}" name="body" rows="10" required>{{ old('body', $draft['body']) }}</textarea></label>
            <p class="text-theme-xs text-gray-500">No promises of approval, amounts or rates: this is regulated credit.</p>
            <div><button class="{{ $btn }}">Send</button></div>
          </form>
        @endif
        @if ($emails->isNotEmpty())
          <h4 class="mt-5 text-sm font-semibold text-gray-700 dark:text-gray-300">Sent</h4>
          <ul class="mt-2 divide-y divide-gray-100 text-sm dark:divide-gray-800">
            @foreach ($emails as $e)
              <li class="flex flex-wrap justify-between gap-2 py-2"><span><b>{{ $e->reference }}</b> {{ $e->subject }}</span><span class="text-gray-500">{{ $e->sent_at?->format('j M, g:ia') }} · {{ $e->status() }}{{ $e->opens > 1 ? ' (' . $e->opens . '×)' : '' }}</span></li>
            @endforeach
          </ul>
        @endif
      </div>

      <div class="{{ $card }}">
        <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Activity</h3>
        <form method="POST" action="{{ route('staff.funding.activity', $p) }}" class="mb-4 grid gap-3 sm:grid-cols-4">
          @csrf
          <select class="{{ $input }}" name="type" aria-label="Type">@foreach (['note' => 'Note', 'call' => 'Call', 'whatsapp' => 'WhatsApp', 'meeting' => 'Meeting', 'follow_up' => 'Follow-up (due)'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
          <input class="{{ $input }} sm:col-span-2" name="title" placeholder="What happened, or what to do" required aria-label="Title">
          <input class="{{ $input }}" type="datetime-local" name="due_at" aria-label="Due (follow-ups)">
          <textarea class="{{ $area }} sm:col-span-3" name="body" rows="2" placeholder="Details (optional)" aria-label="Details"></textarea>
          <button class="{{ $btn }} justify-center">Save</button>
        </form>
        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($activities as $a)
            <li class="py-3 text-sm">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <span><span class="rounded bg-gray-100 px-2 py-0.5 text-theme-xs dark:bg-gray-800">{{ \App\Models\Funding\FundingActivity::TYPES[$a->type] ?? $a->type }}</span> <b class="text-gray-800 dark:text-white/90">{{ $a->title }}</b></span>
                <span class="text-theme-xs text-gray-500">{{ $a->user?->name ?? 'System' }} · {{ $a->created_at->format('j M, g:ia') }}
                  @if ($a->type === 'follow_up' && ! $a->done_at) · due {{ $a->due_at?->format('j M, g:ia') }}
                    <form method="POST" action="{{ route('staff.funding.activities.done', $a) }}" class="inline">@csrf<button class="text-brand-500 underline">Done</button></form>@endif</span>
              </div>
              @if ($a->body)<p class="mt-1 whitespace-pre-line text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($a->body, 600) }}</p>@endif
            </li>
          @empty
            <li class="py-3 text-sm text-gray-500">Nothing yet.</li>
          @endforelse
        </ul>
      </div>
    </div>

    <div class="space-y-6">
      <form method="POST" action="{{ route('staff.funding.update', $p) }}" class="{{ $card }} grid gap-3">
        @csrf
        <h3 class="font-semibold text-gray-800 dark:text-white/90">Pipeline</h3>
        <label><span class="{{ $label }}">Stage</span><select class="{{ $input }}" name="stage">@foreach (config('funding_crm.stages') as $k => $l)<option value="{{ $k }}" @selected($p->stage === $k)>{{ $l }}</option>@endforeach</select></label>
        <label><span class="{{ $label }}">Next step</span><input class="{{ $input }}" name="next_step" value="{{ $p->next_step }}"></label>
        <label><span class="{{ $label }}">On</span><input class="{{ $input }}" type="date" name="next_on" value="{{ $p->next_on?->toDateString() }}"></label>
        <label><span class="{{ $label }}">Likely need (KES)</span><input class="{{ $input }}" type="number" min="0" name="est_need" value="{{ $p->est_need }}"></label>
        <label><span class="{{ $label }}">Contact</span><input class="{{ $input }}" name="contact_name" value="{{ $p->contact_name }}"></label>
        <label><span class="{{ $label }}">Email</span><input class="{{ $input }}" type="email" name="email" value="{{ $p->email }}"></label>
        <label><span class="{{ $label }}">Phone</span><input class="{{ $input }}" name="phone" value="{{ $p->phone }}"></label>
        @if (auth()->user()->hasAnyRole(['super-admin', 'admin']))
          <label><span class="{{ $label }}">Owner</span><select class="{{ $input }}" name="owner_id"><option value="">Unassigned</option>@foreach ($owners as $o)<option value="{{ $o->id }}" @selected($p->owner_id === $o->id)>{{ $o->name }}</option>@endforeach</select></label>
        @endif
        <label><span class="{{ $label }}">Notes</span><textarea class="{{ $area }}" name="notes" rows="3">{{ $p->notes }}</textarea></label>
        <button class="{{ $btn }} justify-center">Save</button>
      </form>

      <div class="{{ $card }}">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">Application</h3>
        @if ($application)
          <p class="text-sm"><a class="text-brand-500 underline" href="{{ auth()->user()->can('review finance applications') ? route('staff.finance.show', $application) : '#' }}">{{ $application->reference }}</a> · {{ $application->statusLabel() }}</p>
        @else
          <p class="text-sm text-gray-500">None yet. Their email link opens <a class="underline" href="{{ route('finance.choose') }}" target="_blank" rel="noopener">{{ route('finance.choose') }}</a>, and an application started from it links here.</p>
        @endif
      </div>

      <div class="{{ $card }}">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">{{ $book['label'] }} playbook</h3>
        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-400">@foreach ($book['approach'] as $l)<li>{{ $l }}</li>@endforeach</ul>
        <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">What to offer</p>
        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-400">@foreach ($book['offers'] as $l)<li>{{ $l }}</li>@endforeach</ul>
        <p class="mt-3"><a class="text-sm text-brand-500 underline" href="{{ route('staff.funding.playbook') }}#{{ $p->segment }}">Objections and answers</a></p>
      </div>
    </div>
  </div>
@endsection
