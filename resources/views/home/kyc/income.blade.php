@extends('home.layouts.kyc', ['step' => 4, 'title' => 'Income & payout'])

@php
  $val = fn ($f) => old($f, $saved[$f] ?? null);
  $isLocked = fn ($f) => in_array($f, $locked, true);
  $type = old('income_type', $saved['income_type'] ?? 'employed');
  $typeLocked = $isLocked('income_type');
  $kes = fn ($v) => filled($v) ? 'KES ' . number_format((float) $v, 2) : null;

  // label, placeholder and input attributes per field, per employment status
  $sets = [
    'employed' => [
      'employer_name' => ['Employer', 'e.g. Safaricom PLC', 'organization'],
      'job_title' => ['Job title', 'e.g. Accountant', 'organization-title'],
      'net_salary' => ['Monthly net salary (KES)', 'After tax, e.g. 45000', null],
      'years' => ['Years with this employer', 'e.g. 3', null],
      'employer_phone' => ['Employer or HR phone (optional)', '07XX XXX XXX', null],
    ],
    'self_employed' => [
      'job_title' => ['What do you do?', 'e.g. Electrician, tailor, boda boda', null],
      'workplace' => ['Where do you work from?', 'e.g. Gikomba market, Nairobi', null],
      'net_salary' => ['Average monthly income (KES)', 'e.g. 30000', null],
      'years' => ['Years doing this', 'e.g. 4', null],
      'employer_name' => ['Trading name (optional)', 'If you use one', null],
    ],
    'business' => [
      'employer_name' => ['Business name', 'As registered or known', 'organization'],
      'business_type' => ['What does the business do?', 'e.g. Hardware shop, transport', null],
      'workplace' => ['Business location', 'e.g. Kenyatta Ave, Nakuru', null],
      'net_salary' => ['Monthly profit you take home (KES)', 'e.g. 80000', null],
      'years' => ['Years in business', 'e.g. 5', null],
      'registration_no' => ['Business registration no. (optional)', 'e.g. BN-ABC123', null],
    ],
    'other' => [
      'job_title' => ['Main source of income', 'e.g. Pension, family support, farming', null],
      'net_salary' => ['Average monthly income (KES)', 'e.g. 20000', null],
      'employer_name' => ['Paid by (optional)', 'e.g. NSSF, a relative', null],
    ],
  ];
  $required = \App\Http\Controllers\HomeController::INCOME_FIELDS;
  $numeric = ['net_salary', 'years'];
  $bankLocked = $isLocked('bank_name');
  $bankName = $val('bank_name');
  $bankIsListed = $bankName === null || in_array($bankName, $banks, true);
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Income &amp; payout</h1>
    <p class="muted">Individual accounts borrow in your own name, as a borrower.</p>
  </div>
  <form class="kyc-form" method="POST" action="{{ route('kyc.income.store') }}" data-validate data-server novalidate id="income-form">
    @csrf
    <div class="kyc-card">
      @if ($typeLocked)
        @include('home.kyc.partials.saved', ['label' => 'Employment status', 'value' => $incomeTypes[$type] ?? $type])
        <input type="hidden" name="income_type" value="{{ $type }}" />
      @else
        <fieldset class="chips">
          <legend>Employment status<span class="req">*</span></legend>
          @foreach ($incomeTypes as $value => $label)
            <input type="radio" name="income_type" id="emp-{{ $loop->index }}" value="{{ $value }}" @checked($type === $value) /><label
              for="emp-{{ $loop->index }}">{{ $label }}</label>
          @endforeach
        </fieldset>
      @endif

      {{-- one set of fields per status; only the chosen one is shown and submitted --}}
      @foreach ($sets as $set => $fields)
        <div class="income-set" data-income-set="{{ $set }}" @if ($set !== $type) hidden @endif>
          <div class="form-row" style="flex-wrap:wrap">
            @foreach ($fields as $field => [$label, $placeholder, $autocomplete])
              @php $req = in_array($field, $required[$set]['required'], true); @endphp
              @if ($isLocked($field) && $set === $type)
                @include('home.kyc.partials.saved', ['label' => preg_replace('/ \(optional\)$/', '', $label), 'value' => $field === 'net_salary' ? $kes($val($field)) : $val($field)])
              @else
                <div class="field @error($field) invalid @enderror" style="flex:1 1 240px">
                  <label for="k-{{ $set }}-{{ $field }}">{{ $label }}@if ($req)<span class="req">*</span>@endif</label
                  ><input class="input" id="k-{{ $set }}-{{ $field }}" name="{{ $field }}"
                    type="{{ in_array($field, $numeric, true) ? 'number' : ($field === 'employer_phone' ? 'tel' : 'text') }}"
                    @if (in_array($field, $numeric, true)) inputmode="numeric" min="0" step="1" @endif
                    @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                    placeholder="{{ $placeholder }}" value="{{ $set === $type ? $val($field) : '' }}"
                    @if ($req) data-required @endif
                  /><span class="error">{{ $errors->first($field) ?: 'This is required.' }}</span>
                </div>
              @endif
            @endforeach
          </div>
        </div>
      @endforeach

      @if ($isLocked('source_of_funds'))
        @include('home.kyc.partials.saved', ['label' => 'Main source of funds', 'value' => $val('source_of_funds')])
      @else
        <div class="field @error('source_of_funds') invalid @enderror">
          <label for="k-src">Main source of funds<span class="req">*</span></label
          ><select class="input" id="k-src" name="source_of_funds" required>
            @foreach (['Salary', 'Business income', 'Rental income', 'Other'] as $source)
              <option @selected(($val('source_of_funds') ?? ['employed' => 'Salary', 'self_employed' => 'Business income', 'business' => 'Business income'][$type] ?? 'Other') === $source)>{{ $source }}</option>
            @endforeach
          </select>
        </div>
      @endif
    </div>

    <div class="kyc-card">
      <h3 class="card-h">Where should we send your funds?</h3>
      @if ($isLocked('mpesa_number'))
        @include('home.kyc.partials.saved', ['label' => 'M-Pesa number', 'value' => $val('mpesa_number')])
      @else
        <div class="field @error('mpesa_number') invalid @enderror">
          <label for="k-mpesa">M-Pesa number<span class="req">*</span></label
          ><input class="input" id="k-mpesa" name="mpesa_number" type="tel" autocomplete="tel" placeholder="07XX XXX XXX"
            value="{{ $val('mpesa_number') ?? $user->phone }}" required
          /><span class="error">{{ $errors->first('mpesa_number') ?: 'Enter your M-Pesa number.' }}</span>
        </div>
      @endif

      @if ($bankLocked)
        @include('home.kyc.partials.saved', ['label' => 'Bank', 'value' => $bankName . ' · ' . $val('bank_account')])
      @else
        <div class="form-row">
          <div class="field @error('bank_name') invalid @enderror">
            <label for="k-bank">Bank <span class="muted">(optional)</span></label
            ><select class="input" id="k-bank" name="bank_name">
              <option value="">No bank account</option>
              @foreach ($banks as $bank)
                <option @selected($bankName === $bank)>{{ $bank }}</option>
              @endforeach
              <option value="{{ \App\Support\Banks::OTHER }}" @selected(! $bankIsListed || old('bank_name') === \App\Support\Banks::OTHER)>Other bank</option>
            </select>
            <span class="error">{{ $errors->first('bank_name') }}</span>
          </div>
          <div class="field @error('bank_account') invalid @enderror">
            <label for="k-acct">Account number <span class="muted">(optional)</span></label
            ><input class="input" id="k-acct" name="bank_account" type="text" inputmode="numeric" value="{{ $val('bank_account') }}"
            /><span class="error">{{ $errors->first('bank_account') }}</span>
          </div>
        </div>
        <div class="field @error('bank_other') invalid @enderror" id="bank-other" @if ($bankIsListed && old('bank_name') !== \App\Support\Banks::OTHER) hidden @endif>
          <label for="k-bank-other">Bank name</label
          ><input class="input" id="k-bank-other" name="bank_other" type="text" value="{{ old('bank_other', $bankIsListed ? '' : $bankName) }}" placeholder="Type your bank's name"
          /><span class="error">{{ $errors->first('bank_other') ?: 'Type the name of your bank.' }}</span>
        </div>
      @endif
    </div>
    <div class="kyc-nav">
      <a href="{{ route('kyc.documents') }}">Back</a><button class="btn btn-primary" type="submit">Continue</button>
    </div>
  </form>

  <script>
    // show only the chosen status's fields: hidden sets are disabled (not submitted, not required)
    (function () {
      var form = document.getElementById('income-form');
      function apply(type) {
        form.querySelectorAll('[data-income-set]').forEach(function (set) {
          var on = set.getAttribute('data-income-set') === type;
          set.hidden = !on;
          set.querySelectorAll('input').forEach(function (input) {
            input.disabled = !on;
            input.required = on && input.hasAttribute('data-required');
          });
        });
      }
      var checked = form.querySelector('input[name=income_type]:checked') || form.querySelector('input[type=hidden][name=income_type]');
      apply(checked ? checked.value : 'employed');
      form.querySelectorAll('input[name=income_type]').forEach(function (radio) {
        radio.addEventListener('change', function () { apply(radio.value); });
      });
      var bank = document.getElementById('k-bank'), other = document.getElementById('bank-other');
      if (bank && other) {
        var sync = function () {
          var show = bank.value === @json(\App\Support\Banks::OTHER);
          other.hidden = !show;
          other.querySelector('input').required = show;
        };
        bank.addEventListener('change', sync);
        sync();
      }
    })();
  </script>
@endsection
