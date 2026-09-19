@props(['menu' => []])

{{--
    The catalog panel. Every section's brands and regions are delivered with
    the page from one cached read, so moving from one section to the next
    costs no request — and with JavaScript off each trigger is still a real
    link to that section's own page.
--}}
@if(! empty($menu))
<div x-data="megaMenu(@js(array_column($menu, 'slug')))"
     @keydown.escape.window="close()"
     class="relative hidden lg:block border-t border-surface-3">

    <div class="mx-auto flex max-w-shell items-center gap-1 px-4 sm:px-6 lg:px-8">
        @foreach($menu as $section)
            <a href="{{ $section['url'] }}"
               x-ref="trigger-{{ $section['slug'] }}"
               @mouseenter="open(@js($section['slug']))"
               @focus="open(@js($section['slug']))"
               @keydown.down.prevent="open(@js($section['slug'])); focusPanel()"
               :aria-expanded="active === @js($section['slug']) ? 'true' : 'false'"
               aria-haspopup="true"
               class="relative flex min-h-[44px] items-center gap-1.5 px-3 text-caption font-semibold uppercase tracking-wide transition-colors"
               :class="active === @js($section['slug']) ? 'text-ink-hi' : 'text-ink-mid hover:text-ink-hi'">
                {{ $section['name'] }}
                <svg class="h-3 w-3 transition-transform" :class="active === @js($section['slug']) ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                <span class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-accent transition-opacity"
                      :class="active === @js($section['slug']) ? 'opacity-100' : 'opacity-0'"></span>
            </a>
        @endforeach

        <span class="ml-auto flex items-center gap-1">
            <a href="{{ route('faq') }}" class="flex min-h-[44px] items-center px-3 text-caption font-medium text-ink-mid transition-colors hover:text-ink-hi">FAQ</a>
            <a href="{{ route('contact') }}" class="flex min-h-[44px] items-center px-3 text-caption font-medium text-ink-mid transition-colors hover:text-ink-hi">Contact</a>
        </span>
    </div>

    {{-- One panel, swapped in place. Rendering a panel per trigger would
         duplicate the whole tree in the DOM for no benefit. --}}
    <div x-show="active !== null" x-cloak
         x-ref="panel" tabindex="-1"
         @mouseleave="close()"
         @keydown.tab="trapFocus($event)"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute inset-x-0 top-full z-40 border-y border-surface-3 bg-surface-1 shadow-hover">

        <div class="mx-auto max-w-shell px-4 sm:px-6 lg:px-8 py-5">
            <div class="grid grid-cols-12 gap-6">

                {{-- Column 1: the sections themselves --}}
                <nav class="col-span-3 xl:col-span-2" aria-label="Catalog sections">
                    <p class="mb-2 text-meta font-bold uppercase tracking-widest text-ink-low">Sections</p>
                    <ul class="space-y-0.5">
                        @foreach($menu as $section)
                            <li>
                                <a href="{{ $section['url'] }}"
                                   @mouseenter="active = @js($section['slug'])"
                                   @focus="active = @js($section['slug'])"
                                   class="flex items-center justify-between gap-2 rounded-control px-3 py-2 text-caption font-medium transition-colors"
                                   :class="active === @js($section['slug']) ? 'bg-accent/15 text-accent-hover' : 'text-ink-mid hover:bg-surface-2 hover:text-ink-hi'">
                                    <span class="truncate">{{ $section['name'] }}</span>
                                    <svg class="h-3 w-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                @foreach($menu as $section)
                    <div x-show="active === @js($section['slug'])" x-cloak class="col-span-9 xl:col-span-10 grid grid-cols-12 gap-6">

                        {{-- Column 2: brands in the section --}}
                        <div class="col-span-8">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-meta font-bold uppercase tracking-widest text-ink-low">Brands</p>
                                <a href="{{ $section['url'] }}" class="text-meta font-semibold text-accent-hover hover:underline">
                                    View all {{ count($section['brands']) }} &rarr;
                                </a>
                            </div>

                            <ul class="grid grid-cols-2 xl:grid-cols-3 gap-1.5">
                                @foreach(array_slice($section['brands'], 0, 12) as $brand)
                                    <li>
                                        <a href="{{ $brand['url'] }}" class="flex items-center gap-2.5 rounded-control px-2 py-2 transition-colors hover:bg-surface-2">
                                            <x-catalog.artwork :image="$brand['image']" :name="$brand['name']" ratio="aspect-square"
                                                               class="w-8 flex-shrink-0 rounded-chip" :width="32" :height="32" />
                                            <span class="min-w-0">
                                                <span class="block truncate text-caption font-medium text-ink-hi">{{ $brand['name'] }}</span>
                                                @if($brand['region_count'] > 0)
                                                    <span class="block text-meta text-ink-low">{{ $brand['region_count'] }} {{ Str::plural('region', $brand['region_count']) }}</span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Column 3: regions the section sells into --}}
                        <div class="col-span-4">
                            <p class="mb-2 text-meta font-bold uppercase tracking-widest text-ink-low">
                                Region @if(! empty($section['regions']))({{ count($section['regions']) }})@endif
                            </p>

                            @if(empty($section['regions']))
                                <p class="text-caption text-ink-low">No region filter yet.</p>
                            @else
                                <ul class="space-y-0.5">
                                    @foreach(array_slice($section['regions'], 0, 8) as $region)
                                        <li>
                                            <a href="{{ route('category', ['sectionSlug' => $section['slug'], 'region' => $region['code']]) }}"
                                               class="flex items-center gap-2.5 rounded-control px-2 py-2 transition-colors hover:bg-surface-2">
                                                <span class="text-lede leading-none" aria-hidden="true">{{ $region['flag'] }}</span>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-caption font-medium text-ink-hi">{{ $region['name'] }}</span>
                                                    <span class="block text-meta text-accent-hover">{{ $region['count'] }} {{ Str::plural('product', $region['count']) }}</span>
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('megaMenu', (slugs) => ({
        active: null,

        open(slug) { this.active = slug; },
        close() { this.active = null; },

        focusPanel() {
            this.$nextTick(() => this.$refs.panel?.querySelector('a')?.focus());
        },

        // Tabbing past the last link in the panel closes it rather than
        // dropping focus into the page behind an open overlay.
        trapFocus(event) {
            const links = this.$refs.panel?.querySelectorAll('a:not([style*="display: none"])') ?? [];
            if (links.length && event.target === links[links.length - 1] && !event.shiftKey) this.close();
        },
    }));
});
</script>
@endpush
@endif
