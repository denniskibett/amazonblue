{{-- Branded email (designs: resources/design/emails/*.html). Built from App\Support\Emails specs; every value is escaped.
     $m keys: subject, preheader, badge [text, tone], heading, paragraphs[], steps[[state done|now|todo, title, sub]],
     code, panel {label, amount, rows[[label, value, strong?]]}, info[[label, text]], note, buttons[[text, url, primary?]]. --}}
@php
  $tones = ['info' => ['#C9D6F0', '#1B2B6B'], 'warn' => ['#F8E1CF', '#9A4A1C'], 'review' => ['#F4EDE2', '#7A5B2E'], 'ok' => ['#DCEBE2', '#2F6B4F'], 'danger' => ['#F6DCE0', '#9B2C3B']];
  $sans = "'Figtree','Helvetica Neue',Arial,sans-serif";
  $head = "'Sora','Helvetica Neue',Arial,sans-serif";
  $num = "'Outfit','Helvetica Neue',Arial,sans-serif";
  [$badgeBg, $badgeFg] = $tones[$m['badge'][1] ?? 'info'] ?? $tones['info'];
@endphp
<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<title>{{ $m['subject'] }}</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;700&family=Figtree:wght@400;600;700&family=Outfit:wght@500;600&display=swap" rel="stylesheet">
<style>
body{margin:0;padding:0;background:#EEF2FA}
a{color:#1B2B6B}
@media (max-width:620px){.wrap{width:100%!important}.card{padding:28px 22px!important;border-radius:20px!important}.btn{display:block!important;text-align:center!important;margin:0 0 10px!important}h1{font-size:23px!important}}
</style>
</head>
<body style="margin:0;padding:0;background:#EEF2FA">
@if (! empty($m['preheader']))<div style="display:none;max-height:0;overflow:hidden;opacity:0">{{ $m['preheader'] }}&#8202;&#8202;&#8202;&#8202;</div>@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF2FA">
<tr><td align="center" style="padding:32px 12px">
<table role="presentation" class="wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px">
<tr><td style="padding:0 6px 18px">
<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
<td style="background:#1B2B6B;color:#FFFFFF;width:36px;height:36px;border-radius:10px;text-align:center;vertical-align:middle;font-family:{{ $head }};font-weight:700;font-size:17px;letter-spacing:-1px">av</td>
<td style="padding-left:12px;font-family:{{ $head }};font-weight:700;font-size:18px;letter-spacing:-0.8px;color:#1B2B6B">aimzone <span style="font-weight:300">ventures</span></td>
</tr></table>
</td></tr>
<tr><td class="card" style="background:#FFFFFF;border-radius:24px;padding:36px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
@if (! empty($m['badge']))
<tr><td><span style="display:inline-block;background:{{ $badgeBg }};color:{{ $badgeFg }};font-family:{{ $sans }};font-weight:600;font-size:12px;padding:6px 12px;border-radius:999px">{{ $m['badge'][0] }}</span></td></tr>
@endif
<tr><td style="padding-top:16px"><h1 style="margin:0;font-family:{{ $head }};font-weight:700;font-size:26px;line-height:1.2;letter-spacing:-0.8px;color:#151A2E">{{ $m['heading'] }}</h1></td></tr>
@foreach ($m['paragraphs'] ?? [] as $p)
<tr><td style="padding-top:12px;font-family:{{ $sans }};font-size:16px;line-height:1.6;color:#5A6178">{{ $p }}</td></tr>
@endforeach
@if (! empty($m['steps']))
<tr><td style="padding-top:22px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
@foreach ($m['steps'] as $i => [$state, $title, $sub])
@php [$dotBg, $dotFg] = ['done' => ['#DCEBE2', '#2F6B4F'], 'now' => ['#2F6B4F', '#FFFFFF']][$state] ?? ['#EEF2FA', '#5A6178']; @endphp
<tr><td valign="top" style="width:40px;padding:8px 0"><div style="width:28px;height:28px;border-radius:14px;background:{{ $dotBg }};color:{{ $dotFg }};text-align:center;line-height:28px;font-family:{{ $num }};font-weight:600;font-size:13px">{!! $state === 'done' ? '&#10003;' : $i + 1 !!}</div></td><td style="padding:8px 0;font-family:{{ $sans }}"><div style="font-weight:700;font-size:15px;color:#151A2E">{{ $title }}</div><div style="font-size:14px;color:#5A6178">{{ $sub }}</div></td></tr>
@endforeach
</table></td></tr>
@endif
@if (! empty($m['code']))
<tr><td style="padding-top:22px"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
@foreach (mb_str_split((string) $m['code']) as $digit)<td style="width:44px;height:56px;background:#EEF2FA;border-radius:12px;text-align:center;font-family:{{ $num }};font-weight:600;font-size:26px;color:#151A2E">{{ $digit }}</td><td style="width:8px"></td>@endforeach
</tr></table></td></tr>
@endif
@if (! empty($m['panel']))
<tr><td style="padding-top:22px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EEF2FA;border-radius:18px;border-collapse:separate"><tr><td style="padding:20px 24px">
@if (! empty($m['panel']['amount']))
<div style="font-family:{{ $sans }};font-size:13px;color:#5A6178">{{ $m['panel']['label'] ?? 'Amount' }}</div><div style="font-family:{{ $num }};font-weight:600;font-size:34px;letter-spacing:-1px;color:#1B2B6B;padding:4px 0 8px;word-break:break-word">{{ $m['panel']['amount'] }}</div>
@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse">
@foreach ($m['panel']['rows'] ?? [] as $row)
<tr><td style="padding:10px 0;font-family:{{ $sans }};font-size:14px;color:#5A6178;border-top:1px solid #DDE3EF">{{ $row[0] }}</td><td align="right" style="padding:10px 0;font-family:{{ $num }};font-size:15px;font-weight:{{ ! empty($row[2]) ? 700 : 500 }};color:{{ ! empty($row[2]) ? '#1B2B6B' : '#151A2E' }};border-top:1px solid #DDE3EF;word-break:break-word">{{ $row[1] }}</td></tr>
@endforeach
</table></td></tr></table></td></tr>
@endif
@if (! empty($m['info']))
<tr><td style="padding-top:22px"><div style="background:#C9D6F0;color:#1B2B6B;border-radius:14px;padding:14px 16px;font-family:{{ $sans }};font-size:14px;line-height:1.55">
@foreach ($m['info'] as [$label, $text])@if ($label)<strong>{{ $label }}</strong> @endif{{ $text }}@if (! $loop->last)<br>@endif @endforeach
</div></td></tr>
@endif
@if (! empty($m['buttons']))
<tr><td style="padding-top:26px">@foreach ($m['buttons'] as $b)@if (! empty($b[2]))<a href="{{ $b[1] }}" class="btn" style="display:inline-block;background:#1B2B6B;color:#FFFFFF;font-family:{{ $sans }};font-weight:600;font-size:15px;line-height:1;text-decoration:none;padding:16px 28px;border-radius:999px">{{ $b[0] }}</a>@else<a href="{{ $b[1] }}" class="btn" style="display:inline-block;background:#FFFFFF;color:#1B2B6B;border:1.5px solid #C9D6F0;font-family:{{ $sans }};font-weight:600;font-size:15px;line-height:1;text-decoration:none;padding:15px 26px;border-radius:999px">{{ $b[0] }}</a>@endif @if (! $loop->last)&nbsp;&nbsp;@endif @endforeach</td></tr>
@endif
@if (! empty($m['note']))
<tr><td style="padding-top:18px;font-family:{{ $sans }};font-size:13px;line-height:1.6;color:#5A6178">{{ $m['note'] }}</td></tr>
@endif
<tr><td style="padding-top:26px;font-family:{{ $sans }};font-size:15px;line-height:1.6;color:#151A2E">{{ $m['signoff'][0] ?? 'Warm regards,' }}<br><strong>{{ $m['signoff'][1] ?? 'The AIMZONE Ventures team' }}</strong></td></tr>
</table>
</td></tr>
<tr><td style="padding:22px 6px 0;font-family:{{ $sans }};font-size:12px;line-height:1.7;color:#5A6178">
This is a service message about your account.<br>AIMZONE Ventures · The Atrium, 1st Floor, Lenana Road, Nairobi<br>
<a href="tel:+254701607959" style="color:#5A6178">+254 701 607 959</a> · <a href="mailto:hello@aimzoneventures.com" style="color:#5A6178">hello@aimzoneventures.com</a> · <a href="https://aimzoneventures.com" style="color:#5A6178">aimzoneventures.com</a><br>
We will never ask for your PIN, password or one-time codes.
</td></tr>
</table>
</td></tr></table>
</body>
</html>
