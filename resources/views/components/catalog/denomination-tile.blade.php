@props([
    'card',
    'index',
    'image' => null,
])

{{--
    One denomination on the product page. Out-of-stock tiles stay visible,
    dimmed and badged: hiding them is what makes a product page look empty
    when a single popular value sells out.
--}}
@php
    $out = $card->stock_count < 1;
@endphp

<button type="button"
        @click="select({{ $index }})"
        @if($out) disabled aria-disabled="true" @endif
        :aria-pressed="selected === {{ $index }} ? 'true' : 'false'"
        {{ $attributes->class([
            'relative flex w-full items-center gap-3 rounded-card border p-3 text-left transition-colors duration-150',
            $out
                ? 'cursor-not-allowed border-surface-3 bg-surface-1 opacity-50'
                : 'border-surface-3 bg-surface-1 hover:border-accent/60',
        ]) }}
        @if(! $out) :class="selected === {{ $index }} ? 'border-success bg-success/5' : ''" @endif>

    <x-catalog.artwork :image="$image" :name="$card->name" ratio="aspect-square"
                       class="w-10 flex-shrink-0 rounded-control" :width="40" :height="40" />

    <span class="min-w-0 flex-1">
        <span class="flex items-center gap-1.5">
            <span class="truncate text-caption font-semibold text-ink-hi">{{ $card->name }}</span>
            @if($card->usesCodePool())
                <svg class="h-3.5 w-3.5 flex-shrink-0 text-accent-hover" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
            @endif
        </span>
        @if($out)
            <span class="mt-1 inline-flex rounded-chip bg-surface-2 px-1.5 py-0.5 text-meta font-bold uppercase tracking-wide text-ink-low">Stock out</span>
        @endif
    </span>

    <x-catalog.price :price="$card->price_bdt" :compare-at="$card->compare_at_price_bdt" size="text-caption" class="flex-shrink-0 flex-col items-end" />

    <span x-cloak x-show="selected === {{ $index }}"
          class="absolute -top-px -left-px flex h-5 w-5 items-center justify-center rounded-br-card rounded-tl-card bg-success">
        <svg class="h-3 w-3 text-surface-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
    </span>
</button>
