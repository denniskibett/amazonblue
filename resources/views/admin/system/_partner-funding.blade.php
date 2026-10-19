{{-- System › Partner funding: where partners send money (App\Support\PartnerFunding). Its own form, so the
     current-password check applies only here. Editing: super-admin only (route + this view); admins see it read-only.
     An empty field falls back to config/lending.php (.env). Every save writes an audit row and emails every admin. --}}
@php
  $funding = \App\Support\PartnerFunding::all();
  $saved = \App\Support\PartnerFunding::saved();
  $canEdit = auth()->user()?->hasRole('super-admin');
  $fe = $errors->partnerFunding;
  $labels = [
    'bank_name' => ['Bank', 'e.g. I&M Bank'],
    'bank_account_name' => ['Account name', 'As it appears at the bank'],
    'bank_account_number' => ['Account number', null],
    'bank_branch' => ['Branch', null],
    'bank_swift' => ['SWIFT code', '8 or 11 characters, optional'],
    'mpesa_paybill' => ['M-Pesa paybill', 'Bank paybill, e.g. 542542'],
    'mpesa_account' => ['M-Pesa "Account no."', 'For a bank paybill: the bank account number'],
    'reference_prefix' => ['Partner reference prefix', 'Partner 9 quotes AZP-0009'],
    'min_amount' => ['Smallest top-up / withdrawal (KES)', null],
  ];
@endphp
<div id="partner-funding" class="mt-8 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
  <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Partner funding details</h2>
    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
      Partners see these on Add funds and send their money here. A blank field uses the default from the server settings.
      Only the system administrator can change them; every change is logged and emailed to all admins.
    </p>
  </div>

  <div class="space-y-5 p-6">
    @if ($fe->any())
      <x-staff.alert type="err" title="Not saved">Check the fields marked below.</x-staff.alert>
    @endif

    @if ($canEdit)
      <form action="{{ route('system.partner-funding.update') }}" method="POST" class="space-y-5" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
          @foreach ($labels as $key => [$label, $hint])
            <x-staff.field :name="$key" :label="$label" :type="$key === 'min_amount' ? 'number' : 'text'"
              :value="$saved[$key] ?? null" :placeholder="filled($saved[$key] ?? null) ? null : ($funding[$key] ?? null)"
              :hint="filled($saved[$key] ?? null) ? $hint : trim(($hint ? $hint . '. ' : '') . 'Default in use: ' . ($funding[$key] ?? 'none'))"
              :error="$fe->first($key)" />
          @endforeach
        </div>
        <div class="grid grid-cols-1 gap-5 border-t border-gray-200 pt-5 md:grid-cols-2 dark:border-gray-800">
          <x-staff.field name="current_password" label="Your current password" type="password" required
            hint="Needed because this changes where partner money goes." :error="$fe->first('current_password')" autocomplete="current-password" />
        </div>
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
          Save partner funding details
        </button>
      </form>
    @else
      <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-theme-sm md:grid-cols-2">
        @foreach ($labels as $key => [$label])
          <div class="min-w-0">
            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
            <dd class="break-words font-medium text-gray-800 dark:text-white/90">
              {{ filled($funding[$key] ?? null) ? $funding[$key] : 'Not set' }}
              @unless (filled($saved[$key] ?? null))<span class="text-gray-400">(default)</span>@endunless
            </dd>
          </div>
        @endforeach
      </dl>
    @endif
  </div>
</div>
