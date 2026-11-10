{{--
  Sharet cross-site analytics (first-party, collected by Scharge; no cookies, honours DNT/GPC).
  The tracker loads on production only, on indexable pages and on the one page that reports a conversion
  (a one-time session flash 'sa_conversion': signup, lead, loan_application). Private pages are not tracked.
--}}
@php
  $saUrl = rtrim((string) config('services.sharet_analytics.url'), '/');
  $saConversion = session('sa_conversion');
  $saConversion = is_string($saConversion) && preg_match('/^[a-z_]{1,40}$/', $saConversion) ? $saConversion : null;
@endphp
@if (app()->environment('production') && $saUrl !== '' && (! ($noindex ?? false) || $saConversion))
  <script defer src="{{ $saUrl }}/js/sharet-analytics.js" data-site="loans"></script>
@endif
@if ($saConversion)
  <script>(window.sa=window.sa||[]).push(['conversion','{{ $saConversion }}'])</script>
@endif
