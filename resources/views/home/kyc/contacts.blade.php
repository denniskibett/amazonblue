@extends('home.layouts.kyc', ['step' => 4, 'title' => 'Contacts'])

@section('content')
  <div class="kyc-head">
    <h1>Next of kin &amp; guarantor</h1>
    <p class="muted">We call both before approving your account, so use numbers they answer.</p>
  </div>
  <form class="kyc-form" method="POST" action="{{ route('kyc.contacts.store') }}" data-validate data-server novalidate>
    @csrf
    <div class="kyc-card">
      <h3 class="card-h">Next of kin</h3>
      <div class="form-row">
        @if (in_array('kin_name', $locked, true))
          @include('home.kyc.partials.saved', ['label' => 'Full name', 'value' => $user->kin_name])
        @else
        <div class="field @error('kin_name') invalid @enderror">
          <label for="k-kin">Full name<span class="req">*</span></label
          ><input class="input" id="k-kin" name="kin_name" type="text" autocomplete="off"
            value="{{ old('kin_name', $user->kin_name) }}" required
          /><span class="error">{{ $errors->first('kin_name') ?: 'Enter their full name.' }}</span>
        </div>
        @endif
        @if (in_array('kin_relation', $locked, true))
          @include('home.kyc.partials.saved', ['label' => 'Relationship', 'value' => $user->kin_relation])
        @else
        <div class="field @error('kin_relation') invalid @enderror">
          <label for="k-kin-rel">Relationship<span class="req">*</span></label
          ><select class="input" id="k-kin-rel" name="kin_relation" required>
            @foreach ($relationships as $slug => $label)
              <option value="{{ $label }}" @selected(old('kin_relation', $user->kin_relation ?? 'Spouse') === $label)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        @endif
      </div>
      @if (in_array('kin_phone', $locked, true))
        @include('home.kyc.partials.saved', ['label' => 'Phone', 'value' => $user->kin_phone])
      @else
      <div class="field @error('kin_phone') invalid @enderror">
        <label for="k-kin-phone">Phone<span class="req">*</span></label
        ><input class="input" id="k-kin-phone" name="kin_phone" type="tel" autocomplete="off" placeholder="07XX XXX XXX"
          value="{{ old('kin_phone', $user->kin_phone) }}" required
        /><span class="error">{{ $errors->first('kin_phone') ?: 'Enter their phone number.' }}</span>
      </div>
      @endif
    </div>
    <div class="kyc-card">
      <h3 class="card-h">Guarantor</h3>
      <p class="muted text-sm" style="margin-top:-6px">Someone who knows you well and agrees to vouch for your loans. Not your next of kin.</p>
      <div class="form-row">
        @if (in_array('guarantor_name', $locked, true))
          @include('home.kyc.partials.saved', ['label' => 'Full name', 'value' => $guarantor?->name])
        @else
        <div class="field @error('guarantor_name') invalid @enderror">
          <label for="k-g">Full name<span class="req">*</span></label
          ><input class="input" id="k-g" name="guarantor_name" type="text" autocomplete="off"
            value="{{ old('guarantor_name', $guarantor?->name) }}" required
          /><span class="error">{{ $errors->first('guarantor_name') ?: 'Enter their full name.' }}</span>
        </div>
        @endif
        @if (in_array('guarantor_type', $locked, true))
          @include('home.kyc.partials.saved', ['label' => 'How you know them', 'value' => ($relationships[$guarantorType] ?? $guarantorType)])
        @else
        <div class="field @error('guarantor_type') invalid @enderror">
          <label for="k-g-rel">How do you know them?<span class="req">*</span></label
          ><select class="input" id="k-g-rel" name="guarantor_type" required>
            @foreach ($relationships as $slug => $label)
              <option value="{{ $slug }}" @selected(old('guarantor_type', $guarantorType ?? 'friend') === $slug)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        @endif
      </div>
      @if (in_array('guarantor_phone', $locked, true))
        @include('home.kyc.partials.saved', ['label' => 'Phone', 'value' => $guarantor?->phone])
      @else
      <div class="field @error('guarantor_phone') invalid @enderror">
        <label for="k-g-phone">Phone<span class="req">*</span></label
        ><input class="input" id="k-g-phone" name="guarantor_phone" type="tel" autocomplete="off" placeholder="07XX XXX XXX"
          value="{{ old('guarantor_phone', $guarantor?->phone) }}" required
        /><span class="error">{{ $errors->first('guarantor_phone') ?: 'Enter their phone number.' }}</span>
      </div>
      @endif
    </div>
    <div class="kyc-nav">
      <a href="{{ route('kyc.income') }}">Back</a><button class="btn btn-primary" type="submit">Continue</button>
    </div>
  </form>
@endsection
