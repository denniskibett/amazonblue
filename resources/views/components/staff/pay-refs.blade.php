{{-- How the money moved and its references (owner, 23 Oct 2026). Bank ↔ M-Pesa asks for both references; pasting the
     bank or M-Pesa message fills the mode, references, amount and date of the form it sits in (POST staff.mpesa.read-sms).
     direction: in (money we receive: M-Pesa code first) | out (money we send: bank reference first). --}}
@props(['direction' => 'in', 'mode' => 'mpesa', 'dateField' => 'repayment_date'])
@php
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $label = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
  $mode = \App\Support\PaymentModes::normalise($mode) ?? 'mpesa';
@endphp
<div class="space-y-4" x-data="{
    mode: @js($mode), sms: '', msg: '', ok: false, bank: '', mpesa: '', ref: '',
    get both() { return ['bank_to_mpesa', 'mpesa_to_bank'].includes(this.mode); },
    set(name, value) { const el = this.$root.closest('form')?.querySelector(`[name='${name}']`); if (el && value !== undefined && value !== null) { el.value = value; el.dispatchEvent(new Event('input', { bubbles: true })); } },
    async read() {
      if (!this.sms.trim()) { this.msg = ''; return; }
      try {
        const res = await fetch(@js(route('staff.mpesa.read-sms')), { method: 'POST', credentials: 'same-origin',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
          body: JSON.stringify({ sms: this.sms }) });
        const body = await res.json();
        if (!body.ok) { this.ok = false; this.msg = body.message; return; }
        const m = body.sms;
        this.mode = m.mode || this.mode; this.bank = m.bank_ref || ''; this.mpesa = m.mpesa_ref || '';
        this.ref = @js($direction) === 'out' ? (m.bank_ref || m.mpesa_ref || m.code) : (m.mpesa_ref || m.bank_ref || m.code);
        this.set('amount', m.amount); if (m.date) this.set(@js($dateField), m.date);
        this.ok = !(body.seen || []).length;
        this.msg = (body.seen || []).length ? 'Already recorded: ' + body.seen.join(', ') + '. Check you are not entering it twice.'
          : 'Read: ' + body.mode_label + ' · KES ' + Number(m.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 }) + (m.mpesa_ref ? ' · M-Pesa ' + m.mpesa_ref : '') + (m.bank_ref ? ' · bank ' + m.bank_ref : '') + (m.party ? ' · ' + m.party : '');
      } catch (e) { this.ok = false; this.msg = 'Could not read it. Type the details in.'; }
    } }">
  <div>
    <label class="{{ $label }}" for="pr-sms-{{ $direction }}">Paste the M-Pesa or bank message <span class="font-normal text-gray-400">(optional)</span></label>
    <textarea id="pr-sms-{{ $direction }}" rows="2" x-model="sms" @input.debounce.500ms="read()" class="{{ $input }} h-auto py-2.5" placeholder="Fills the mode, references, amount and date"></textarea>
    <p class="mt-1 text-theme-xs" :class="ok ? 'text-success-600' : 'text-amber-700 dark:text-amber-400'" x-show="msg" x-text="msg"></p>
  </div>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
      <label class="{{ $label }}" for="pr-mode-{{ $direction }}">How the money {{ $direction === 'out' ? 'was sent' : 'came' }}</label>
      <select id="pr-mode-{{ $direction }}" name="mode" x-model="mode" class="{{ $input }}">
        @foreach (\App\Support\PaymentModes::MODES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
      </select>
    </div>
    <div x-show="!both">
      <label class="{{ $label }}" for="pr-ref-{{ $direction }}">Reference<span class="text-error-500"> *</span></label>
      <input id="pr-ref-{{ $direction }}" name="transaction" x-model="ref" :disabled="both" :required="!both" maxlength="255" class="{{ $input }}" placeholder="M-Pesa code, bank reference, receipt or slip number">
    </div>
  </div>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="both" x-cloak>
    <div>
      <label class="{{ $label }}" for="pr-bank-{{ $direction }}">Bank Transaction Ref ID<span class="text-error-500"> *</span></label>
      <input id="pr-bank-{{ $direction }}" name="bank_ref" x-model="bank" :disabled="!both" :required="both" maxlength="100" class="{{ $input }}" placeholder="e.g. 5690NMQS2509">
    </div>
    <div>
      <label class="{{ $label }}" for="pr-mpesa-{{ $direction }}">M-Pesa code<span class="text-error-500"> *</span></label>
      <input id="pr-mpesa-{{ $direction }}" name="mpesa_ref" x-model="mpesa" :disabled="!both" :required="both" maxlength="40" class="{{ $input }} uppercase" placeholder="e.g. UJH6OA6DRB">
    </div>
    <p class="text-theme-xs text-gray-500 dark:text-gray-400 sm:col-span-2">Both are kept, so the bank statement and the M-Pesa statement each match, and the same money can't be entered twice.</p>
  </div>
</div>
