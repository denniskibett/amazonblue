@extends('home.business.layout', ['step' => 1, 'title' => 'Business details'])

@php
  $val = fn ($f, $default = null) => old($f, $business->{$f} ?? $default);
  $type = old('registration_type', $business->registration_type);
  $disabled = $business->exists && ! $business->isEditable();
  $field = function ($name, $label, $attrs = '', $type = 'text', $required = true, $placeholder = '') use ($val, $errors, $disabled) {
    $v = $type === 'date' ? old($name, optional($val($name))->toDateString() ?? '') : $val($name);
    $id = 'b-' . str_replace('_', '-', $name);
    return '<div class="field ' . ($errors->has($name) ? 'invalid' : '') . '">'
      . '<label for="' . $id . '">' . e($label) . ($required ? '<span class="req">*</span>' : ' <span class="muted">(optional)</span>') . '</label>'
      . '<input class="input" id="' . $id . '" name="' . $name . '" type="' . $type . '" value="' . e($v) . '" placeholder="' . e($placeholder) . '" ' . ($required ? 'required ' : '') . ($disabled ? 'disabled ' : '') . $attrs . ' />'
      . '<span class="error">' . e($errors->first($name) ?: 'Enter ' . lcfirst($label) . '.') . '</span></div>';
  };
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Business details</h1>
    <p class="muted">As registered with the Business Registration Service.</p>
  </div>
  <form class="kyc-form" method="POST" action="{{ $business->exists ? route('account.business.details.update', $business) : route('account.business.store') }}" data-validate data-server novalidate>
    @csrf
    <div class="kyc-card">
      <fieldset class="chips @error('registration_type') invalid @enderror">
        <legend>Business type<span class="req">*</span></legend>
        @foreach (\App\Models\Business::REGISTRATION_TYPES as $key => $label)
          <input type="radio" name="registration_type" id="rt-{{ $key }}" value="{{ $key }}" @checked($type === $key) @disabled($disabled) required /><label for="rt-{{ $key }}">{{ $label }}</label>
        @endforeach
      </fieldset>
      @error('registration_type')<span class="biz-err">{{ $message }}</span>@enderror
      <div class="form-row">
        {!! $field('legal_name', 'Registered name', 'autocomplete="organization"', 'text', true, 'e.g. Rift Agro Traders Ltd') !!}
        {!! $field('trading_name', 'Trading name', '', 'text', false, 'If different') !!}
      </div>
      <div class="form-row">
        {!! $field('registration_number', 'Registration number', 'autocomplete="off"', 'text', true, 'e.g. PVT-AB12CD3 or BN-XY45ZQ8') !!}
        {!! $field('kra_pin', 'Business KRA PIN', 'autocomplete="off" maxlength="11"', 'text', true, 'P051234567X') !!}
      </div>
      <div class="form-row">
        {!! $field('date_registered', 'Date registered', 'max="' . now()->toDateString() . '"', 'date') !!}
        <div class="field @error('industry') invalid @enderror">
          <label for="b-industry">Industry<span class="req">*</span></label
          ><select class="input" id="b-industry" name="industry" required @disabled($disabled)>
            <option value="">Choose one</option>
            @foreach (\App\Models\Business::INDUSTRIES as $industry)
              <option @selected($val('industry') === $industry)>{{ $industry }}</option>
            @endforeach
          </select><span class="error">{{ $errors->first('industry') ?: 'Choose the industry.' }}</span>
        </div>
      </div>
      @unless ($business->exists)
        <div class="field @error('your_role') invalid @enderror">
          <label for="b-role">Your role<span class="req">*</span></label
          ><select class="input" id="b-role" name="your_role" required>
            @foreach (\App\Models\BusinessMember::ROLES as $key => $label)
              <option value="{{ $key }}" @selected(old('your_role', 'director') === $key)>{{ $label }}</option>
            @endforeach
          </select><span class="error">{{ $errors->first('your_role') ?: 'Choose your role.' }}</span>
        </div>
      @endunless
    </div>

    <div class="kyc-card">
      <span class="flabel">Where to find the business</span>
      {!! $field('address_line_1', 'Physical address', 'autocomplete="address-line1"', 'text', true, 'Building, street') !!}
      <div class="form-row">
        {!! $field('city', 'Town or city', 'autocomplete="address-level2"', 'text', true, 'e.g. Eldoret') !!}
        <div class="field @error('county') invalid @enderror">
          <label for="b-county">County<span class="req">*</span></label
          ><select class="input" id="b-county" name="county" required @disabled($disabled)>
            @foreach ($counties as $county)
              <option @selected($val('county', 'Nairobi') === $county)>{{ $county }}</option>
            @endforeach
          </select><span class="error">{{ $errors->first('county') ?: 'Choose the county.' }}</span>
        </div>
      </div>
      <div class="form-row">
        {!! $field('address_line_2', 'P.O. Box or floor', '', 'text', false) !!}
        {!! $field('postal_code', 'Postal code', 'inputmode="numeric"', 'text', false, '00100') !!}
      </div>
      <div class="form-row">
        {!! $field('phone', 'Business phone', 'autocomplete="tel" inputmode="tel"', 'tel', true, '07XX XXX XXX') !!}
        {!! $field('email', 'Business email', 'autocomplete="email"', 'email', false, 'hello@business.co.ke') !!}
      </div>
      {!! $field('website', 'Website', 'inputmode="url"', 'text', false, 'www.business.co.ke') !!}
    </div>

    <div class="kyc-nav">
      <a href="{{ $business->exists ? route('account.business.show', $business) : route('account.business.start') }}">{{ $business->exists ? 'Finish later' : 'Back' }}</a>
      @if ($disabled)
        <a class="btn btn-primary" href="{{ route('account.business.members', $business) }}">Next</a>
      @else
        <button class="btn btn-primary" type="submit">Continue</button>
      @endif
    </div>
  </form>
@endsection
