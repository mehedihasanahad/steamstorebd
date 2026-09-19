@props([
    'arrows' => true,
    'gap'    => 'gap-3',
])

{{--
    A horizontal catalog row. CSS scroll-snap does the scrolling and the
    momentum; Alpine only nudges scrollLeft and decides whether each arrow is
    still useful. Works with JavaScript off — it is a scroll container.
--}}
<div x-data="{
        atStart: true,
        atEnd: false,
        sync() {
            const el = $refs.track;
            this.atStart = el.scrollLeft <= 4;
            this.atEnd   = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
        },
        nudge(dir) {
            $refs.track.scrollBy({ left: dir * Math.round($refs.track.clientWidth * 0.8), behavior: 'smooth' });
        },
     }"
     x-init="sync(); $nextTick(() => sync())"
     class="relative group/rail">

    <div x-ref="track" @scroll.debounce.100ms="sync()" {{ $attributes->class(['rail', $gap]) }}>
        {{ $slot }}
    </div>

    @if($arrows)
        <button type="button" @click="nudge(-1)" x-show="!atStart" x-cloak aria-label="Scroll left"
                class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 z-10 w-9 h-9 items-center justify-center
                       rounded-full border border-surface-3 bg-surface-1/95 text-ink-hi shadow-hover
                       opacity-0 group-hover/rail:opacity-100 focus-visible:opacity-100 transition-opacity">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" @click="nudge(1)" x-show="!atEnd" x-cloak aria-label="Scroll right"
                class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 z-10 w-9 h-9 items-center justify-center
                       rounded-full border border-surface-3 bg-surface-1/95 text-ink-hi shadow-hover
                       opacity-0 group-hover/rail:opacity-100 focus-visible:opacity-100 transition-opacity">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    @endif
</div>
