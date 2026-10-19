{{-- Form field: label, control, hint, error (from the error bag by name), required marker.
     <x-az.field name="amount" label="Amount (KES)" type="number" required hint="Between 1,000 and 50,000" />
     <x-az.field name="reason" label="Reason" type="select" :options="['a' => 'School fees']" placeholder="Choose one" />
     <x-az.field name="note" label="Note" type="textarea" />   Any other attributes go on the control. --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'placeholder' => null, 'required' => false, 'hint' => null, 'error' => null, 'id' => null])
@php
  $id = $id ?? 'f-' . str_replace(['[', ']', '.'], '-', $name);
  $key = str_replace(['[', ']'], ['.', ''], $name);
  $error = $error ?? $errors->first($key);
  $current = old($key, $value);
  $describedBy = trim(($hint ? $id . '-hint ' : '') . ($error ? $id . '-error' : ''));
  $control = $attributes->merge(['id' => $id, 'name' => $name, 'class' => 'input'])
    ->merge(array_filter(['required' => $required, 'aria-invalid' => $error ? 'true' : null, 'aria-describedby' => $describedBy ?: null]));
@endphp
<div @class(['field', 'az-field', 'invalid' => $error])>
  <label for="{{ $id }}">{{ $label }}@if ($required)<span class="az-req" aria-hidden="true"> *</span>@endif</label>
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
  @if ($hint)<small class="az-hint" id="{{ $id }}-hint">{{ $hint }}</small>@endif
  @if ($error)<span class="error" id="{{ $id }}-error">{{ $error }}</span>@endif
</div>
