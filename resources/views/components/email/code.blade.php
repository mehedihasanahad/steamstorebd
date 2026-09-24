@props(['label' => null])
@php
    $c     = \App\Support\EmailTheme::palette();
    $font  = \App\Support\EmailTheme::FONT;
    $monoF = \App\Support\EmailTheme::MONO;
@endphp
{{-- The payload is the whole reason the message was sent, so its colours are
     inline and opaque: it stays readable in a client that keeps nothing else. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; margin:0 0 10px;">
<tr>
<td style="background-color:{{ $c['raised'] }}; border:1px solid {{ $c['border'] }}; border-radius:8px; padding:14px 16px;">
    @if($label)
    <div style="font-family:{!! $font !!}; font-size:11px; line-height:1.45; letter-spacing:0.06em; text-transform:uppercase; color:{{ $c['ink-low'] }}; margin:0 0 6px;">{{ $label }}</div>
    @endif
    <div style="font-family:{!! $monoF !!}; font-size:17px; line-height:1.5; font-weight:700; letter-spacing:0.06em; color:{{ $c['ink'] }}; word-break:break-all; white-space:pre-wrap;">{{ $slot }}</div>
</td>
</tr>
</table>
