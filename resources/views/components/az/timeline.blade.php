{{-- Steps of a journey. Items use the LoanProgress shape:
     [['label' => 'Approved', 'detail' => '...', 'date' => '2026-10-01', 'done' => true, 'current' => false, 'tone' => 'red'|null], ...] --}}
@props(['items' => []])
<ol {{ $attributes->class(['a-timeline']) }}>
  @foreach ($items as $step)
    @php
      $red = ($step['tone'] ?? null) === 'red';
      $done = (bool) ($step['done'] ?? false);
      $date = ! empty($step['date']) ? \Illuminate\Support\Carbon::parse($step['date'])->format('j M Y') : null;
    @endphp
    <li class="{{ $red ? 'red' : ($done ? 'done' : (($step['current'] ?? false) ? 'current' : '')) }}">
      <span class="dot">@if ($done && ! $red)<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>@else{{ $loop->iteration }}@endif</span>
      <div>
        <b>{{ $step['label'] ?? '' }}@if ($date) <small>· {{ $date }}</small>@endif</b>
        @if (! empty($step['detail']))<span>{{ $step['detail'] }}</span>@endif
      </div>
    </li>
  @endforeach
</ol>
