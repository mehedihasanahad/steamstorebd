@props(['banners'])

@if($banners->isNotEmpty())
{{--
    The hero carousel. Slides snap to the centre so the neighbours peek in at
    the edges; <x-ui.rail-script> supplies the drag, the autoplay and the
    current-slide index the dots read.

    The slider takes the shape of whatever it is showing: 4:3 on a phone when
    the slide has mobile artwork, 16:5 otherwise. Nothing is ever cropped to
    fit, because the shape follows the picture rather than the other way
    round — the admin holds both uploads to their fixed dimensions.
--}}
<x-ui.rail-script />

<section aria-label="Promotions"
         x-data="rail({ autoplay: 6000, centred: true, wholeSlides: true })"
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

                // A phone is shown the 4:3 artwork and the box matches it. With
                // no mobile upload it keeps the desktop slide's own 16:5, since
                // squeezing that into a taller box would crop a third of it away.
                $ratio = $hasMobile ? 'aspect-[4/3] md:aspect-[16/5]' : 'aspect-[16/5]';
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
                         class="w-full {{ $ratio }} object-cover"
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
