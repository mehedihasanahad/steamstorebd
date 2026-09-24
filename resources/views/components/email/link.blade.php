@props(['url', 'tone' => 'accent'])
@php
    $c = \App\Support\EmailTheme::palette();
@endphp
<a href="{{ $url }}" style="color:{{ $tone === 'muted' ? $c['ink-low'] : $c['accent-hover'] }}; text-decoration:none;">{{ $slot }}</a>
