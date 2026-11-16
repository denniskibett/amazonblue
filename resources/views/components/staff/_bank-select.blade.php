{{-- Which bank the money went through (owner, 16 Nov 2026): required for bank transfer, bank ↔ M-Pesa, Pesalink and
     cheque. Saves the id from resources/data/banks-kenya.txt (bank_code) and, for "Other", the name typed (bank_other).
     $show: an Alpine expression that is true when the mode goes through a bank; $model: the Alpine field for the id;
     $value / $otherValue: what is saved already (editing). --}}
@php
  $show ??= 'viaBank'; $model ??= 'bankCode'; $value ??= null; $otherValue ??= null;
  $in = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $lb = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
@endphp
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="{{ $show }}" x-cloak
     x-init="{{ $model }} = {{ $model }} || @js($value ?? '')">
  <div>
    <label class="{{ $lb }}">Our bank<span class="text-error-500"> *</span></label>
    <select name="bank_code" x-model="{{ $model }}" :disabled="!({{ $show }})" :required="{{ $show }}" class="{{ $in }}">
      <option value="">Pick the bank…</option>
      @foreach (\App\Support\Banks::options() as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
      <option value="{{ \App\Support\Banks::OTHER_CODE }}">Other (type it)</option>
    </select>
  </div>
  <div x-show="{{ $model }} === @js(\App\Support\Banks::OTHER_CODE)">
    <label class="{{ $lb }}">Bank name<span class="text-error-500"> *</span></label>
    <input name="bank_other" value="{{ $otherValue }}" maxlength="120" :disabled="!({{ $show }}) || {{ $model }} !== @js(\App\Support\Banks::OTHER_CODE)"
           :required="({{ $show }}) && {{ $model }} === @js(\App\Support\Banks::OTHER_CODE)" class="{{ $in }}" placeholder="The bank's full name">
  </div>
</div>
