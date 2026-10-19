{{-- Form field (TailAdmin). Same props as x-az.field: name, label, type (text|number|email|date|select|textarea|...),
     value, options (select), placeholder, required, hint, error (defaults to the error bag), id. Other attributes go on the control. --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'placeholder' => null, 'required' => false, 'hint' => null, 'error' => null, 'id' => null])
@php
  $id = $id ?? 'f-' . str_replace(['[', ']', '.'], '-', $name);
  $key = str_replace(['[', ']'], ['.', ''], $name);
  $error = $error ?? $errors->first($key);
  $current = old($key, $value);
  $describedBy = trim(($hint ? $id . '-hint ' : '') . ($error ? $id . '-error' : ''));
  $base = 'w-full rounded-lg border bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:outline-none focus:ring dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 '
    . ($error ? 'border-error-300 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700');
  $control = $attributes->merge(['id' => $id, 'name' => $name, 'class' => $base . ($type === 'textarea' ? ' min-h-[110px] py-2.5' : ' h-11')])
    ->merge(array_filter(['required' => $required, 'aria-invalid' => $error ? 'true' : null, 'aria-describedby' => $describedBy ?: null]));
@endphp
<div class="space-y-1.5">
  <label for="{{ $id }}" class="block text-theme-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}@if ($required)<span class="text-error-500" aria-hidden="true"> *</span>@endif</label>
  @if ($type === 'select')
    <select {{ $control }}>
      @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
      @foreach ($options as $val => $text)
        <option value="{{ $val }}" @selected((string) $current === (string) $val)>{{ $text }}</option>
      @endforeach
    </select>
  @elseif ($type === 'textarea')
    <textarea {{ $control->merge(['placeholder' => $placeholder]) }}>{{ $current }}</textarea>
  @else
    <input type="{{ $type }}" value="{{ $current }}" {{ $control->merge(['placeholder' => $placeholder]) }} />
  @endif
  @if ($hint)<p class="text-theme-xs text-gray-500 dark:text-gray-400" id="{{ $id }}-hint">{{ $hint }}</p>@endif
  @if ($error)<p class="text-theme-xs text-error-500" id="{{ $id }}-error">{{ $error }}</p>@endif
</div>
