@extends('finance.layout', ['title' => config('finance_journey.steps.' . $step) . ' · ' . $fa->reference, 'current' => $step])

@php
  $p = $fa->productConfig();
  $b = $fa->business ?? [];
  $d = $fa->details ?? [];
  $val = fn ($f, $src = []) => old($f, $src[$f] ?? null);
  $field = function (string $name, string $label, array $src = [], string $type = 'text', bool $req = true, string $attrs = '', string $hint = '') use ($val, $errors) {
    $id = 'f-' . str_replace('_', '-', $name);
    return '<div class="field ' . ($errors->has($name) ? 'invalid' : '') . '"><label for="' . $id . '">' . e($label) . ($req ? '<span class="req">*</span>' : ' <span class="muted">(optional)</span>') . '</label>'
      . '<input class="input" id="' . $id . '" name="' . $name . '" type="' . $type . '" value="' . e($val($name, $src)) . '" ' . ($req ? 'required ' : '') . ($errors->has($name) ? 'aria-invalid="true" ' : '') . $attrs . ' />'
      . ($hint ? '<span class="fj-small">' . e($hint) . '</span>' : '') . '<span class="error">' . e($errors->first($name)) . '</span></div>';
  };
  $select = function (string $name, string $label, array $options, array $src = [], bool $req = true) use ($val, $errors) {
    $id = 'f-' . str_replace('_', '-', $name);
    $html = '<div class="field ' . ($errors->has($name) ? 'invalid' : '') . '"><label for="' . $id . '">' . e($label) . ($req ? '<span class="req">*</span>' : '') . '</label><select class="input" id="' . $id . '" name="' . $name . '" ' . ($req ? 'required' : '') . '><option value="">Choose one</option>';
    foreach ($options as $k => $l) {
      $html .= '<option value="' . e($k) . '" ' . ((string) $val($name, $src) === (string) $k ? 'selected' : '') . '>' . e($l) . '</option>';
    }
    return $html . '</select><span class="error">' . e($errors->first($name)) . '</span></div>';
  };
  $money = fn ($v, $cur = null) => ($cur ?? $fa->currency()) . ' ' . number_format((float) $v, 0);
  $sectors = collect(config('funding_crm.segments'))->map(fn ($s) => $s['label'])->all() + ['other' => 'Other'];
@endphp

@section('content')
  <div class="kyc-head">
    <h1>{{ ['business' => 'About the business', 'need' => $p['need_label'], 'terms' => 'Amount and term', 'documents' => 'Documents', 'review' => 'Check, consent and submit'][$step] }}</h1>
    <p class="muted">{{ [
      'business' => 'As registered with the Business Registration Service and KRA.',
      'need' => $fa->product === 'asset' ? 'What you want to buy and who is selling it. Attach the supplier quote in Documents.' : 'The order or invoice this finance is for.',
      'terms' => 'How much you would like and for how long. The figures are an estimate to help you plan, not an offer.',
      'documents' => 'PDF, JPG or PNG, up to ' . round(config('finance_journey.uploads.max_kb') / 1024) . ' MB each. Only our credit team can open them.',
      'review' => 'Read your answers, then give the consents we need to assess the application.',
    ][$step] }}</p>
    @if ($fa->status === 'more_info' && $fa->staff_note)
      <div class="kyc-note warn" role="status"><div><b>What we asked for</b><span>{{ $fa->staff_note }}</span></div></div>
    @endif
  </div>

  @if ($step === 'documents')
    @php $types = collect(config('finance_journey.documents'))->filter(fn ($t, $k) => in_array($fa->product, $t[3], true) || $k === 'other'); @endphp
    <div class="uploads">
      @foreach ($types as $type => [$label, $hint, $max, $for])
        @php $files = $fa->documents->where('type', $type); $req = in_array($fa->product, $for, true); @endphp
        <div class="upload {{ $files->isNotEmpty() ? 'done' : '' }}" style="flex-wrap:wrap">
          <div class="u-text" style="flex:1 1 220px">
            <b>{{ $label }}@if ($req)<span class="req">*</span>@else <span class="muted biz-opt">optional</span>@endif</b>
            <span class="u-hint">@if ($errors->has('docs.' . $type)){{ $errors->first('docs.' . $type) }}@else{{ $hint }} · up to {{ $max }} file{{ $max > 1 ? 's' : '' }}@endif</span>
            @foreach ($files as $f)
              <span class="fj-file">{{ \Illuminate\Support\Str::limit($f->original_name, 36) }} · {{ round($f->size / 1024) }} KB
                <form method="POST" action="{{ route('finance.documents.remove', [$token, $f->id]) }}">@csrf<button type="submit">Remove</button></form></span>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
    <form class="kyc-form" method="POST" action="{{ route('finance.save', [$token, 'documents']) }}" enctype="multipart/form-data" style="margin-top:16px">
      @csrf
      <div class="kyc-card">
        <span class="flabel">Add files</span>
        @foreach ($types as $type => [$label, $hint, $max])
          @php $left = $max - $fa->documents->where('type', $type)->count(); @endphp
          @if ($left > 0)
            <div class="field"><label for="d-{{ $type }}">{{ $label }}</label>
              <input class="input" type="file" id="d-{{ $type }}" name="docs[{{ $type }}][]" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" @if ($left > 1) multiple @endif /></div>
          @endif
        @endforeach
        @if ($missing)
          <p class="fj-small">Still needed: {{ collect($missing)->map(fn ($t) => config('finance_journey.documents.' . $t . '.0'))->implode(', ') }}.</p>
        @endif
      </div>
      <div class="kyc-nav">
        <button class="btn btn-ghost" type="submit" name="exit" value="1">Save and exit</button>
        <button class="btn btn-primary" type="submit">{{ $missing ? 'Upload' : 'Continue' }}</button>
      </div>
    </form>
  @elseif ($step === 'review')
    <div class="kyc-card">
      <span class="flabel">Business</span>
      <ul class="fj-rows">
        @foreach (['legal_name' => 'Name', 'registration_number' => 'Registration no.', 'kra_pin' => 'KRA PIN', 'county' => 'County', 'years_trading' => 'Years trading', 'directors' => 'Directors'] as $k => $l)
          <li><span>{{ $l }}</span><span>{{ $b[$k] ?? '—' }}</span></li>
        @endforeach
        <li><span>Turnover, last 12 months</span><span>{{ isset($b['annual_turnover']) ? $money($b['annual_turnover'], 'KES') : '—' }}</span></li>
      </ul>
      <p class="fj-small"><a href="{{ route('finance.step', [$token, 'business']) }}">Change</a></p>
    </div>
    <div class="kyc-card">
      <span class="flabel">{{ $p['need_label'] }}</span>
      <ul class="fj-rows">
        @if ($fa->product === 'asset')
          <li><span>Asset</span><span>{{ $p['categories'][$d['category'] ?? ''] ?? '—' }} · {{ $d['description'] ?? '' }} ({{ $d['condition'] ?? '' }})</span></li>
          <li><span>Supplier</span><span>{{ $d['supplier'] ?? '—' }}</span></li>
          <li><span>Price · your deposit</span><span>{{ isset($d['price']) ? $money($d['price']) : '—' }} · {{ isset($d['deposit']) ? $money($d['deposit']) : '—' }}</span></li>
        @else
          <li><span>Type</span><span>{{ $p['kinds'][$d['kind'] ?? ''] ?? '—' }}</span></li>
          <li><span>Supplier</span><span>{{ $d['supplier'] ?? '—' }}, {{ $d['supplier_country'] ?? '' }}</span></li>
          <li><span>Goods</span><span>{{ $d['goods'] ?? '—' }}</span></li>
          @if (! empty($d['buyer']))<li><span>Buyer</span><span>{{ $d['buyer'] }}</span></li>@endif
          <li><span>Order value</span><span>{{ isset($d['order_value']) ? $money($d['order_value']) : '—' }}</span></li>
        @endif
        <li><span>You ask for</span><span>{{ $fa->amount ? $money($fa->amount) . ' over ' . $fa->term_months . ' months' : '—' }}</span></li>
        @if ($fa->estimate)
          <li><span>Estimated {{ $fa->estimate['basis'] === 'monthly' ? 'monthly instalment' : 'repayment at the end' }}</span><span>{{ $money($fa->estimate['instalment']) }}</span></li>
        @endif
      </ul>
      <p class="fj-small"><a href="{{ route('finance.step', [$token, 'need']) }}">Change</a> · <a href="{{ route('finance.step', [$token, 'terms']) }}">Change the amount</a></p>
    </div>
    <div class="kyc-card">
      <span class="flabel">Documents</span>
      <p class="fj-small" style="margin:0">{{ $fa->documents->count() }} file{{ $fa->documents->count() === 1 ? '' : 's' }} uploaded{{ $missing ? '. Still needed: ' . collect($missing)->map(fn ($t) => config('finance_journey.documents.' . $t . '.0'))->implode(', ') : '' }}. <a href="{{ route('finance.step', [$token, 'documents']) }}">Change</a></p>
    </div>
    <form class="kyc-form" method="POST" action="{{ route('finance.save', [$token, 'review']) }}">
      @csrf
      <div class="kyc-card" style="display:grid;gap:10px">
        <label class="check"><input type="checkbox" name="confirm_true" value="1" required /><span>The information and documents are true and complete, and I may apply on behalf of {{ $fa->businessName() }}.</span></label>
        <label class="check"><input type="checkbox" name="consent_crb" value="1" required /><span>Aimzone Ventures may check the business and its directors with credit reference bureaus and verify the documents with the issuers.</span></label>
        <label class="check"><input type="checkbox" name="consent_data" value="1" required /><span>Aimzone Ventures may process this information to assess the application, as set out in its <a href="{{ url('/privacy') }}" target="_blank" rel="noopener">privacy notice</a> (Data Protection Act, 2019).</span></label>
        <p class="fj-small" style="margin:0">Submitting is a request, not a commitment by you or by us. Any finance depends on our assessment, final terms and a signed agreement. Repayments are collected through Sharet Pay.</p>
      </div>
      <div class="kyc-nav">
        <a href="{{ route('finance.show', $token) }}">Finish later</a>
        <button class="btn btn-primary" type="submit">Submit application</button>
      </div>
    </form>
  @else
    <form class="kyc-form" method="POST" action="{{ route('finance.save', [$token, $step]) }}" novalidate>
      @csrf
      <div class="kyc-card">
        @if ($step === 'business')
          {!! $field('legal_name', 'Registered name', $b, 'text', true, 'autocomplete="organization"') !!}
          <div class="form-row">
            {!! $field('registration_number', 'Registration number', $b, 'text', true, 'autocomplete="off"', 'e.g. PVT-AB12CD3') !!}
            {!! $field('kra_pin', 'Business KRA PIN', $b, 'text', true, 'maxlength="11" autocomplete="off"', 'e.g. P051234567X') !!}
          </div>
          <div class="form-row">
            {!! $select('sector', 'What the business does', $sectors, $b) !!}
            {!! $select('county', 'County', array_combine(\App\Http\Controllers\HomeController::COUNTIES, \App\Http\Controllers\HomeController::COUNTIES), $b) !!}
          </div>
          <div class="form-row">
            {!! $field('years_trading', 'Years trading', $b, 'number', true, 'min="0" max="150" inputmode="numeric"') !!}
            {!! $field('directors', 'Number of directors', $b, 'number', true, 'min="1" max="50" inputmode="numeric"') !!}
          </div>
          {!! $field('annual_turnover', 'Turnover in the last 12 months (KES)', $b, 'number', true, 'min="0" step="1" inputmode="numeric"', 'From your bank and M-Pesa statements') !!}
        @elseif ($step === 'need' && $fa->product === 'asset')
          {!! $select('category', 'What kind of asset', $p['categories'], $d) !!}
          {!! $field('description', 'Make, model and year', $d, 'text', true, '', 'e.g. Isuzu FRR 2022, 10-tonne truck') !!}
          <fieldset class="chips @error('condition') invalid @enderror"><legend>Condition<span class="req">*</span></legend>
            @foreach (['new' => 'New', 'used' => 'Used'] as $k => $l)
              <input type="radio" name="condition" id="c-{{ $k }}" value="{{ $k }}" @checked($val('condition', $d) === $k) required /><label for="c-{{ $k }}">{{ $l }}</label>
            @endforeach
          </fieldset>
          <div class="form-row">
            {!! $field('supplier', 'Supplier or dealer', $d) !!}
            {!! $field('supplier_phone', 'Supplier phone', $d, 'tel', false) !!}
          </div>
          <div class="form-row">
            {!! $field('price', 'Price on the quote (KES)', $d, 'number', true, 'min="0" step="1" inputmode="numeric"') !!}
            {!! $field('deposit', 'Deposit you can pay (KES)', $d, 'number', true, 'min="0" step="1" inputmode="numeric"', 'At least ' . $p['min_deposit_pct'] . '% of the price is usual') !!}
          </div>
        @elseif ($step === 'need')
          <fieldset class="chips @error('kind') invalid @enderror"><legend>What the finance is for<span class="req">*</span></legend>
            @foreach ($p['kinds'] as $k => $l)
              <input type="radio" name="kind" id="k-{{ $k }}" value="{{ $k }}" @checked($val('kind', $d) === $k) required /><label for="k-{{ $k }}">{{ $l }}</label>
            @endforeach
          </fieldset>
          @error('kind')<span class="biz-err">{{ $message }}</span>@enderror
          <div class="form-row">
            {!! $field('supplier', 'Supplier', $d) !!}
            {!! $field('supplier_country', 'Supplier\'s country', $d, 'text', true, '', 'e.g. Kenya, China') !!}
          </div>
          {!! $field('goods', 'Goods or services', $d) !!}
          {!! $field('buyer', 'Buyer who will pay you', $d, 'text', false, '', 'Needed for LPO and invoice finance, e.g. Kiambu County Government') !!}
          <div class="form-row">
            {!! $select('currency', 'Currency', ['KES' => 'KES', 'USD' => 'USD'], $d + ['currency' => $d['currency'] ?? 'KES']) !!}
            {!! $field('order_value', 'Order or invoice value', $d, 'number', true, 'min="1" step="1" inputmode="numeric"') !!}
          </div>
          {!! $field('buyer_pays_on', 'When the buyer should pay', $d, 'date', false, 'min="' . now()->addDay()->toDateString() . '"') !!}
        @elseif ($step === 'terms')
          @php $max = $fa->product === 'asset' ? max(0, (float) ($d['price'] ?? 0) - max((float) ($d['price'] ?? 0) * $p['min_deposit_pct'] / 100, (float) ($d['deposit'] ?? 0))) : (float) ($d['order_value'] ?? 0); @endphp
          {!! $field('amount', 'Amount you would like (' . $fa->currency() . ')', ['amount' => $fa->amount ? (int) $fa->amount : ($max ? (int) $max : null)], 'number', true, 'min="0" step="1" inputmode="numeric" data-est-amount', $max ? 'Up to ' . $money($max) . ($fa->product === 'asset' ? ' (price less deposit)' : ' (the order value)') : '') !!}
          {!! $select('term_months', $fa->product === 'asset' ? 'Repay over' : 'Repay after', collect($p['terms'])->mapWithKeys(fn ($m) => [$m => $m . ' month' . ($m > 1 ? 's' : '')])->all(), ['term_months' => $fa->term_months ?? $p['terms'][2] ?? $p['terms'][0]]) !!}
          {!! $field('purpose', 'Anything we should know', $fa->estimate ?? [], 'text', false, 'maxlength="500"') !!}
          <div class="fj-est" aria-live="polite" data-est data-rate="{{ $p['monthly_rate'] }}" data-basis="{{ $fa->product === 'asset' ? 'monthly' : 'end' }}" data-cur="{{ $fa->currency() }}">
            <span class="fj-small">Estimated {{ $fa->product === 'asset' ? 'monthly instalment' : 'repayment at the end of the term' }}</span>
            <b class="num" data-est-out>—</b>
            <span class="fj-small" data-est-note></span>
            <span class="fj-small">An estimate at {{ $p['monthly_rate'] }}% a month to help you plan. It is not an offer; your price depends on our assessment.</span>
          </div>
        @endif
      </div>
      <div class="kyc-nav">
        <button class="btn btn-ghost" type="submit" name="exit" value="1">Save and exit</button>
        <button class="btn btn-primary" type="submit">Save and continue</button>
      </div>
    </form>
  @endif
@endsection

@push('scripts')
<script>
  (() => {
    const box = document.querySelector('[data-est]'); if (!box) return;
    const amt = document.querySelector('[data-est-amount]'), term = document.getElementById('f-term-months');
    const r = parseFloat(box.dataset.rate) / 100, cur = box.dataset.cur, fmt = (v) => cur + ' ' + Math.round(v).toLocaleString('en-KE');
    const calc = () => {
      const a = parseFloat(amt.value) || 0, n = parseInt(term.value) || 0;
      if (!a || !n) { box.querySelector('[data-est-out]').textContent = '—'; box.querySelector('[data-est-note]').textContent = ''; return; }
      if (box.dataset.basis === 'monthly') {
        const i = r > 0 ? a * r / (1 - Math.pow(1 + r, -n)) : a / n;
        box.querySelector('[data-est-out]').textContent = fmt(i) + ' a month';
        box.querySelector('[data-est-note]').textContent = n + ' instalments, about ' + fmt(i * n) + ' in all';
      } else {
        const t = a + a * r * n;
        box.querySelector('[data-est-out]').textContent = fmt(t);
        box.querySelector('[data-est-note]').textContent = 'one payment after ' + n + ' month' + (n > 1 ? 's' : '') + '; repaying early costs less';
      }
    };
    amt.addEventListener('input', calc); term.addEventListener('change', calc); calc();
  })();
</script>
@endpush
