@props(['banners'])

@if($banners->isNotEmpty())
<section aria-label="Promotions"
         x-data="heroSlider({{ $banners->count() }})"
         x-init="init()"
         @mouseenter="paused = true" @mouseleave="paused = false"
         @focusin="paused = true" @focusout="paused = false"
         class="relative">

    <div x-ref="track" @scroll.debounce.120ms="sync()"
         class="rail hero-rail gap-3 px-3 md:px-0"
         role="group" aria-roledescription="carousel">

        @foreach($banners as $i => $banner)
            @php
                $desktop = Storage::disk('public')->url($banner->image);
                $mobile  = Storage::disk('public')->url($banner->imageForMobile());
                $alt     = $banner->alt_text ?: $banner->title;
                $tag     = $banner->link_url ? 'a' : 'div';
            @endphp

            <{{ $tag }} @if($banner->link_url) href="{{ $banner->link_url }}" @endif
                class="block overflow-hidden rounded-card border border-surface-3 bg-surface-1"
                aria-roledescription="slide"
                aria-label="Slide {{ $i + 1 }} of {{ $banners->count() }}">
                <picture>
                    <source media="(max-width: 767px)" srcset="{{ $mobile }}">
                    <img src="{{ $desktop }}"
                         alt="{{ $alt }}"
                         width="1600" height="500"
                         class="w-full aspect-[4/3] md:aspect-[16/5] object-cover"
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

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('heroSlider', (count) => ({
        current: 0,
        paused: false,
        timer: null,

        init() {
            // Autoplay is a nicety, not the mechanism. A reader who has asked
            // the system to stop moving things gets a plain scroller.
            if (count < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            this.timer = setInterval(() => {
                if (!this.paused && !document.hidden) this.go((this.current + 1) % count);
            }, 6000);

            this.$el.addEventListener('alpine:destroyed', () => clearInterval(this.timer));
        },

        go(index) {
            this.current = index;
            const slide = this.$refs.track.children[index];
            if (slide) this.$refs.track.scrollTo({ left: slide.offsetLeft - this.$refs.track.offsetLeft, behavior: 'smooth' });
        },

        // Keeps the dots honest when the reader swipes instead of tapping.
        sync() {
            const track = this.$refs.track;
            const middle = track.scrollLeft + track.clientWidth / 2;
            let best = 0, bestGap = Infinity;
            Array.from(track.children).forEach((slide, i) => {
                const gap = Math.abs(slide.offsetLeft + slide.clientWidth / 2 - track.offsetLeft - middle);
                if (gap < bestGap) { bestGap = gap; best = i; }
            });
            this.current = best;
        },
    }));
});
</script>
@endpush
@endonce
@endif
