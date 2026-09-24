@props(['tone' => 'neutral', 'heading' => null])
@php
    $c    = \App\Support\EmailTheme::palette();
    $font = \App\Support\EmailTheme::FONT;
@endphp
<tr>
<td class="e-pad" style="background-color:{{ $c[$tone . '-bg'] }}; border:1px solid {{ $c[$tone . '-border'] }}; border-radius:10px; padding:22px 24px; text-align:center; font-family:{!! $font !!};">
    @if($heading)
    <div style="font-family:{!! $font !!}; font-size:22px; line-height:1.25; letter-spacing:-0.015em; font-weight:700; color:{{ $c[$tone . '-ink'] }}; margin:0 0 6px;">{{ $heading }}</div>
    @endif
    <div style="font-family:{!! $font !!}; font-size:15px; line-height:1.7; color:{{ $c['ink-mid'] }};">{{ $slot }}</div>
</td>
</tr>
<tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>
