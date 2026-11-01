{{-- Borrower testimonials to approve or hide (owner, 1 Nov 2026). Only approved ones show on the website,
     latest six; a borrower who edits theirs sends it back to pending. --}}
@extends('layouts.app')

@php
  $btn = 'inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600';
  $ghost = 'inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
  $tones = ['pending' => 'amber', 'approved' => 'green', 'hidden' => 'gray'];
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Testimonials', 'sub' => 'What borrowers wrote about us. Only approved ones show on the website (the latest six).'])

  <x-staff.table title="Testimonials" caption="Borrower testimonials" :headers="['Borrower', 'Rating', 'Their words', 'Shown as', 'Status', '']">
    @forelse ($testimonials as $t)
      <tr>
        <td data-sort-value="{{ $t->user?->name }}">
          <x-staff.person :name="$t->user?->name ?? 'Deleted user'" :id="$t->user_id" :sub="'Sent ' . $t->updated_at?->format('j M Y')" />
          @if ($t->loan_id)<a class="mt-1 block text-theme-xs text-brand-500 hover:underline" href="{{ route('loans.show', $t->loan_id) }}">Open loan #{{ $t->loan_id }}</a>@endif
        </td>
        <td data-sort-value="{{ $t->rating }}"><span class="text-amber-500" aria-label="{{ $t->rating }} out of 5">{{ str_repeat('★', $t->rating) }}</span><span class="text-gray-300 dark:text-gray-600" aria-hidden="true">{{ str_repeat('★', 5 - $t->rating) }}</span></td>
        <td class="max-w-sm !whitespace-normal text-theme-xs text-gray-600 dark:text-gray-400"><span class="line-clamp-3" title="{{ $t->body }}">{{ $t->body }}</span></td>
        <td class="text-theme-xs">
          <span class="font-medium text-gray-800 dark:text-white/90">{{ $t->display_name }}</span>
          @if ($t->display_role)<span class="block text-gray-500 dark:text-gray-400">{{ $t->display_role }}</span>@endif
        </td>
        <td data-filter-value="{{ ucfirst($t->status) }}">
          <x-staff.pill :tone="$tones[$t->status] ?? 'gray'" dot>{{ ucfirst($t->status) }}</x-staff.pill>
          @if ($t->status === 'approved' && $t->approved_at)<span class="mt-1 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $t->approver?->name ?? 'Staff' }}, {{ $t->approved_at->format('j M Y') }}</span>@endif
        </td>
        <td class="text-right" data-actions>
          <div class="flex justify-end gap-2">
            @if ($t->status !== 'approved')
              <form method="POST" action="{{ route('staff.testimonials.decide', $t) }}">@csrf<input type="hidden" name="decision" value="approve"><button type="submit" class="{{ $btn }}">Approve</button></form>
            @endif
            @if ($t->status !== 'hidden')
              <form method="POST" action="{{ route('staff.testimonials.decide', $t) }}">@csrf<input type="hidden" name="decision" value="hide"><button type="submit" class="{{ $ghost }}">Hide</button></form>
            @endif
          </div>
        </td>
      </tr>
    @empty
      <tr data-table-skip><td colspan="6"><x-staff.empty title="Nothing yet" message="No borrower has written a testimonial yet." /></td></tr>
    @endforelse
  </x-staff.table>
@endsection
