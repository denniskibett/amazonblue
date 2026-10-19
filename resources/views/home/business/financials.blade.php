@extends('home.business.layout', ['step' => 4, 'title' => 'Financials'])

@php
  $locked = ! $business->isEditable();
  $bankName = old('bank_name', $business->bank_name);
  $listed = $bankName === null || in_array($bankName, $banks, true);
  $other = \App\Support\Banks::OTHER;
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Financials</h1>
    <p class="muted">Rough figures are fine. We confirm them from your bank statements later.</p>
  </div>
  <form class="kyc-form" method="POST" action="{{ route('account.business.financials.store', $business) }}" data-validate data-server novalidate>
    @csrf
    <div class="kyc-card">
      <div class="form-row">
        <div class="field @error('monthly_revenue') invalid @enderror">
          <label for="f-rev">Average monthly revenue (KES)<span class="req">*</span></label
          ><input class="input num" id="f-rev" name="monthly_revenue" type="number" min="0" step="1" inputmode="numeric" placeholder="e.g. 450000"
            value="{{ old('monthly_revenue', $business->monthly_revenue !== null ? (int) $business->monthly_revenue : '') }}" required @disabled($locked)
          /><span class="error">{{ $errors->first('monthly_revenue') ?: 'Enter the average monthly revenue.' }}</span>
        </div>
        <div class="field @error('employees') invalid @enderror">
          <label for="f-emp">Employees<span class="req">*</span></label
          ><input class="input num" id="f-emp" name="employees" type="number" min="0" step="1" inputmode="numeric" placeholder="0 if it's just you"
            value="{{ old('employees', $business->employees) }}" required @disabled($locked)
          /><span class="error">{{ $errors->first('employees') ?: 'Enter the number of employees.' }}</span>
        </div>
      </div>
    </div>
    <div class="kyc-card">
      <span class="flabel">Business bank account</span>
      <div class="form-row">
        <div class="field @error('bank_name') invalid @enderror">
          <label for="f-bank">Bank<span class="req">*</span></label
          ><select class="input" id="f-bank" name="bank_name" required @disabled($locked)>
            <option value="">Choose the bank</option>
            @foreach ($banks as $bank)
              <option @selected($bankName === $bank)>{{ $bank }}</option>
            @endforeach
            <option value="{{ $other }}" @selected(! $listed || $bankName === $other)>Other bank</option>
          </select><span class="error">{{ $errors->first('bank_name') ?: 'Choose the bank.' }}</span>
        </div>
        <div class="field @error('bank_account') invalid @enderror">
          <label for="f-acct">Account number<span class="req">*</span></label
          ><input class="input" id="f-acct" name="bank_account" type="text" inputmode="numeric" value="{{ old('bank_account', $business->bank_account) }}" required @disabled($locked)
          /><span class="error">{{ $errors->first('bank_account') ?: 'Enter the account number.' }}</span>
        </div>
      </div>
      <div class="field @error('bank_other') invalid @enderror" id="bank-other" @if ($listed && $bankName !== $other) hidden @endif>
        <label for="f-bank-other">Bank name</label
        ><input class="input" id="f-bank-other" name="bank_other" type="text" value="{{ old('bank_other', $listed ? '' : $bankName) }}" placeholder="Type the bank's name" @disabled($locked)
        /><span class="error">{{ $errors->first('bank_other') ?: 'Type the name of the bank.' }}</span>
      </div>
      <p class="muted biz-small">The account should be in the business's name. Upload 6 months of statements in Documents if you have them.</p>
    </div>
    <div class="kyc-nav">
      <a href="{{ route('account.business.documents', $business) }}">Back</a>
      @if ($locked)
        <a class="btn btn-primary" href="{{ route('account.business.review', $business) }}">Next</a>
      @else
        <button class="btn btn-primary" type="submit">Continue</button>
      @endif
    </div>
  </form>
  <script>
    (function () {
      var s = document.getElementById('f-bank'), box = document.getElementById('bank-other');
      if (!s || !box) return;
      s.addEventListener('change', function () { box.hidden = s.value !== @json($other); });
    })();
  </script>
@endsection
