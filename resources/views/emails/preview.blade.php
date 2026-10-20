{{-- /system/emails: every branded email (App\Support\Emails::samples) with sample values. Super-admin only. --}}
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Email templates</title>
<style>
body{margin:0;font-family:Figtree,Arial,sans-serif;background:#EEF2FA;color:#151A2E}
header{padding:20px 24px;background:#fff;border-bottom:1px solid #DDE3EF}
h1{margin:0;font-size:20px}p{margin:6px 0 0;color:#5A6178;font-size:14px}
main{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;padding:24px}
figure{margin:0;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #DDE3EF}
figcaption{padding:10px 14px;font-size:13px;font-weight:600;display:flex;justify-content:space-between}
figcaption a{color:#1B2B6B}
iframe{width:100%;height:520px;border:0;border-top:1px solid #DDE3EF}
</style>
</head>
<body>
<header><h1>Email templates ({{ count($samples) }})</h1><p>Sample values. Designs: resources/design/emails. Code: App\Support\Emails.</p></header>
<main>
@foreach (array_keys($samples) as $i => $name)
  <figure><figcaption><span>{{ $name }} · {{ $samples[$name]['subject'] }}</span><a href="{{ route('system.emails.show', $i) }}" target="_blank">Open</a></figcaption>
    <iframe src="{{ route('system.emails.show', $i) }}" loading="lazy" title="{{ $name }}"></iframe></figure>
@endforeach
</main>
</body>
</html>
