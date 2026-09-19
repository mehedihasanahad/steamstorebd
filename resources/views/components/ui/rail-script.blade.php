{{--
    The behaviour behind every rail: the hero slider and each catalog carousel.

    CSS scroll-snap still does the scrolling, the snapping and the momentum —
    this adds only the two things CSS cannot: a mouse drag, and an autoplay
    that steps one item at a time. Both rails render this, and @once means it
    is defined exactly once however many rails a page has.
--}}
@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    // How far the pointer has to travel before we call it a drag rather than a
    // click. Below this, a shaky hand on a product card still opens the product.
    const DRAG_SLOP = 6;

    /**
     * Options:
     *   autoplay  milliseconds between steps; 0 never moves on its own
     *   centred   true when the rail snaps slides to its centre (the hero)
     *             rather than to its left edge (every other rail)
     */
    Alpine.data('rail', (options = {}) => ({
        autoplay: options.autoplay ?? 0,
        centred: options.centred ?? false,

        current: 0,
        atStart: true,
        atEnd: false,
        paused: false,
        timer: null,

        init() {
            this.sync();
            this.$nextTick(() => this.sync());

            this.bindDrag();
            this.bindAutoplay();
        },

        destroy() {
            clearInterval(this.timer);
        },

        items() {
            return Array.from(this.$refs.track.children);
        },

        /** True when everything already fits, so there is nothing to scroll. */
        isStatic() {
            const track = this.$refs.track;

            return track.scrollWidth <= track.clientWidth + 4;
        },

        sync() {
            const track = this.$refs.track;

            this.atStart = track.scrollLeft <= 4;
            this.atEnd = Math.ceil(track.scrollLeft + track.clientWidth) >= track.scrollWidth - 4;

            // A centred rail is "on" the slide nearest its middle; a
            // left-aligned one is "on" the leftmost slide in view, which is
            // the one autoplay steps away from.
            const target = track.scrollLeft + (this.centred ? track.clientWidth / 2 : 0);

            let best = 0;
            let bestGap = Infinity;

            this.items().forEach((item, index) => {
                const position = item.offsetLeft - track.offsetLeft
                    + (this.centred ? item.clientWidth / 2 : 0);
                const gap = Math.abs(position - target);

                if (gap < bestGap) {
                    bestGap = gap;
                    best = index;
                }
            });

            this.current = best;
        },

        go(index) {
            const track = this.$refs.track;
            const item = this.items()[index];

            if (! item) return;

            this.current = index;
            track.scrollTo({
                left: item.offsetLeft - track.offsetLeft,
                behavior: reducedMotion.matches ? 'auto' : 'smooth',
            });
        },

        /** One item forward, back to the first once the end is in view. */
        advance() {
            this.atEnd ? this.go(0) : this.go(this.current + 1);
        },

        /** A page at a time — what the arrows do, and what a reader expects. */
        nudge(direction) {
            const track = this.$refs.track;

            track.scrollBy({
                left: direction * Math.round(track.clientWidth * 0.8),
                behavior: reducedMotion.matches ? 'auto' : 'smooth',
            });
        },

        bindAutoplay() {
            // Autoplay is a nicety, not the mechanism. A reader who has asked
            // the system to stop moving things gets a plain scroller.
            if (! this.autoplay || reducedMotion.matches) return;

            this.timer = setInterval(() => {
                if (this.paused || document.hidden || this.isStatic()) return;

                this.advance();
            }, this.autoplay);
        },

        bindDrag() {
            const track = this.$refs.track;

            let startX = 0;
            let startLeft = 0;
            let travelled = 0;
            let dragging = false;
            let captured = false;

            const start = (event) => {
                // Autoplay must not pull the rail out from under a hand that
                // is already on it — including a finger, which scrolls natively.
                this.paused = true;

                // Touch and pen keep the browser's own scrolling: it has
                // momentum and snap that no scrollLeft arithmetic can match.
                if (event.pointerType !== 'mouse' || event.button !== 0) return;

                dragging = true;
                captured = false;
                travelled = 0;
                startX = event.clientX;
                startLeft = track.scrollLeft;

                track.classList.add('rail--dragging');
            };

            const move = (event) => {
                if (! dragging) return;

                const distance = event.clientX - startX;

                travelled = Math.max(travelled, Math.abs(distance));

                // Capture only once this is unmistakably a drag. While a
                // pointer is captured the browser dispatches the click to the
                // capturing element, so capturing on pointerdown would stop
                // every card in the rail from opening when simply clicked.
                if (travelled > DRAG_SLOP && ! captured) {
                    try {
                        track.setPointerCapture(event.pointerId);
                        captured = true;
                    } catch (e) {
                        // A drag that works only inside the rail beats one that throws.
                    }
                }

                track.scrollLeft = startLeft - distance;
            };

            const end = (event) => {
                this.paused = false;

                if (! dragging) return;

                dragging = false;
                track.classList.remove('rail--dragging');

                if (captured && track.hasPointerCapture(event.pointerId)) {
                    track.releasePointerCapture(event.pointerId);
                }

                // Dropping the snap during the drag leaves the rail resting
                // between two snap points; nudging it by nothing lets the
                // browser settle it onto the nearest one.
                track.scrollBy({ left: 0, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
                this.sync();
            };

            track.addEventListener('pointerdown', start);
            track.addEventListener('pointermove', move);
            track.addEventListener('pointerup', end);
            track.addEventListener('pointercancel', end);

            // A drag that happens to finish over a product card must not also
            // open it. Captured, so it is decided before the link sees the click.
            track.addEventListener('click', (event) => {
                if (travelled > DRAG_SLOP) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                travelled = 0;
            }, true);

            // Otherwise the browser's native image drag hijacks the gesture.
            track.addEventListener('dragstart', (event) => event.preventDefault());
        },
    }));
});
</script>
@endpush
@endonce
