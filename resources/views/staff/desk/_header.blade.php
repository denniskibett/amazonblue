{{-- Page title row for the staff desk pages, TailAdmin style, followed by the page's cards (App\Support\PageMetrics).
     @include('staff.desk._header', ['title' => ..., 'sub' => ..., 'actions' => html, 'metrics' => page key | false]) --}}
@php
  $metricsKey = $metrics ?? ([
    'staff.partner-payouts' => 'partner-payouts', 'staff.inbox' => 'inbox', 'staff.wallets' => 'wallets', 'staff.roles' => 'roles',
    'staff.end-of-day' => 'end-of-day', 'staff.products' => 'products', 'staff.approvals' => 'approvals', 'staff.portfolio' => 'portfolio',
    'staff.disbursements.queue' => 'disbursements-queue', 'staff.sign-offs' => 'sign-offs', 'staff.payments' => 'payments',
    'staff.repayments.receive' => 'receive-payment', 'staff.requests' => 'requests', 'staff.collections' => 'collections',
  ][\Illuminate\Support\Facades\Route::currentRouteName()] ?? null);
@endphp
<x-ui.page-header :title="$title" :sub="$sub ?? null">
  @isset($actions)<div class="flex flex-wrap gap-2 print:hidden">{!! $actions !!}</div>@endisset
</x-ui.page-header>
@if ($metricsKey)<x-ui.page-metrics :page="$metricsKey" />@endif
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
