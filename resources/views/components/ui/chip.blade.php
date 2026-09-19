@props([
    'href'   => null,
    'active' => false,
])

@php
    $classes = implode(' ', [
        'inline-flex items-center gap-1.5 rounded-chip border px-3 min-h-[36px] text-caption font-medium',
        'transition-colors duration-150 whitespace-nowrap',
        $active
            ? 'bg-accent/15 border-accent/45 text-accent-hover'
            : 'bg-surface-1 border-surface-3 text-ink-mid hover:text-ink-hi hover:border-surface-3',
    ]);
@endphp

@if($href)
    <a href="{{ $href }}" @if($active) aria-current="page" @endif {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
