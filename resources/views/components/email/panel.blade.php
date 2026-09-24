@props(['title' => null, 'align' => 'left'])
@php
    $c    = \App\Support\EmailTheme::palette();
    $font = \App\Support\EmailTheme::FONT;
@endphp
<tr>
<td class="e-pad" style="background-color:{{ $c['card'] }}; border:1px solid {{ $c['border'] }}; border-radius:10px; padding:20px 24px; text-align:{{ $align }}; font-family:{!! $font !!};">
    @if($title)
    <div style="font-family:{!! $font !!}; font-size:17px; line-height:1.45; font-weight:700; color:{{ $c['ink'] }}; margin:0 0 14px;">{{ $title }}</div>
    @endif
    {{ $slot }}
</td>
</tr>
<tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>
