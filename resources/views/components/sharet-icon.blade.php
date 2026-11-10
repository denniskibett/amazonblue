{{-- Sharet's official app icon (public/images/integrations/sharet.svg), wherever Sharet Pay is shown as a way to pay.
     Sized in em so it sits on the text line beside it; pass a class or style to size it otherwise. --}}
@props(['size' => '1.15em'])
<img src="{{ asset('images/integrations/sharet.svg') }}" alt="" width="20" height="20" {{ $attributes->merge(['style' => "width:{$size};height:{$size};border-radius:22%;flex:none;display:inline-block;vertical-align:-0.2em"]) }}>
