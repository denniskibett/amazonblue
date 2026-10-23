@extends('home.layouts.account', ['title' => 'Complete your profile', 'tab' => 'profile'])

{{-- Complete your profile (owner, 23 Oct 2026): fill in what's missing; details already on file are read-only. --}}
@php $kes = fn ($n) => 'KES ' . number_format((float) $n, 2); @endphp

@section('content')
  <div class="acct-head">
    <h1>Complete your profile</h1>
    <p>We use these to set your limit and to remind you around your payday.</p>
  </div>

  @if ($missing)
    <x-az.alert type="info" title="Still missing">{{ ucfirst(implode(', ', $missing)) }}.</x-az.alert>
  @else
    <x-az.alert type="ok" title="Your profile is complete">You can update your pay schedule any time.</x-az.alert>
  @endif

  <section class="a-card" style="margin-top:16px">
    <form method="POST" action="{{ route('account.details.store') }}">
      @csrf
      <div class="a-sec-h"><h2>Income</h2></div>
      @if ($b->income_type)
        <dl class="a-break"><dt>Income type</dt><dd>{{ $types[$b->income_type] ?? ucfirst(str_replace('_', ' ', $b->income_type)) }}</dd></dl>
      @else
        <x-az.field name="income_type" label="How do you earn?" type="select" :options="$types" placeholder="Choose one" />
      @endif
      @if ((float) $b->net_salary > 0)
        <dl class="a-break"><dt>Monthly take-home</dt><dd>{{ $kes($b->net_salary) }}</dd></dl>
      @else
        <x-az.field name="net_salary" label="Monthly take-home income (KES)" type="number" min="1" step="1" hint="After tax and deductions. Our team may ask for a payslip or M-Pesa statement." />
      @endif
      @if ($b->employer_name || $b->workplace)
        <dl class="a-break"><dt>Employer or business</dt><dd>{{ $b->employer_name ?: $b->workplace }}</dd></dl>
      @else
        <x-az.field name="employer_name" label="Employer or business name" />
        <x-az.field name="job_title" label="Your job or what the business does" />
      @endif
      @if ($b->income_type && (float) $b->net_salary > 0 && ($b->employer_name || $b->workplace))
        <p class="muted" style="font-size:12.5px">To change these, <a href="{{ route('account.help') }}">talk to us</a>: they were checked when you were verified.</p>
      @endif

      <div class="a-sec-h" style="margin-top:18px"><h2>When you are paid</h2></div>
      <div x-data="{ f: @js(old('pay_frequency', $b->pay_frequency ?? '')) }">
        <div class="field az-field">
          <label for="f-pay_frequency">How often are you paid?<span class="az-req" aria-hidden="true"> *</span></label>
          <select id="f-pay_frequency" name="pay_frequency" class="input" required x-model="f">
            <option value="">Choose one</option>
            @foreach (\App\Models\Borrower::PAY_FREQUENCIES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
          </select>
          @error('pay_frequency')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div x-show="f === 'monthly'" x-cloak>
          <x-az.field name="pay_day" label="Day of the month you are paid" type="number" min="1" max="31" :value="$b->pay_day" hint="For example 28. If it moves, put the usual day." />
        </div>
      </div>
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px">Save</button>
    </form>
  </section>
@endsection
