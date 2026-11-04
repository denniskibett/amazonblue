{{-- People and places (owner, 4 Nov 2026): who pays us back, by how they earn, literacy, education, loan size against
     income, time with us and age; places; and income gaps. App\Services\Analytics\PeopleInsights. Religion,
     ethnicity, disability and health are never used. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]';
  $th = 'px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400';
  $td = 'px-4 py-3 text-sm text-gray-700 dark:text-gray-300';
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 0);
  $bar = fn ($v) => $v === null ? 'bg-gray-300' : ($v >= 85 ? 'bg-success-500' : ($v >= 60 ? 'bg-brand-500' : ($v >= 40 ? 'bg-orange-500' : 'bg-error-500')));
  $known = fn ($key, $empty) => $all->filter(fn ($r) => ! in_array($r->{$key}, (array) $empty, true) && $r->{$key} !== null)->count();
  $coverage = [
    ['How they earn', $known('work', 'Not told us')], ['Income', $all->filter(fn ($r) => $r->income > 0)->count()], ['Education', $known('education_band', 'Not told us')],
    ['Age', $all->whereNotNull('age')->count()], ['Place', $all->whereNotNull('place')->count()],
  ];
  $total = max(1, $all->count());
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'People and places', 'sub' => 'Who pays us back, and from where: on-time rate by how people earn, how they use the app, their loan against their income, how long they have been with us, their age and where they live.', 'metrics' => false])

  <div class="mb-6 {{ $card }}">
    <div class="flex flex-col gap-4">
      <div><h3 class="font-semibold text-gray-800 dark:text-white/90">What we know about {{ $all->count() }} borrowers</h3>
        <p class="text-theme-sm text-gray-500">The fewer we know, the less these figures say. Ask for income, work and address at application and on the profile.</p></div>
      <div class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-5">
        @foreach ($coverage as [$l, $n])
          <div><div class="flex justify-between text-theme-xs text-gray-500"><span>{{ $l }}</span><span>{{ round($n / $total * 100) }}%</span></div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5"><div class="h-2 rounded-full {{ $n / $total >= 0.7 ? 'bg-success-500' : ($n / $total >= 0.3 ? 'bg-orange-500' : 'bg-error-500') }}" style="width: {{ $n / $total * 100 }}%"></div></div></div>
        @endforeach
      </div>
    </div>
  </div>

  <x-staff.tabs :tabs="['segments' => 'Who pays', 'places' => ['Places', $places['known']], 'gaps' => ['Income gaps', $gaps['big']->count() + $gaps['payday']->count()], 'all' => ['Borrowers', $all->count()]]">
    <x-staff.tab name="segments">
      <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-2 xl:grid-cols-3">
        @foreach ($dims as $key => $label)
          <div>
            <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">{{ $label }}</h3>
            @foreach ($segments[$key] as $group => $s)
              <div class="mt-2.5">
                <div class="flex justify-between text-theme-sm"><span class="text-gray-700 dark:text-gray-300">{{ $group ?: 'Not told us' }} <span class="text-theme-xs text-gray-400">· {{ $s['borrowers'] }} people, {{ $s['loans'] }} loans</span></span>
                  <b class="font-outfit text-gray-800 dark:text-white/90">{{ $s['on_time'] !== null ? $s['on_time'] . '%' : '—' }}</b></div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5"><div class="h-2 rounded-full {{ $bar($s['on_time']) }}" style="width: {{ $s['on_time'] ?? 0 }}%"></div></div>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
      <p class="border-t border-gray-100 px-5 py-4 text-theme-xs text-gray-500 dark:border-gray-800">On time: share of finished loans repaid on time, weighted by loans (borrower behaviour profiles, refreshed nightly). Digital literacy counts how much of the journey they did themselves in the app: their KYC, signing, a request and their profile (3–4 Fluent, 2 Functional, 0–1 Basic). It helps us explain, never to refuse. Religion, tribe, disability and health are never used.</p>
    </x-staff.tab>

    <x-staff.tab name="places">
      <div class="overflow-x-auto"><table class="w-full min-w-[640px]">
        <thead><tr class="border-b border-gray-100 dark:border-gray-800"><th class="{{ $th }} pl-6">Place</th><th class="{{ $th }} text-right">Borrowers</th><th class="{{ $th }} text-right">Loans</th><th class="{{ $th }}">On time</th><th class="{{ $th }}"></th></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($places['rows'] as $p)
            <tr><td class="{{ $td }} pl-6 font-medium">{{ $p['place'] }}</td><td class="{{ $td }} text-right">{{ $p['borrowers'] }}</td><td class="{{ $td }} text-right">{{ $p['loans'] }}</td>
              <td class="{{ $td }}"><div class="flex items-center gap-3"><div class="h-2 w-40 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5"><div class="h-2 rounded-full {{ $bar($p['on_time']) }}" style="width: {{ $p['on_time'] ?? 0 }}%"></div></div><span class="font-outfit">{{ $p['on_time'] !== null ? $p['on_time'] . '%' : '—' }}</span></div></td>
              <td class="{{ $td }}">@if ($p['on_time'] !== null)<x-staff.pill :tone="$p['on_time'] >= 85 ? 'green' : ($p['on_time'] >= 60 ? 'blue' : 'red')">{{ $p['on_time'] >= 85 ? 'Trust more' : ($p['on_time'] >= 60 ? 'Normal' : 'Tighten limits') }}</x-staff.pill>@endif</td></tr>
          @empty
            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No addresses recorded yet.</td></tr>
          @endforelse
        </tbody>
      </table></div>
      <p class="border-t border-gray-100 px-6 py-4 text-theme-xs text-gray-500 dark:border-gray-800">Place is known for {{ $places['known'] }} of {{ $places['total'] }} borrowers (address city, else their business's town or county). A place is a hint, not a verdict: we change limits by place, we don't refuse by place.</p>
    </x-staff.tab>

    <x-staff.tab name="gaps">
      <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-3">
        @foreach ([['Loans bigger than a month\'s income', $gaps['big']->count(), 'average loan above the income they told us'], ['Salaried, paying far from payday', $gaps['payday']->count(), 'repayments 7+ days from their payday on average'],
          ['Borrowing with no income on file', $gaps['no_income'], 'of ' . $gaps['total'] . ' borrowers']] as [$l, $n, $s])
          <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800"><p class="text-theme-sm text-gray-500">{{ $l }}</p><p class="mt-1 font-outfit text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $n }}</p><p class="text-theme-xs text-gray-500">{{ $s }}</p></div>
        @endforeach
      </div>
      <div class="overflow-x-auto"><table class="w-full min-w-[820px]">
        <thead><tr class="border-b border-gray-100 dark:border-gray-800">@foreach (['Borrower', 'Works', 'Income they told us', 'Average loan', 'Loan ÷ income', 'On time', 'Score', ''] as $h)<th class="{{ $th }} {{ $loop->first ? 'pl-6' : '' }}">{{ $h }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($gaps['big']->merge($gaps['payday'])->unique('id') as $b)
            <tr><td class="{{ $td }} pl-6"><a class="font-medium text-brand-500 hover:underline" href="{{ route('staff.people.person', $b->id) }}">{{ $b->name }}</a>@isset($b->payday_gap)<span class="block text-theme-xs text-orange-600">pays ~{{ $b->payday_gap }} days from payday</span>@endisset</td>
              <td class="{{ $td }}">{{ $b->work }}</td><td class="{{ $td }} font-outfit">{{ $b->income > 0 ? $kes($b->income) : '—' }}</td><td class="{{ $td }} font-outfit">{{ $b->avg_loan > 0 ? $kes($b->avg_loan) : '—' }}</td>
              <td class="{{ $td }}">@if ($b->loan_to_income !== null)<x-staff.pill :tone="$b->loan_to_income > 1 ? 'red' : 'gray'">{{ $b->loan_to_income }}×</x-staff.pill>@endif</td>
              <td class="{{ $td }}">{{ $b->on_time_rate !== null ? round($b->on_time_rate) . '%' : '—' }}</td><td class="{{ $td }}">{{ $b->score ?? '—' }}</td>
              <td class="{{ $td }}"><x-staff.pill :tone="$b->loan_to_income > 1.5 ? 'red' : 'amber'">{{ $b->loan_to_income > 1.5 ? 'Cut the limit' : 'Ask for proof of income' }}</x-staff.pill></td></tr>
          @endforeach
        </tbody>
      </table></div>
      <p class="border-t border-gray-100 px-6 py-4 text-theme-xs text-gray-500 dark:border-gray-800">Income seen on M-Pesa needs their statements; until we collect them, gaps compare loan sizes with the income they told us and salaried repayments with their payday.</p>
    </x-staff.tab>

    <x-staff.tab name="all">
      <div class="overflow-x-auto"><table class="w-full min-w-[900px]">
        <thead><tr class="border-b border-gray-100 dark:border-gray-800">@foreach (['Borrower', 'Works', 'Literacy', 'Education', 'Age', 'Place', 'Loans', 'On time', 'Score'] as $h)<th class="{{ $th }} {{ $loop->first ? 'pl-6' : '' }}">{{ $h }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($all->sortByDesc('loans') as $b)
            <tr><td class="{{ $td }} pl-6"><a class="font-medium text-brand-500 hover:underline" href="{{ route('staff.people.person', $b->id) }}">{{ $b->name }}</a></td><td class="{{ $td }}">{{ $b->work }}</td><td class="{{ $td }}">{{ $b->literacy }}</td>
              <td class="{{ $td }}">{{ $b->education_band }}</td><td class="{{ $td }}">{{ $b->age ?? '—' }}</td><td class="{{ $td }}">{{ $b->place ?? '—' }}</td><td class="{{ $td }}">{{ (int) $b->loans }}</td>
              <td class="{{ $td }}">{{ $b->on_time_rate !== null ? round($b->on_time_rate) . '%' : '—' }}</td><td class="{{ $td }} font-outfit">{{ $b->score ?? '—' }}</td></tr>
          @endforeach
        </tbody>
      </table></div>
    </x-staff.tab>
  </x-staff.tabs>
@endsection
