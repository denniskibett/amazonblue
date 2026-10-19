{{-- Customer-facing business status pill. $business --}}
@php
  [$text, $tone] = [
    'draft' => ['In progress', 'sand'],
    'submitted' => ['Under review', 'amber'],
    'approved' => ['Verified', 'ok'],
    'rejected' => ['Needs attention', 'err'],
  ][$business->status] ?? [ucfirst($business->status), 'sand'];
@endphp
<span class="biz-pill {{ $tone }}">{{ $text }}</span>
