@extends('home.layouts.kyc', ['step' => 3, 'title' => 'Identity documents'])

@php
  $upIcon = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M6 10l6-6 6 6M4 20h16" /></svg>';
  $passport = old('id_type', $user->id_type) === 'passport';
  // a row shows as done when the file is already on record; a validation error replaces the hint
  $hint = function ($field, $onFile, $text) use ($errors) {
    if ($errors->has($field)) {
      return '<span class="u-hint" style="color:#9B2C3B">' . e($errors->first($field)) . '</span>';
    }
    return '<span class="u-hint" data-hint="' . e($text) . '">' . e($onFile ? 'Uploaded · on file' : $text) . '</span>';
  };
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Identity documents</h1>
    <p class="muted">JPG, PNG or PDF, up to 10 MB each.</p>
  </div>
  <form class="kyc-form kyc-docs" method="POST" action="{{ route('kyc.documents.store') }}" enctype="multipart/form-data"
    data-validate data-server novalidate>
    @csrf
    <input class="kyc-radio" type="radio" name="id_type" id="dt-id" value="national_id" @checked(!$passport) /><input
      class="kyc-radio" type="radio" name="id_type" id="dt-pp" value="passport" @checked($passport) />
    <div class="field">
      <span class="flabel">Which document will you use?<span class="req">*</span></span>
      <div class="kyc-seg">
        <label for="dt-id" class="l-id"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c1-4 4-6 8-6s7 2 8 6" /></svg
          >National ID</label
        ><label for="dt-pp" class="l-pp"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
          >Passport</label
        >
      </div>
    </div>
    <div class="uploads">
      <div class="upload only-id {{ $user->id_front_path && !$passport ? 'done' : '' }}">
        <span class="chip">{!! $upIcon !!}</span>
        <div class="u-text">
          <b>National ID — front<span class="req">*</span></b
          >{!! $hint('id_front', $user->id_front_path && !$passport, 'All four corners visible, no glare') !!}
        </div>
        <label class="btn btn-ghost u-btn" for="u-front">{{ $user->id_front_path && !$passport ? 'Replace' : 'Upload' }}</label
        ><input class="u-input" type="file" id="u-front" name="id_front" accept="image/*,application/pdf" />
      </div>
      <div class="upload only-id {{ $user->id_back_path ? 'done' : '' }}">
        <span class="chip">{!! $upIcon !!}</span>
        <div class="u-text">
          <b>National ID — back<span class="req">*</span></b
          >{!! $hint('id_back', $user->id_back_path, 'Photo of the back of your ID') !!}
        </div>
        <label class="btn btn-ghost u-btn" for="u-back">{{ $user->id_back_path ? 'Replace' : 'Upload' }}</label
        ><input class="u-input" type="file" id="u-back" name="id_back" accept="image/*,application/pdf" />
      </div>
      <div class="upload only-pp {{ $user->id_front_path && $passport ? 'done' : '' }}">
        <span class="chip">{!! $upIcon !!}</span>
        <div class="u-text">
          <b>Passport bio page<span class="req">*</span></b
          >{!! $hint('passport_bio', $user->id_front_path && $passport, 'The page with your photo and details') !!}
        </div>
        <label class="btn btn-ghost u-btn" for="u-bio">{{ $user->id_front_path && $passport ? 'Replace' : 'Upload' }}</label
        ><input class="u-input" type="file" id="u-bio" name="passport_bio" accept="image/*,application/pdf" />
      </div>
      <div class="upload {{ $user->avatar ? 'done' : '' }}">
        <span class="chip"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 8h3l2-3h6l2 3h3v11H4zM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></svg
        ></span>
        <div class="u-text">
          <b>Passport-size photo<span class="req">*</span></b
          >{!! $hint('photo', $user->avatar, 'Recent, plain light background') !!}
        </div>
        <label class="btn btn-ghost u-btn" for="u-photo">{{ $user->avatar ? 'Replace' : 'Upload' }}</label
        ><input class="u-input" type="file" id="u-photo" name="photo" accept="image/*" />
      </div>
      <div class="upload sign {{ $user->signature ? 'done' : '' }}">
        <div class="sig-head">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
          ></span>
          <div class="u-text">
            <b>Signature<span class="req">*</span></b
            >@if ($errors->has('signature'))<span class="u-hint" style="color:#9B2C3B">{{ $errors->first('signature') }}</span
            >@else<span class="u-hint">{{ $user->signature ? 'On file. Sign again or upload to replace it.' : 'Sign below, or upload a photo of your signature.' }}</span
            >@endif
          </div>
          <label class="btn btn-ghost u-btn" for="u-sign">Upload</label
          ><input class="u-input" type="file" id="u-sign" name="signature_file" accept="image/*" />
        </div>
        <div class="sig-pad">
          <canvas aria-label="Signature pad: draw your signature" data-sign></canvas
          ><span class="sig-line"></span><button type="button" class="sig-clear" data-sign-clear>Clear</button>
        </div>
        <input type="hidden" name="signature_data" data-sign-out />
      </div>
    </div>
    <div class="kyc-nav">
      <a href="{{ route('kyc.personal') }}">Back</a><button class="btn btn-primary" type="submit">Continue</button>
    </div>
  </form>
@endsection
