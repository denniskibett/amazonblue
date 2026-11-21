{{-- Guarantor sign-off on the staff loan page (owner, 21 Nov 2026). App\Services\Loans\Guarantees.
     Waiting: no money can go out until the guarantor signs from their texted link (or here, in the office), or a manager waives it. --}}
@php
  $gs = app(\App\Services\Loans\Guarantees::class);
  $g = $gs->forLoan($loan);
  $open = in_array($loan->status, \App\Services\Loans\Guarantees::OPEN, true);
  $kycGuarantor = ! $g && $open && \App\Services\Loans\Guarantees::available() ? $loan->user?->contacts()->where('relationship_specific', \App\Http\Controllers\HomeController::GUARANTOR)->latest('id')->first() : null;
  $badge = [
      'waiting' => ['Waiting to sign', 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'],
      'signed' => ['Signed', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
      'waived' => ['Waived', 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'],
  ][$g->status ?? ''] ?? null;
  $btn = 'rounded-lg px-3 py-1.5 text-xs font-medium';
@endphp
@if ($g || $kycGuarantor)
<div class="no-print mb-8 rounded-lg border border-gray-100 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800" id="guarantor">
  <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
    <h3 class="text-lg font-semibold">Guarantor @if ($badge)<span class="ml-2 inline-flex rounded-full px-2 py-0.5 align-middle text-xs font-medium {{ $badge[1] }}">{{ $badge[0] }}</span>@endif</h3>
    <div class="flex flex-wrap gap-2">
      @if ($g && $g->status === 'signed')
        <a href="{{ route('loans.guarantee.document', $loan) }}" target="_blank" class="{{ $btn }} border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">Signed guarantee</a>
      @endif
      @if ($open && (! $g || $g->status === 'waiting'))
        <form method="POST" action="{{ route('loans.guarantee.send', $loan) }}">@csrf<button class="{{ $btn }} bg-blue-500 text-white hover:bg-blue-700" type="submit">{{ $g?->link_sent_at ? 'Text the link again' : 'Text the link' }}</button></form>
        <form method="POST" action="{{ route('loans.guarantee.sign-here', $loan) }}" target="_blank">@csrf<button class="{{ $btn }} border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300" type="submit">Sign here, in the office</button></form>
      @endif
    </div>
  </div>

  @if (! $g)
    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $kycGuarantor->name }} ({{ \App\Services\Loans\Guarantees::masked($kycGuarantor->phone) }}) is this borrower's guarantor. This loan was made before guarantors signed online; text them the link if you want their signature and ID on file.</p>
  @else
    @if ($g->status === 'waiting')
      <p class="mb-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-900/20 dark:text-amber-200">
        No money can be sent on this loan until {{ $g->firstName() }} signs.
        {{ $g->link_sent_at ? 'Link texted ' . $g->link_sent_at->diffForHumans() . '.' : 'No link sent yet.' }}
      </p>
    @endif
    <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
      @foreach (array_filter([
          'Name' => $g->name,
          'Phone' => $g->phone,
          'Relationship' => $g->relationship,
          'ID or passport' => $g->id_number,
          'Date of birth' => $g->dob?->format('j M Y'),
          'Lives in' => $g->residence,
          'Does' => $g->occupation,
          'Email' => $g->email,
          'Signed' => $g->signed_at ? $g->signed_at->format('j M Y, H:i') . ($g->signed_ip ? ' · ' . $g->signed_ip : '') : null,
          'Waived' => $g->waived_at ? $g->waived_at->format('j M Y') . ' by ' . ($g->waiver->name ?? 'staff') . ': ' . $g->waived_reason : null,
      ]) as $label => $value)
        <div class="flex justify-between gap-4 border-b border-gray-100 py-1 dark:border-gray-700"><dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt><dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $value }}</dd></div>
      @endforeach
    </dl>
    @if ($g->status === 'signed')
      <div class="mt-3 flex flex-wrap gap-3">
        @foreach (['id-front' => [$g->id_front_path, 'ID front'], 'id-back' => [$g->id_back_path, 'ID back'], 'signature' => [$g->signature_path, 'Signature']] as $kind => [$path, $label])
          @if ($path)
            <a href="{{ route('loans.guarantee.file', [$loan, $kind]) }}" target="_blank" class="block w-36 overflow-hidden rounded-lg border border-gray-200 text-center text-xs text-gray-600 dark:border-gray-700 dark:text-gray-400">
              @if (str_ends_with(strtolower($path), '.pdf'))<div class="flex h-24 items-center justify-center bg-gray-50 dark:bg-gray-900">PDF</div>
              @else<img src="{{ route('loans.guarantee.file', [$loan, $kind]) }}" alt="{{ $label }}" class="h-24 w-full bg-white object-contain" loading="lazy">@endif
              <span class="block py-1">{{ $label }}</span>
            </a>
          @endif
        @endforeach
      </div>
    @endif
    @if ($g->status === 'waiting' && $open && auth()->user()->can('approve loans'))
      <details class="mt-3 text-sm">
        <summary class="cursor-pointer text-gray-600 dark:text-gray-400">Waive it (e.g. signed on paper in the office)</summary>
        <form method="POST" action="{{ route('loans.guarantee.waive', $loan) }}" class="mt-2 flex flex-wrap gap-2">@csrf
          <input name="reason" required minlength="5" maxlength="255" placeholder="Why, e.g. signed the paper guarantee on 21 Nov" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900">
          <button class="{{ $btn }} bg-gray-800 text-white hover:bg-gray-900" type="submit">Waive</button>
        </form>
      </details>
    @endif
  @endif
</div>
@endif
