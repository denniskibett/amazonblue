{{-- A KYC detail already on file: shown, not editable (verified borrowers only add what's missing). --}}
<div class="field saved-field">
  <span class="flabel">{{ $label }}</span>
  <div class="saved-value">
    <span>{{ filled($value) ? $value : '—' }}</span>
    <span class="saved-tag"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>Saved</span>
  </div>
</div>
