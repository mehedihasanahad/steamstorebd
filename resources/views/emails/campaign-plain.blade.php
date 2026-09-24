@include('emails.partials.plain-header', ['eyebrow' => 'News and offers', 'heading' => $campaign->subject])

{{ $bodyText }}
@if($campaign->cta_label && $campaign->cta_url)

{{ strtoupper($campaign->cta_label) }}
{{ $campaign->cta_url }}
@endif

--
Questions? {{ route('contact') }}
{{ site_setting('site_name', 'Steam Store BD') }} — {{ route('home') }}

You are receiving this because you have ordered from {{ site_setting('site_name', 'Steam Store BD') }}.
{{-- Unescaped on purpose: this is a text/plain part, where Blade's escaping
     turns every & in the query string into &amp; and breaks the link. --}}
Unsubscribe from offers: {!! $unsubscribeUrl !!}
Your order confirmations and codes are not affected.
