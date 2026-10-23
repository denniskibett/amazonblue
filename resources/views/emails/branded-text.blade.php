{{-- Plain-text part of emails.branded (same spec). Raw output on purpose: plain text is never rendered as HTML. --}}
{!! $m['heading'] !!}

@foreach ($m['paragraphs'] ?? [] as $p)
{!! $p !!}

@endforeach
@foreach ($m['steps'] ?? [] as $i => [$state, $title, $sub])
{!! $state === 'done' ? '[done]' : ($i + 1) . '.' !!} {!! $title !!}: {!! $sub !!}
@endforeach
@if (! empty($m['code']))
Code: {!! $m['code'] !!}

@endif
@if (! empty($m['panel']))
@if (! empty($m['panel']['amount']))
{!! $m['panel']['label'] ?? 'Amount' !!}: {!! $m['panel']['amount'] !!}
@endif
@foreach ($m['panel']['rows'] ?? [] as $row)
{!! $row[0] !!}: {!! $row[1] !!}
@endforeach

@endif
@foreach ($m['info'] ?? [] as [$label, $text])
{!! $label ? $label . ' ' : '' !!}{!! $text !!}
@endforeach
@foreach ($m['buttons'] ?? [] as $b)
{!! $b[0] !!}: {!! $b[1] !!}
@endforeach
@if (! empty($m['note']))

{!! $m['note'] !!}
@endif

{{ $m['signoff'][0] ?? 'Warm regards,' }}
{{ $m['signoff'][1] ?? 'The AIMZONE Ventures team' }}

AIMZONE Ventures · The Atrium, 1st Floor, Lenana Road, Nairobi · +254 701 607 959 · hello@aimzoneventures.com
We will never ask for your PIN, password or one-time codes.
