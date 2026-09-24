--
@if(($support ?? true))
Questions? {{ route('contact') }}
@endif
{{ site_setting('site_name', 'Steam Store BD') }} — {{ route('home') }}
@isset($disclaimer)

{{ $disclaimer }}
@endisset
