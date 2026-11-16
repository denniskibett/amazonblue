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
    <p id="pdf-tip" hidden class="no-print mb-3 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-800">In the print window, choose <strong>Save as PDF</strong> as the printer (on a phone: Share, then Print, then save).</p>
    <div class="overflow-x-auto">
      @include('loans._statement-card')
    </div>
    <p class="no-print mt-4 text-center text-xs text-gray-400">Questions about this statement? Call or WhatsApp us, or reply to the message that sent you this link.</p>
  </main>
</body>
</html>
