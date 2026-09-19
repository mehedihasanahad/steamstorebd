@props([
    'variant' => 'primary',
    'size'    => 'md',
    'href'    => null,
    'type'    => 'button',
])

@php
    // 44px minimum touch target on every size — this store is mobile-dominant,
    // so that floor is a requirement rather than a polish item.
    $sizes = [
        'sm' => 'text-caption px-3 min-h-[36px] gap-1.5',
        'md' => 'text-body px-4 min-h-[44px] gap-2',
        'lg' => 'text-body px-6 min-h-[48px] gap-2',
    ];

    $variants = [
        'primary'   => 'bg-accent text-white hover:bg-accent-hover',
        'secondary' => 'bg-surface-2 text-ink-hi border border-surface-3 hover:border-accent/60 hover:bg-surface-3',
        'outline'   => 'border border-accent text-accent hover:bg-accent/10',
        'ghost'     => 'text-ink-mid hover:text-ink-hi hover:bg-surface-2',
        'success'   => 'bg-success text-surface-0 hover:brightness-110',
        'danger'    => 'bg-danger text-white hover:brightness-110',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-control font-semibold',
        'transition-colors duration-150 select-none',
        'disabled:opacity-40 disabled:cursor-not-allowed disabled:pointer-events-none',
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
    ]);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
