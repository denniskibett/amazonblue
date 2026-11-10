{{-- Connect / Settings for an integration whose card is its one place (Sharet Pay, 10 Nov 2026): the connection
     (API key, webhook signing secret, API base URL), its options, Test connection and Remove. Secrets are never sent
     to the browser; a blank secret keeps the saved one. Needs $c (a card from IntegrationsController@index), $input, $btn. --}}
@php
  $connection = $c['fields']->where('connection', true);
  $options = $c['fields']->where('connection', false);
  $field = function ($f) use ($c) {
      $id = 'f-' . $c['slug'] . '-' . $f['env'];
      $name = 'fields[' . $f['env'] . ']';
      return compact('id', 'name');
  };
@endphp
<div x-cloak x-show="settings === @js($c['slug'])" x-data="{ saving: false, testing: false }" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" @keydown.escape.window="settings = null">
  <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px] dark:bg-gray-900/70" @click="settings = null"></div>
  <div class="relative my-auto w-full max-w-[600px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
    <div class="mb-1 flex items-center gap-3">
      <img src="{{ $c['logo'] }}" alt="" class="size-10 rounded-xl">
      <h4 class="text-title-xs font-semibold text-gray-800 dark:text-white/90">{{ $c['connected'] ? $c['name'] . ' settings' : 'Connect ' . $c['name'] }}</h4>
    </div>
    <p class="mb-6 mt-3 text-sm text-gray-500 dark:text-gray-400">This is the one place the Sharet key is set. Secrets are stored encrypted and never shown again; leave one blank to keep what is saved. A new key switches Sharet Pay off until it is tested and switched back on.</p>

    <form id="connect-{{ $c['slug'] }}" method="POST" action="{{ route('staff.integrations.update', $c['slug']) }}" @submit="saving = true" class="space-y-4">
      @csrf
      @foreach ($connection as $f)
        @php ['id' => $id, 'name' => $name] = $field($f); @endphp
        <div>
          <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $f['label'] }}@if ($f['env'] === 'SHARET_API_KEY' && ! $c['connected'])<span class="text-error-500"> *</span>@endif</label>
          @if ($f['secret'])
            <input id="{{ $id }}" type="password" name="{{ $name }}" autocomplete="new-password" spellcheck="false" @required($f['env'] === 'SHARET_API_KEY' && ! $c['connected'])
              placeholder="{{ $f['set'] ? 'Saved · leave blank to keep' : ($f['placeholder'] ?? 'Not set') }}" class="{{ $input }} font-mono">
          @else
            <input id="{{ $id }}" type="url" name="{{ $name }}" value="{{ ($f['source'] ?? '') === 'saved' ? $f['value'] : '' }}" placeholder="{{ $f['placeholder'] }}" spellcheck="false" class="{{ $input }}">
          @endif
          @if ($f['help'])<p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">{{ $f['help'] }}</p>@endif
        </div>
      @endforeach

      @if ($options->isNotEmpty())
        <h5 class="!mt-8 border-t border-gray-100 pt-5 text-sm font-semibold text-gray-800 dark:border-gray-800 dark:text-white/90">Options</h5>
        @foreach ($options as $f)
          @php ['id' => $id, 'name' => $name] = $field($f); @endphp
          <div>
            <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $f['label'] }}</label>
            @if ($f['type'] === 'bool')
              <select id="{{ $id }}" name="{{ $name }}" class="{{ $input }}">
                <option value="0" @selected($f['value'] !== '1')>Off</option>
                <option value="1" @selected($f['value'] === '1')>On</option>
              </select>
            @elseif ($f['type'] === 'select')
              <select id="{{ $id }}" name="{{ $name }}" class="{{ $input }}">
                @foreach ($f['options'] as $o)<option value="{{ $o }}" @selected($f['value'] === $o)>{{ ['paybill' => 'Own M-Pesa paybill', 'sharet' => 'Sharet Pay'][$o] ?? $o }}</option>@endforeach
              </select>
            @else
              <input id="{{ $id }}" type="number" inputmode="numeric" min="15" max="43200" name="{{ $name }}" value="{{ $f['value'] }}" class="{{ $input }}">
            @endif
            @if ($f['help'])<p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">{{ $f['help'] }}</p>@endif
          </div>
        @endforeach
      @endif
    </form>

    @if ($c['test'])
      <div class="mt-6 rounded-xl px-4 py-3 text-sm {{ $c['test']['ok'] ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' }}">
        {{ $c['test']['message'] }} <span class="opacity-70">({{ $c['test']['when'] }})</span>
      </div>
    @endif

    {{-- Test connection: checks the saved key and registers our webhook once; disabled while it runs --}}
    @if ($c['connected'])
      <form id="test-{{ $c['slug'] }}" method="POST" action="{{ route('sharet.test') }}" @submit="testing = true" class="hidden">@csrf</form>
    @endif

    <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
      <button type="button" @click="settings = null" class="{{ $btn }} sm:mr-auto">Cancel</button>
      @if ($c['connected'])
        <button type="submit" form="test-{{ $c['slug'] }}" :disabled="testing || saving" class="{{ $btn }} disabled:cursor-wait disabled:opacity-50">
          <span x-text="testing ? 'Testing…' : 'Test connection'">Test connection</span>
        </button>
      @endif
      <button type="submit" form="connect-{{ $c['slug'] }}" :disabled="testing || saving"
        class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 disabled:cursor-wait disabled:opacity-50">
        <span x-text="saving ? 'Saving…' : @js($c['connected'] ? 'Save' : 'Connect')">{{ $c['connected'] ? 'Save' : 'Connect' }}</span>
      </button>
    </div>

    @if ($c['removable'] && $c['connected'])
      <form method="POST" action="{{ route('staff.integrations.destroy', $c['slug']) }}" class="mt-6 border-t border-gray-100 pt-5 dark:border-gray-800">
        @csrf @method('DELETE')
        <button type="submit" class="text-sm font-medium text-error-600 hover:underline dark:text-error-400" data-confirm-danger
          data-confirm="Remove {{ $c['name'] }}? The saved key, webhook secret and options are deleted and Sharet Pay switches off. Borrowers keep paying by the own paybill; open pay links and queued Sharet payouts wait until it is connected again.">Remove {{ $c['name'] }}</button>
      </form>
    @endif
  </div>
</div>
