@props([
    'arrows'   => true,
    'gap'      => 'gap-3',
    'autoplay' => 5000,
])

{{--
    A horizontal catalog row. CSS scroll-snap does the scrolling and the
    momentum; the shared `rail` component (resources/js/app.js) adds the mouse
    drag, the one-item-at-a-time autoplay and the arrow state. Works with
    JavaScript off — it is a scroll container.

    Autoplay pauses while the pointer or the keyboard is inside the rail, while
    the tab is in the background, and never starts at all for a reader who has
    asked for reduced motion.
--}}
<x-ui.rail-script />

<div x-data="rail({ autoplay: {{ (int) $autoplay }} })"
     @mouseenter="paused = true" @mouseleave="paused = false"
     @focusin="paused = true" @focusout="paused = false"
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
