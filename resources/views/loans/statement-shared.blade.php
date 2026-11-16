{{-- The borrower's statement link (owner, 16 Nov 2026): no sign-in, a signed link that expires; view, print or
     save as PDF (the browser's "Save as PDF" keeps it exactly as it looks). --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="no-referrer">
  <title>Loan statement #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }} · Aimzone Ventures Capital</title>
  <link rel="icon" href="{{ asset('assets/images/logo/favicon.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css'])
  @include('loans._statement-print')
</head>
<body class="bg-gray-50 font-outfit text-gray-800">
  <main class="mx-auto max-w-4xl px-3 py-5 sm:px-6">
    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-gray-600">Your loan statement, as at {{ now()->format('j M Y') }}.</p>
      <div class="flex gap-2">
        <button type="button" onclick="window.print()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Print</button>
        <button type="button" onclick="document.getElementById('pdf-tip').hidden = false; window.print()" class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600">Download PDF</button>
      </div>
    </div>
    {{-- clear the loan (owner, 16 Nov 2026): what to pay and where, each with a copy button; the account is the one
         set under System (App\Support\PartnerFunding), the same I&M account partners pay into --}}
    @php
      $pay = \App\Support\PartnerFunding::all();
      $ref = 'Loan ' . $loan->id;
      $copyRow = fn ($label, $value) => $value ? '<div class="flex items-center justify-between gap-3 border-t border-gray-100 py-2 first:border-0"><span class="text-gray-500">' . e($label)
          . '</span><span class="flex items-center gap-2"><span class="font-semibold tabular-nums text-gray-800">' . e($value) . '</span><button type="button" data-copy="' . e($value)
          . '" class="az-copy rounded-md border border-gray-300 px-2 py-0.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Copy</button></span></div>' : '';
    @endphp
    @if ($balance > 0 && (filled($pay['bank_account_number'] ?? null) || filled($pay['mpesa_paybill'] ?? null)))
    <section class="no-print mb-4 rounded-2xl border border-brand-100 bg-white p-4 sm:p-5">
      <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-base font-semibold text-gray-800">Clear your loan</h2>
        <p class="text-sm text-gray-600">Pay <strong class="text-red-600">KES {{ number_format($balance, 2) }}</strong>
          <button type="button" data-copy="{{ number_format($balance, 2, '.', '') }}" class="az-copy ml-1 rounded-md border border-gray-300 px-2 py-0.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Copy</button></p>
      </div>
      <div class="grid gap-4 text-sm sm:grid-cols-2">
        @if (filled($pay['bank_account_number'] ?? null))
          <div class="rounded-xl bg-gray-50 px-3 py-2">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Bank transfer · {{ $pay['bank_name'] }}</p>
            {!! $copyRow('Account name', $pay['bank_account_name'] ?? null) !!}
            {!! $copyRow('Account number', $pay['bank_account_number']) !!}
            {!! $copyRow('Branch', $pay['bank_branch'] ?? null) !!}
            {!! $copyRow('SWIFT', $pay['bank_swift'] ?? null) !!}
            {!! $copyRow('Reference', $ref) !!}
          </div>
        @endif
        @if (filled($pay['mpesa_paybill'] ?? null))
          <div class="rounded-xl bg-gray-50 px-3 py-2">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">M-Pesa · Lipa na M-Pesa, Paybill</p>
            {!! $copyRow('Paybill', $pay['mpesa_paybill']) !!}
            {!! $copyRow('Account no.', $pay['mpesa_account'] ?? $pay['bank_account_number'] ?? null) !!}
            <p class="pt-2 text-xs text-gray-500">The money goes to our {{ $pay['bank_name'] }} account. Then send us the M-Pesa message on WhatsApp with "{{ $ref }}" so we match it to your loan.</p>
          </div>
        @endif
      </div>
    </section>
    <script>
      document.addEventListener('click', (e) => {
        const b = e.target.closest('.az-copy'); if (!b) return;
        navigator.clipboard.writeText(b.dataset.copy).then(() => { const t = b.textContent; b.textContent = 'Copied'; setTimeout(() => b.textContent = t, 1500); });
      });
    </script>
    @endif

    <p id="pdf-tip" hidden class="no-print mb-3 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-800">In the print window, choose <strong>Save as PDF</strong> as the printer (on a phone: Share, then Print, then save).</p>
    <div class="overflow-x-auto">
      @include('loans._statement-card')
    </div>
    <p class="no-print mt-4 text-center text-xs text-gray-400">Questions about this statement? Call or WhatsApp us, or reply to the message that sent you this link.</p>
  </main>
</body>
</html>
