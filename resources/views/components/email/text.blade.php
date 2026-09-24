@props([
    'size'   => 'body',
    'tone'   => 'mid',
    'align'  => 'left',
    'top'    => '0',
    'bottom' => '0',
    'lines'  => 'normal',
])
@php
    $c    = \App\Support\EmailTheme::palette();
    $font = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";

    [$px, $lh] = match ($size) {
        'caption' => ['13px', '1.6'],
        'meta'    => ['11px', '1.6'],
        default   => ['15px', '1.7'],
    };

    $colour = match ($tone) {
        'hi'    => $c['ink'],
        'low'   => $c['ink-low'],
        default => $c['ink-mid'],
    };
@endphp
<p style="margin:{{ $top }} 0 {{ $bottom }}; font-family:{!! $font !!}; font-size:{{ $px }}; line-height:{{ $lh }}; color:{{ $colour }}; text-align:{{ $align }}; white-space:{{ $lines }};">{{ $slot }}</p>
