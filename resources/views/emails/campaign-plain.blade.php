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
Unsubscribe from offers: {{ $unsubscribeUrl }}
Your order confirmations and codes are not affected.
