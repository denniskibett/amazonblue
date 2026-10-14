@extends('home.layouts.kyc', ['step' => 4, 'title' => 'Income & payout'])

@section('content')
  <div class="kyc-head">
    <h1>Income &amp; payout</h1>
    <p class="muted">Individual accounts borrow in your own name, as a borrower.</p>
  </div>
  <form class="kyc-form" method="POST" action="{{ route('kyc.income.store') }}" data-validate data-server novalidate>
    @csrf
    <div class="kyc-card">
      <fieldset class="chips">
        <legend>Employment status<span class="req">*</span></legend>
        @foreach ($incomeTypes as $value => $label)
          <input type="radio" name="income_type" id="emp-{{ $loop->index }}" value="{{ $value }}"
            @checked(old('income_type', $borrower?->income_type ?? 'employed') === $value) /><label
            for="emp-{{ $loop->index }}">{{ $label }}</label>
        @endforeach
      </fieldset>
      <div class="form-row">
        <div class="field @error('employer_name') invalid @enderror">
          <label for="k-emp">Employer or business name<span class="req">*</span></label
          ><input class="input" id="k-emp" name="employer_name" type="text" autocomplete="organization"
            placeholder="Who pays you?" value="{{ old('employer_name', $borrower?->employer_name) }}" required
          /><span class="error">{{ $errors->first('employer_name') ?: 'Enter your employer or business name.' }}</span>
        </div>
        <div class="field @error('net_salary') invalid @enderror">
          <label for="k-inc">Monthly net income (KES)<span class="req">*</span></label
          ><input class="input" id="k-inc" name="net_salary" type="number" inputmode="numeric" min="0" step="1"
            placeholder="e.g. 85000" value="{{ old('net_salary', $borrower?->net_salary ? (int) $borrower->net_salary : '') }}" required
          /><span class="error">{{ $errors->first('net_salary') ?: 'Enter your monthly income after tax.' }}</span>
        </div>
      </div>
      <div class="form-row">
        <div class="field @error('job_title') invalid @enderror">
          <label for="k-job">Job title or role<span class="req">*</span></label
          ><input class="input" id="k-job" name="job_title" type="text" autocomplete="organization-title"
            placeholder="e.g. Accountant, Owner" value="{{ old('job_title', $borrower?->job_title) }}" required
          /><span class="error">{{ $errors->first('job_title') ?: 'Enter your job title or role.' }}</span>
        </div>
        <div class="field @error('years') invalid @enderror">
          <label for="k-yrs">Years in this role or business<span class="req">*</span></label
          ><input class="input" id="k-yrs" name="years" type="number" inputmode="numeric" min="0" max="60" step="1"
            placeholder="e.g. 3" value="{{ old('years', $years) }}" required
          /><span class="error">{{ $errors->first('years') ?: 'Enter the number of years.' }}</span>
        </div>
      </div>
      <div class="field @error('source_of_funds') invalid @enderror">
        <label for="k-src">Main source of funds<span class="req">*</span></label
        ><select class="input" id="k-src" name="source_of_funds" required>
          @foreach (['Salary', 'Business income', 'Rental income', 'Other'] as $source)
            <option @selected(old('source_of_funds', $sourceOfFunds ?? 'Salary') === $source)>{{ $source }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="kyc-card">
      <h3 class="card-h">Where should we send your funds?</h3>
      <div class="field @error('mpesa_number') invalid @enderror">
        <label for="k-mpesa">M-Pesa number<span class="req">*</span></label
        ><input class="input" id="k-mpesa" name="mpesa_number" type="tel" autocomplete="tel" placeholder="07XX XXX XXX"
          value="{{ old('mpesa_number', $mpesa?->mobile_number ?? $user->phone) }}" required
        /><span class="error">{{ $errors->first('mpesa_number') ?: 'Enter your M-Pesa number.' }}</span>
      </div>
      <div class="form-row">
        <div class="field @error('bank_name') invalid @enderror">
          <label for="k-bank">Bank <span class="muted">(optional)</span></label
          ><input class="input" id="k-bank" name="bank_name" type="text" placeholder="e.g. KCB"
            value="{{ old('bank_name', $bank?->bank_name) }}"
          /><span class="error">{{ $errors->first('bank_name') }}</span>
        </div>
        <div class="field @error('bank_account') invalid @enderror">
          <label for="k-acct">Account number <span class="muted">(optional)</span></label
          ><input class="input" id="k-acct" name="bank_account" type="text" inputmode="numeric"
            value="{{ old('bank_account', $bank?->account_number) }}"
          /><span class="error">{{ $errors->first('bank_account') }}</span>
        </div>
      </div>
    </div>
    <div class="kyc-nav">
      <a href="{{ route('kyc.documents') }}">Back</a><button class="btn btn-primary" type="submit">Continue</button>
    </div>
  </form>
@endsection
