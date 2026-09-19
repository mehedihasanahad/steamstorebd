@props([
    'product',
    'width'     => null,
    'showPrice' => true,
])

{{-- The grid card on a section, brand or search page: artwork, region flag
     top-right, name beneath. One accent per card and no more. --}}
@php $from = $product->min_price_bdt ?? null; @endphp

<a href="{{ route('product', $product->slug) }}"
   {{ $attributes->class([
       'group block overflow-hidden rounded-card border border-surface-3 bg-surface-1 shadow-card',
       'transition-colors duration-150 hover:border-accent/50',
       $width,
   ]) }}>
    <div class="relative">
        <x-catalog.artwork :image="$product->image ?: $product->mainCategory?->image"
                           :name="$product->name" ratio="aspect-[4/3]" :width="280" :height="210" />
        @if($product->regionFlag())
            <span class="absolute top-2 right-2 flex h-6 w-8 items-center justify-center rounded-chip border border-surface-3 bg-surface-0/85 text-caption leading-none"
                  title="{{ $product->regionName() }}">
                <span aria-hidden="true">{{ $product->regionFlag() }}</span>
                <span class="sr-only">{{ $product->regionName() }}</span>
            </span>
        @endif
    </div>

    <div class="px-3 py-2.5">
        <p class="text-caption font-semibold uppercase tracking-wide text-ink-hi line-clamp-2 group-hover:text-accent-hover transition-colors">{{ $product->name }}</p>
        @if($showPrice && $from)
            <p class="mt-1 text-meta text-ink-low">From <span class="font-semibold text-success">{{ format_bdt($from) }}</span></p>
        @endif
    </div>
</a>
