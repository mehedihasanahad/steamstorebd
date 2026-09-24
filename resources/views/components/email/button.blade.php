@props(['url'])
@php
    $c    = \App\Support\EmailTheme::palette();
    $font = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp
{{-- bgcolor on the cell as well as on the anchor: a client that drops the
     anchor background still shows the label on the accent, not on nothing. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;">
<tr>
<td align="center" bgcolor="{{ $c['accent'] }}" style="background-color:{{ $c['accent'] }}; border-radius:8px;">
    <a href="{{ $url }}" style="display:inline-block; background-color:{{ $c['accent'] }}; border-radius:8px; color:{{ $c['ink'] }}; font-family:{!! $font !!}; font-size:15px; font-weight:700; line-height:1.2; padding:14px 32px; text-decoration:none;">{{ $slot }}</a>
</td>
</tr>
</table>
