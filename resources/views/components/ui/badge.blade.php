@props([
    'tone' => 'neutral',
])

@php
    $tones = [
        'neutral' => 'bg-surface-2 text-ink-mid border-surface-3',
        'accent'  => 'bg-accent/15 text-accent-hover border-accent/30',
        'success' => 'bg-success/15 text-success border-success/30',
        'warning' => 'bg-warning/15 text-warning border-warning/30',
        'danger'  => 'bg-danger/15 text-danger border-danger/30',
        'solid'   => 'bg-success text-surface-0 border-transparent font-bold',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-chip border px-2 py-0.5 text-meta font-semibold leading-none whitespace-nowrap',
    $tones[$tone] ?? $tones['neutral'],
]) }}>{{ $slot }}</span>
