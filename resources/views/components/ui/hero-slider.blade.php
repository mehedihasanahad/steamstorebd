@props(['banners'])

@if($banners->isNotEmpty())
{{--
    The hero carousel. Slides snap to the centre so the neighbours peek in at
    the edges; <x-ui.rail-script> supplies the drag, the autoplay and the
    current-slide index the dots read.

    Every slide is 16:5 at every width — a phone gets the same picture, only
    shorter. Cropping it to a taller shape on small screens would mean one
    upload could not serve both, and object-cover would decide for itself
    which third of the artwork to discard.
--}}
<x-ui.rail-script />

<section aria-label="Promotions"
         x-data="rail({ autoplay: 6000, centred: true })"
         @mouseenter="paused = true" @mouseleave="paused = false"
         @focusin="paused = true" @focusout="paused = false"
         class="relative">

    <div x-ref="track" @scroll.debounce.120ms="sync()"
         class="rail hero-rail gap-3 px-3 md:px-0"
         role="group" aria-roledescription="carousel">

        @foreach($banners as $i => $banner)
            @php
                $desktop   = Storage::disk('public')->url($banner->image);
                $hasMobile = filled($banner->mobile_image);
                $alt       = $banner->alt_text ?: $banner->title;
                $tag       = $banner->link_url ? 'a' : 'div';
            @endphp

            <{{ $tag }} @if($banner->link_url) href="{{ $banner->link_url }}" @endif
                class="block overflow-hidden rounded-card border border-surface-3 bg-surface-1"
                aria-roledescription="slide"
                aria-label="Slide {{ $i + 1 }} of {{ $banners->count() }}">
                <picture>
                    @if($hasMobile)
                        <source media="(max-width: 767px)" srcset="{{ Storage::disk('public')->url($banner->mobile_image) }}">
                    @endif
                    <img src="{{ $desktop }}"
                         alt="{{ $alt }}"
                         width="1600" height="500"
                         class="w-full aspect-[16/5] object-cover"
                         @if($i === 0) fetchpriority="high" @else loading="lazy" @endif
                         decoding="async">
                </picture>
            </{{ $tag }}>
        @endforeach
    </div>

    @if($banners->count() > 1)
        <div class="mt-3 flex items-center justify-center gap-2" role="tablist" aria-label="Choose slide">
            @foreach($banners as $i => $banner)
                <button type="button" role="tab" @click="go({{ $i }})"
                        :aria-selected="current === {{ $i }} ? 'true' : 'false'"
                        aria-label="Go to slide {{ $i + 1 }}"
                        class="h-1.5 rounded-full transition-all duration-200"
                        :class="current === {{ $i }} ? 'w-6 bg-accent' : 'w-1.5 bg-surface-3 hover:bg-ink-low'"></button>
            @endforeach
        </div>
    @endif
</section>
@endif
