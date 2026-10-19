{{-- Page title row for the staff desk pages. @include('staff.desk._header', ['title' => ..., 'sub' => ..., 'actions' => html]) --}}
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
  <div class="min-w-0">
    <h1 class="font-sora text-title-sm font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h1>
    @isset($sub)<p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $sub }}</p>@endisset
  </div>
  @isset($actions)<div class="flex flex-wrap gap-2 print:hidden">{!! $actions !!}</div>@endisset
</div>
@php
  $done = [
    'approved' => 'Loan approved through the existing loan update. It is now in the disbursement queue.',
    'rejected' => 'Loan rejected. The reason is saved on the loan.',
    'disbursed' => 'Disbursement recorded through the existing disbursement path. The loan is now disbursed.',
    'repaid' => 'Repayment recorded through the existing repayment path.',
  ][request('done')] ?? null;
@endphp
<div class="mb-6 space-y-3 empty:hidden print:hidden">
  @if (session('success'))<x-staff.alert type="ok">{{ session('success') }}</x-staff.alert>@endif
  @if (session('error'))<x-staff.alert type="err">{{ session('error') }}</x-staff.alert>@endif
  @if ($done)<x-staff.alert type="ok">{{ $done }}@if (request('ref')) <span class="font-medium">#{{ (int) request('ref') }}</span>@endif</x-staff.alert>@endif
</div>
