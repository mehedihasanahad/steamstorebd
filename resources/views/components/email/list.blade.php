@props(['title' => null, 'ordered' => true])
@php
    $c    = \App\Support\EmailTheme::palette();
    $font = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    $tag  = $ordered ? 'ol' : 'ul';
@endphp
<tr>
<td class="e-pad" style="background-color:{{ $c['raised'] }}; border:1px solid {{ $c['border'] }}; border-radius:10px; padding:20px 24px; font-family:{!! $font !!};">
    @if($title)
    <div style="font-family:{!! $font !!}; font-size:15px; line-height:1.6; font-weight:700; color:{{ $c['ink'] }}; margin:0 0 10px;">{{ $title }}</div>
    @endif
    <{{ $tag }} style="margin:0; padding:0 0 0 20px; color:{{ $c['ink-mid'] }}; font-family:{!! $font !!}; font-size:15px; line-height:1.9;">{{ $slot }}</{{ $tag }}>
    @isset($note)
    <div style="margin:12px 0 0; font-family:{!! $font !!}; font-size:13px; line-height:1.6; color:{{ $c['ink-low'] }};">{{ $note }}</div>
    @endisset
</td>
</tr>
<tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>
