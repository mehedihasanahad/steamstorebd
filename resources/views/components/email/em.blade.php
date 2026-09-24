{{-- Inline emphasis that carries its own colour, for the few words in a
     sentence that are the point of it. --}}
@props(['tone' => 'hi'])
@php
    $c = \App\Support\EmailTheme::palette();

    $colour = match ($tone) {
        'mid'     => $c['ink-mid'],
        'success' => $c['success-ink'],
        'warning' => $c['warning-ink'],
        'danger'  => $c['danger-ink'],
        'info'    => $c['info-ink'],
        default   => $c['ink'],
    };
@endphp
<strong style="color:{{ $colour }};">{{ $slot }}</strong>
