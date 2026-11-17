{{-- The fields of one feature on the tracker (Features and suggestions): add, edit, or add from a suggestion. --}}
@php
  $v = fn ($key, $def = null) => $old($sheet, $key, $f->{$key} ?? $def);
  $fromSuggestion = $s ? \Illuminate\Support\Str::limit($s->body, 150, '') : null;
@endphp
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
  <div class="sm:col-span-2"><label class="{{ $label }}" for="{{ $sheet }}-name">Feature</label>
    <input id="{{ $sheet }}-name" name="name" required maxlength="160" value="{{ $v('name', $fromSuggestion) }}" class="{{ $input_ }}"></div>
  <div><label class="{{ $label }}" for="{{ $sheet }}-area">Area</label>
    <input id="{{ $sheet }}-area" name="area" required maxlength="60" list="{{ $sheet }}-areas" value="{{ $v('area') }}" class="{{ $input_ }}">
    <datalist id="{{ $sheet }}-areas">@foreach ($areas as $a)<option value="{{ $a }}">@endforeach</datalist></div>
  <div><label class="{{ $label }}" for="{{ $sheet }}-status">Status</label>
    <select id="{{ $sheet }}-status" name="status" class="{{ $input_ }}">@foreach ($S as $k => $l)<option value="{{ $k }}" @selected($v('status', $s ? 'missing' : 'improve') === $k)>{{ $l }}</option>@endforeach</select></div>
  <div><label class="{{ $label }}" for="{{ $sheet }}-priority">Priority</label>
    <select id="{{ $sheet }}-priority" name="priority" class="{{ $input_ }}">@foreach ($P as $k => $l)<option value="{{ $k }}" @selected($v('priority', 'later') === $k)>{{ $l }}</option>@endforeach</select></div>
  <div class="sm:col-span-2"><label class="{{ $label }}" for="{{ $sheet }}-gap">Shortcoming <span class="font-normal text-gray-400">(what is wrong or missing)</span></label>
    <textarea id="{{ $sheet }}-gap" name="shortcoming" rows="4" maxlength="2000" class="{{ $area_ }}">{{ $v('shortcoming', $s?->body) }}</textarea></div>
</div>
@if (old('_sheet') === $sheet && $errors->any())<x-staff.alert type="err">{{ $errors->first() }}</x-staff.alert>@endif
