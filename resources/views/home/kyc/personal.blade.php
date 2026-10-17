@extends('home.layouts.kyc', ['step' => 2, 'title' => 'Personal details'])

@php
  $isLocked = fn ($f) => in_array($f, $locked, true);
  $allSaved = count($locked) === 7;
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Personal details</h1>
    <p class="muted">{{ $allSaved ? 'Everything here is saved and verified.' : 'As they appear on your national ID or passport.' }}</p>
  </div>
  <form class="kyc-form" method="POST" action="{{ route('kyc.personal.store') }}" data-validate data-server novalidate>
    @csrf
    <div class="kyc-card">
      <div class="form-row">
        @if ($isLocked('first_name'))
          @include('home.kyc.partials.saved', ['label' => 'First name', 'value' => $firstName])
        @else
          <div class="field @error('first_name') invalid @enderror">
            <label for="k-first">First name<span class="req">*</span></label
            ><input class="input" id="k-first" name="first_name" type="text" autocomplete="given-name"
              placeholder="Enter your first name" value="{{ old('first_name', $firstName) }}" required
            /><span class="error">{{ $errors->first('first_name') ?: 'Enter your first name.' }}</span>
          </div>
        @endif
        @if ($isLocked('last_name'))
          @include('home.kyc.partials.saved', ['label' => 'Last name', 'value' => $lastName])
        @else
          <div class="field @error('last_name') invalid @enderror">
            <label for="k-last">Last name<span class="req">*</span></label
            ><input class="input" id="k-last" name="last_name" type="text" autocomplete="family-name"
              placeholder="Enter your last name" value="{{ old('last_name', $lastName) }}" required
            /><span class="error">{{ $errors->first('last_name') ?: 'Enter your last name.' }}</span>
          </div>
        @endif
      </div>
      <div class="form-row">
        @if ($isLocked('id_number'))
          @include('home.kyc.partials.saved', ['label' => 'ID or passport number', 'value' => $user->id_number])
        @else
          <div class="field @error('id_number') invalid @enderror">
            <label for="k-id">ID or passport number<span class="req">*</span></label
            ><input class="input" id="k-id" name="id_number" type="text" autocomplete="off" placeholder="e.g. 12345678"
              value="{{ old('id_number', $user->id_number) }}" required
            /><span class="error">{{ $errors->first('id_number') ?: 'Enter your ID or passport number.' }}</span>
          </div>
        @endif
        @if ($isLocked('dob'))
          @include('home.kyc.partials.saved', ['label' => 'Date of birth', 'value' => $user->dob?->format('j M Y')])
        @else
          <div class="field @error('dob') invalid @enderror">
            <label for="k-dob">Date of birth<span class="req">*</span></label
            ><input class="input" id="k-dob" name="dob" type="date" autocomplete="bday"
              max="{{ now()->subYears(18)->toDateString() }}" value="{{ old('dob', $user->dob?->toDateString()) }}" required
            /><span class="error">{{ $errors->first('dob') ?: 'Enter your date of birth.' }}</span>
          </div>
        @endif
      </div>
      <div class="form-row">
        @if ($isLocked('kra_pin'))
          @include('home.kyc.partials.saved', ['label' => 'KRA PIN', 'value' => $user->kra_pin])
        @else
          <div class="field @error('kra_pin') invalid @enderror">
            <label for="k-kra">KRA PIN<span class="req">*</span></label
            ><input class="input" id="k-kra" name="kra_pin" type="text" autocomplete="off" placeholder="A000000000X"
              value="{{ old('kra_pin', $user->kra_pin) }}" required
            /><span class="error">{{ $errors->first('kra_pin') ?: 'Enter your KRA PIN.' }}</span>
          </div>
        @endif
        @if ($isLocked('county'))
          @include('home.kyc.partials.saved', ['label' => 'County', 'value' => $address?->state])
        @else
          <div class="field @error('county') invalid @enderror">
            <label for="k-county">County<span class="req">*</span></label
            ><select class="input" id="k-county" name="county" required>
              @foreach ($counties as $county)
                <option @selected(old('county', $address?->state ?? 'Nairobi') === $county)>{{ $county }}</option>
              @endforeach
            </select>
            <span class="error">{{ $errors->first('county') ?: 'Choose your county.' }}</span>
          </div>
        @endif
      </div>
      @if ($isLocked('address'))
        @include('home.kyc.partials.saved', ['label' => 'Physical address', 'value' => $address?->address_line_1])
      @else
        <div class="field @error('address') invalid @enderror">
          <label for="k-addr">Physical address<span class="req">*</span></label
          ><input class="input" id="k-addr" name="address" type="text" autocomplete="street-address"
            placeholder="Street, building, area" value="{{ old('address', $address?->address_line_1) }}" required
          /><span class="error">{{ $errors->first('address') ?: 'Enter your physical address.' }}</span>
        </div>
      @endif
    </div>
    <div class="kyc-nav">
      <a href="{{ route('account.home') }}">Finish later</a
      ><button class="btn btn-primary" type="submit">Continue</button>
    </div>
  </form>
@endsection
