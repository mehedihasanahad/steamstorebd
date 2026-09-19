@props([
    'price',
    'compareAt' => null,
    'size'      => 'text-body',
    'align'     => 'left',
])

{{--
    The only place in the storefront that formats money. Everything else asks
    this component, so a price can never be rendered two different ways.
--}}
@php
    $isDeal = $compareAt !== null && (float) $compareAt > (float) $price;
@endphp

<span {{ $attributes->class(['inline-flex flex-wrap items-baseline gap-x-2', $align === 'right' ? 'justify-end' : '']) }}>
    <span class="{{ $size }} font-bold text-success tabular-nums">{{ format_bdt($price) }}</span>
    @if($isDeal)
        <s class="text-meta text-ink-low tabular-nums">{{ format_bdt($compareAt) }}</s>
    @endif
</span>
