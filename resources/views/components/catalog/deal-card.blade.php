@props([
    'card',
    'width' => 'w-48 sm:w-60',
])

{{-- A discounted SKU. The badge is derived from the two prices, never from a
     flag, so a deal cannot outlive the discount that justified it. --}}
@php
    $product = $card->category;
    $saved   = $card->discountAmount();
    $percent = $card->discountPercent();
@endphp

<a href="{{ route('product', $product->slug) }}"
   {{ $attributes->class([
       'group block overflow-hidden rounded-card border border-surface-3 bg-surface-1 shadow-card',
       'transition-colors duration-150 hover:border-accent/50',
       $width,
   ]) }}>
    <div class="relative">
        {{-- The product's own cover, not the denomination icon: a deal is
             recognised by its brand, and the value is already in the price. --}}
        <x-catalog.artwork :image="$product->image ?: $product->mainCategory?->image ?: $card->image"
                           :name="$card->name" ratio="aspect-[4/3]" :width="240" :height="180" />
        {{-- The badge leads with the percentage because that is what both the
             rail and the offers page are ordered by: taka saved would have the
             cards reading out of sequence. A saving too small to round to a
             whole percent falls back to it rather than reading "0% off". --}}
        @if($percent >= 1)
            <span class="absolute top-2 left-2 rounded-chip bg-success px-2 py-0.5 text-meta font-bold leading-none text-surface-0">
                {{ $percent }}% off
            </span>
        @elseif($saved)
            <span class="absolute top-2 left-2 rounded-chip bg-success px-2 py-0.5 text-meta font-bold leading-none text-surface-0">
                {{ number_format($saved, 0) }}tk off
            </span>
        @endif
    </div>

    <div class="px-3 py-2.5">
        <p class="text-caption font-semibold text-ink-hi line-clamp-2 group-hover:text-accent-hover transition-colors">{{ $card->name }}</p>
        <x-catalog.price class="mt-1" :price="$card->price_bdt" :compare-at="$card->compare_at_price_bdt" size="text-caption" />
    </div>
</a>
