@props([
    'label',
    'divider'  => true,
    'tone'     => 'hi',
    'mono'     => false,
    'emphasis' => false,
])
@php
    $c     = \App\Support\EmailTheme::palette();
    $font  = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    $monoF = "'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, 'Courier New', monospace";

    $valueColour = match ($tone) {
        'accent'  => $c['accent-hover'],
        'success' => $c['success-ink'],
        'warning' => $c['warning-ink'],
        'danger'  => $c['danger-ink'],
        default   => $c['ink'],
    };

    $edge = $divider ? 'border-bottom:1px solid ' . $c['border'] . ';' : '';
    $cell = 'padding:10px 0; font-size:15px; line-height:1.5; vertical-align:top; font-family:' . $font . '; ' . $edge;
@endphp
<tr>
    <td style="{!! $cell !!} padding-right:12px; color:{{ $emphasis ? $c['ink'] : $c['ink-mid'] }};{{ $emphasis ? ' font-weight:700;' : '' }}">{{ $label }}</td>
    <td align="right" style="{!! $cell !!} text-align:right; color:{{ $valueColour }}; font-weight:{{ $emphasis ? '700' : '600' }};{!! $mono ? ' font-family:' . $monoF . '; letter-spacing:0.02em;' : '' !!}">{{ $slot }}</td>
</tr>
