{{-- Read with Claude (owner, 20 Nov 2026): Services\Kyc\DocumentReader. Shown only while Integrations › Claude is on. --}}
@php $reader = \App\Services\Kyc\DocumentReader::class; $read = $doc->read_status === 'read' ? ($doc->extracted ?? []) : null; @endphp
@if ($reader::canRead($doc) && ($reader::enabled() || $doc->read_status))
    <div class="mt-1 text-xs" data-claude-read="{{ $doc->id }}">
        @if ($read)
            <details class="rounded-lg border border-gray-200 p-2 dark:border-gray-700" @if (collect($read['_checks'] ?? [])->contains(fn ($c) => $c[1] === false)) open @endif>
                <summary class="cursor-pointer font-medium text-gray-700 dark:text-gray-300">Read by Claude {{ $doc->read_at?->diffForHumans() }}</summary>
                <dl class="mt-1 space-y-0.5">
                    @foreach ($reader::FIELDS[$doc->type] as $f)
                        <div class="flex gap-2"><dt class="w-28 shrink-0 text-gray-500">{{ ucfirst(str_replace('_', ' ', $f)) }}</dt><dd class="min-w-0 break-words text-gray-800 dark:text-white/90">{{ ($read[$f] ?? '') !== '' ? $read[$f] : '—' }}</dd></div>
                    @endforeach
                </dl>
                @foreach ($read['_checks'] ?? [] as [$text, $ok])
                    <p class="{{ $ok === true ? 'text-success-600' : ($ok === false ? 'text-error-600' : 'text-gray-500') }}">{{ $ok === true ? '✓' : ($ok === false ? '✗' : '?') }} {{ $text }}</p>
                @endforeach
                @if (! ($read['legible'] ?? true) || ($read['notes'] ?? '') !== '')
                    <p class="mt-1 text-warning-600">{{ ($read['legible'] ?? true) ? '' : 'Hard to read. ' }}{{ $read['notes'] ?? '' }}</p>
                @endif
                <p class="mt-1 text-gray-400">Claude can misread: check against the document before approving.</p>
            </details>
        @elseif ($doc->read_error)
            <p class="text-error-600">{{ $doc->read_error }}</p>
        @endif
        @if ($reader::enabled() && auth()->user()->can('edit users'))
            <form method="POST" action="{{ route('business-reviews.read', [$business, $doc]) }}" x-data="{ busy: false }" @submit="busy = true">@csrf
                <button class="mt-1 font-medium text-brand-500 hover:underline disabled:opacity-50" :disabled="busy"
                        x-text="busy ? 'Reading…' : @js($read ? 'Read again with Claude' : 'Read with Claude')">{{ $read ? 'Read again with Claude' : 'Read with Claude' }}</button>
            </form>
        @endif
    </div>
@endif
