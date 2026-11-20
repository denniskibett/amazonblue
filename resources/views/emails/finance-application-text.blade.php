Hello {{ \Illuminate\Support\Str::before($fa->contact_name, ' ') }},

@switch($kind)
@case('link')
You started a {{ strtolower($fa->productConfig('label')) }} application for {{ $fa->businessName() }} ({{ $fa->reference }}). Every step is saved as you go. Use this private link to carry on whenever it suits you:
@break
@case('submitted')
Thank you. We have {{ $fa->businessName() }}'s {{ strtolower($fa->productConfig('label')) }} application ({{ $fa->reference }}). Our team will review it and may call you with questions. You can follow it here:
@break
@case('more_info')
We are reviewing {{ $fa->reference }} and need something more before we can carry on:

{{ $fa->staff_note }}

Open your application here to add it:
@break
@case('approved')
{{ $fa->reference }} is approved in principle. This is not yet an offer: the final amount, price and security depend on the checks still to do and on a signed agreement. Our team will call you about next steps. Details:
@break
@case('declined')
We have reviewed {{ $fa->reference }} and cannot offer finance for it at this time.

{{ $fa->staff_note }}

You can see the application here:
@break
@endswitch

{{ $link }}

This link is private to you; please don't forward it. It stops working {{ (int) config('finance_journey.link_days', 30) }} days after your last visit; you can ask for a new one at {{ route('finance.resume') }}.

Aimzone Ventures
+254 701 607 959
