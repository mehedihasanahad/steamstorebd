@props(['id' => 'search'])

{{--
    The primary navigation device once the catalog spans four verticals. It is
    a real GET form to /search, so it works with JavaScript off; the type-ahead
    panel is an accelerator layered on top.
--}}
<div x-data="catalogSearch()" class="relative w-full" @click.outside="results = null" @keydown.escape="results = null">
    {{-- Abandon any in-flight suggestion on submit: its results are for a
         panel that is about to be replaced by the results page, and leaving
         the request running holds a connection open for nothing. --}}
    <form action="{{ route('search') }}" method="GET" role="search" class="relative"
          @submit="controller?.abort()">
        <label for="{{ $id }}-input" class="sr-only">Search the catalog</label>

        {{-- A real submit button, not decoration: it gives the form an
             unambiguous implicit-submission target for the Enter key, and it
             gives a touch or screen-reader user a way to search at all. --}}
        <button type="submit" aria-label="Search"
                class="absolute left-1 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-control text-ink-low transition-colors hover:text-ink-hi">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
        </button>

        <input id="{{ $id }}-input"
               name="q"
               type="search"
               autocomplete="off"
               x-model="term"
               @input.debounce.250ms="suggest()"
               @focus="if (results) results = results"
               value="{{ request('q') }}"
               placeholder="Search games, gift cards, software…"
               aria-label="Search the catalog"
               :aria-expanded="results ? 'true' : 'false'"
               class="w-full rounded-control border border-surface-3 bg-surface-2 pl-9 pr-3 min-h-[40px] text-body text-ink-hi
                      placeholder:text-ink-low transition-colors
                      focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">

        <svg x-show="loading" x-cloak class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-accent" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
    </form>

    <div x-show="results" x-cloak
         class="absolute inset-x-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-card border border-surface-3 bg-surface-1 shadow-hover"
         role="listbox" aria-label="Search suggestions">

        <template x-if="results && results.brands.length">
            <div class="border-b border-surface-3 p-2">
                <p class="px-2 py-1 text-meta font-bold uppercase tracking-widest text-ink-low">Brands</p>
                <template x-for="brand in results.brands" :key="brand.url">
                    <a :href="brand.url" class="block rounded-control px-2 py-2 text-caption font-medium text-ink-hi transition-colors hover:bg-surface-2" x-text="brand.name"></a>
                </template>
            </div>
        </template>

        <template x-if="results && results.products.length">
            <div class="p-2">
                <p class="px-2 py-1 text-meta font-bold uppercase tracking-widest text-ink-low">Products</p>
                <template x-for="product in results.products" :key="product.url">
                    <a :href="product.url" class="flex items-center gap-2 rounded-control px-2 py-2 transition-colors hover:bg-surface-2">
                        <span class="text-caption leading-none" x-show="product.region" x-text="product.region" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-caption font-medium text-ink-hi" x-text="product.name"></span>
                            <span class="block text-meta text-ink-low" x-show="product.section" x-text="product.section"></span>
                        </span>
                        <span class="flex-shrink-0 text-meta font-semibold text-success" x-show="product.from" x-text="'from ' + product.from"></span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="results && !results.products.length && !results.brands.length">
            <p class="p-4 text-caption text-ink-low">
                Nothing matched “<span class="text-ink-mid" x-text="term"></span>”. Try a brand name, or
                <a href="{{ route('contact') }}" class="text-accent-hover underline">ask us to stock it</a>.
            </p>
        </template>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('catalogSearch', () => ({
        term: new URLSearchParams(window.location.search).get('q') ?? '',
        results: null,
        loading: false,
        controller: null,

        async suggest() {
            if (this.term.trim().length < 2) { this.results = null; return; }

            // One request in flight at a time: a fast typist would otherwise
            // race several and render whichever happened to land last.
            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;

            try {
                const res = await fetch('{{ route('search.suggest') }}?q=' + encodeURIComponent(this.term), {
                    signal: this.controller.signal,
                    headers: { 'Accept': 'application/json' },
                });
                if (res.ok) this.results = await res.json();
            } catch (e) {
                if (e.name !== 'AbortError') this.results = null;
            }

            this.loading = false;
        },
    }));
});
</script>
@endpush
@endonce
