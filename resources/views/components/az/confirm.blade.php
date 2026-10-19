{{-- Button that opens a confirm <dialog>; confirming submits a form (with CSRF).
     <x-az.confirm id="cancel-app" :action="route('...')" method="DELETE" trigger="Cancel application"
        title="Cancel this application?" confirm="Yes, cancel" tone="danger">You can apply again later.</x-az.confirm>
     Extra inputs (e.g. a reason field) go in <x-slot:fields>. trigger-class: classes for the opening button. --}}
@props(['id', 'action', 'method' => 'POST', 'title', 'trigger' => 'Continue', 'confirm' => 'Confirm', 'cancel' => 'Go back', 'tone' => null, 'triggerClass' => 'btn btn-ghost'])
<button type="button" class="{{ $triggerClass }}" onclick="document.getElementById('{{ $id }}').showModal()">{{ $trigger }}</button>
<dialog {{ $attributes->class(['modal', 'az-confirm']) }} id="{{ $id }}" aria-labelledby="{{ $id }}-title" onclick="if (event.target === this) this.close()">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if (strtoupper($method) !== 'POST') @method($method) @endif
    <h2 id="{{ $id }}-title">{{ $title }}</h2>
    @if (! $slot->isEmpty())<p class="muted">{{ $slot }}</p>@endif
    {{ $fields ?? '' }}
    <div class="az-confirm__actions">
      <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">{{ $cancel }}</button>
      <button type="submit" @class(['btn', 'btn-danger' => $tone === 'danger', 'btn-primary' => $tone !== 'danger'])>{{ $confirm }}</button>
    </div>
  </form>
</dialog>
