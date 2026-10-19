@extends('home.business.layout', ['step' => 2, 'title' => 'Directors & owners'])

@php
  $locked = ! $business->isEditable();
  $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.') . '%';
  $form = $editing ?? null;
  $isApplicant = $form && $form->user_id === $business->owner_user_id;
  $open = $errors->hasAny(['full_name', 'role', 'id_number', 'kra_pin', 'phone', 'email', 'ownership_pct']) || $form || request()->has('edit') || $members->count() < 2;
  $v = fn ($f, $d = null) => old($f, $form?->{$f} ?? $d);
  $tones = ['sage', 'apricot', 'cloud', 'sand'];
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Directors &amp; owners</h1>
    <p class="muted">Everyone on your {{ $business->isLimitedCompany() ? 'CR12' : 'registration' }} who owns 10% or more, and every director.</p>
  </div>

  <div class="kyc-card">
    <div class="biz-sec-h"><span class="flabel">People on this business</span><span class="biz-total {{ $total > 100 ? 'over' : '' }}">{{ $pct($total) }} of 100% listed</span></div>
    <div class="biz-bar" aria-hidden="true"><i style="width: {{ min(100, $total) }}%"></i></div>
    <ul class="biz-people">
      @foreach ($members as $i => $m)
        <li>
          <span class="biz-av {{ $tones[$i % 4] }}">{{ $m->initials() }}</span>
          <div class="t">
            <b>{{ $m->full_name }}{{ $m->user_id === $business->owner_user_id ? ' (you)' : '' }}</b>
            <span>{{ $m->roleLabel() }} · <span class="num">{{ $pct($m->ownership_pct) }}</span> ownership{{ $m->is_signatory ? ' · signs' : '' }}</span>
          </div>
          @if ($m->user_id && $m->verified_at)
            <span class="biz-pill ok">Verified</span>
          @else
            <span class="biz-pill">ID {{ \Illuminate\Support\Str::mask((string) $m->id_number, '•', 0, max(0, strlen((string) $m->id_number) - 3)) }}</span>
          @endif
          @unless ($locked)
            <a class="biz-link" href="{{ route('account.business.members', [$business, 'edit' => $m->id]) }}#person" aria-label="Edit {{ $m->full_name }}">Edit</a>
            @if ($m->user_id !== $business->owner_user_id)
              <form method="POST" action="{{ route('account.business.members.destroy', [$business, $m]) }}" class="biz-inline">
                @csrf @method('DELETE')
                <button type="submit" class="biz-link danger" aria-label="Remove {{ $m->full_name }}">Remove</button>
              </form>
            @endif
          @endunless
        </li>
      @endforeach
    </ul>
    @if (! $locked && ! $open)
      <a class="biz-add" href="{{ route('account.business.members', [$business, 'edit' => 0]) }}#person">+ Add person</a>
    @endif
  </div>

  @unless ($locked)
    @if ($open)
      <form class="kyc-form kyc-card" id="person" method="POST" action="{{ route('account.business.members.store', $business) }}" data-validate data-server novalidate>
        @csrf
        @if ($form)<input type="hidden" name="member_id" value="{{ $form->id }}" />@endif
        <span class="flabel">{{ $form ? ($isApplicant ? 'Your role in the business' : 'Edit ' . $form->full_name) : 'Add a person' }}</span>
        @unless ($isApplicant)
          <div class="form-row">
            <div class="field @error('full_name') invalid @enderror">
              <label for="m-name">Full name<span class="req">*</span></label
              ><input class="input" id="m-name" name="full_name" type="text" value="{{ $v('full_name') }}" placeholder="As on their ID" required
              /><span class="error">{{ $errors->first('full_name') ?: 'Enter their full name.' }}</span>
            </div>
            <div class="field @error('id_number') invalid @enderror">
              <label for="m-id">ID or passport number<span class="req">*</span></label
              ><input class="input" id="m-id" name="id_number" type="text" value="{{ $v('id_number') }}" autocomplete="off" placeholder="e.g. 12345678" required
              /><span class="error">{{ $errors->first('id_number') ?: 'Enter their ID or passport number.' }}</span>
            </div>
          </div>
        @endunless
        <div class="form-row">
          <div class="field @error('role') invalid @enderror">
            <label for="m-role">Role<span class="req">*</span></label
            ><select class="input" id="m-role" name="role" required>
              @foreach (\App\Models\BusinessMember::ROLES as $key => $label)
                <option value="{{ $key }}" @selected($v('role', 'director') === $key)>{{ $label }}</option>
              @endforeach
            </select><span class="error">{{ $errors->first('role') }}</span>
          </div>
          <div class="field @error('ownership_pct') invalid @enderror">
            <label for="m-pct">Ownership (%)<span class="req">*</span></label
            ><input class="input num" id="m-pct" name="ownership_pct" type="number" min="0" max="100" step="0.01" inputmode="decimal" value="{{ $v('ownership_pct', 0) + 0 }}" required
            /><span class="error">{{ $errors->first('ownership_pct') ?: 'Enter 0 if they own none.' }}</span>
          </div>
        </div>
        @unless ($isApplicant)
          <div class="form-row">
            <div class="field @error('phone') invalid @enderror">
              <label for="m-phone">Phone <span class="muted">(optional)</span></label
              ><input class="input" id="m-phone" name="phone" type="tel" value="{{ $v('phone') }}" placeholder="07XX XXX XXX"
              /><span class="error">{{ $errors->first('phone') }}</span>
            </div>
            <div class="field @error('kra_pin') invalid @enderror">
              <label for="m-kra">KRA PIN <span class="muted">(optional)</span></label
              ><input class="input" id="m-kra" name="kra_pin" type="text" value="{{ $v('kra_pin') }}" placeholder="A000000000X" maxlength="11"
              /><span class="error">{{ $errors->first('kra_pin') }}</span>
            </div>
          </div>
          <div class="field @error('email') invalid @enderror">
            <label for="m-email">Email <span class="muted">(optional)</span></label
            ><input class="input" id="m-email" name="email" type="email" value="{{ $v('email') }}" placeholder="name@email.com"
            /><span class="error">{{ $errors->first('email') }}</span>
          </div>
        @endunless
        <label class="check"><input type="checkbox" name="is_signatory" value="1" @checked(old('is_signatory', $form ? $form->is_signatory : false)) /><span>Can sign for the business (authorised signatory)</span></label>
        <div class="biz-form-nav">
          @if ($form)<a href="{{ route('account.business.members', $business) }}">Cancel</a>@else<span></span>@endif
          <button class="btn btn-ghost" type="submit">{{ $form ? 'Save' : 'Add person' }}</button>
        </div>
      </form>
    @endif
  @endunless

  <div class="kyc-note info">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>
    <div><b>Already verified with us?</b><span>When someone's ID number matches a verified Aimzone account, we link them and they show as verified. Inviting others to verify themselves comes later.</span></div>
  </div>

  <form method="POST" action="{{ route('account.business.members.next', $business) }}" class="kyc-nav">
    @csrf
    <a href="{{ route('account.business.details', $business) }}">Back</a>
    @if ($locked)
      <a class="btn btn-primary" href="{{ route('account.business.documents', $business) }}">Next</a>
    @else
      <button class="btn btn-primary" type="submit">Continue</button>
    @endif
  </form>
@endsection
