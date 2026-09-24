@php($heading = trim($heading))
{{ site_setting('site_name', 'Steam Store BD') }} — {{ $eyebrow }}
{{ $heading }}
{{ str_repeat('=', mb_strlen($heading)) }}
