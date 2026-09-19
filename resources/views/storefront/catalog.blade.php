@extends('layouts.storefront')

@php
    $_payWith   = $paymentMethodNames ? ' with ' . \Illuminate\Support\Arr::join($paymentMethodNames, ', ', ' or ') : '';
    $_owner     = $brand ?? $section;
    $_seoTitle  = $_owner->seo_title ?: 'Buy ' . $heading . ' in Bangladesh';
    $_seoDesc   = $_owner->seo_description
        ?: 'Buy ' . $heading . ' in Bangladesh' . $_payWith . '. Instant code delivery to email. 100% genuine codes at the best BDT price.';
    $_canonical = $brand ? route('brand', $brand->slug) : route('category', $section->slug);
@endphp

@section('title', $_seoTitle . ' — Steam Store BD')
@section('meta_description', $_seoDesc)
@section('og_type', 'website')
@section('og_image_alt', 'Buy ' . $heading . ' in Bangladesh — Steam Store BD')
@if($_owner->image)
@section('og_image', Storage::disk('public')->url($_owner->image))
@endif

@push('schema')
@php
    $_crumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')]];

    if ($brand?->catalogSection) {
        $_crumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $brand->catalogSection->name, 'item' => route('category', $brand->catalogSection->slug)];
    }
    $_crumbs[] = ['@type' => 'ListItem', 'position' => count($_crumbs) + 1, 'name' => $heading, 'item' => $_canonical];

    $_pageSchema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            ['@type' => 'BreadcrumbList', 'itemListElement' => $_crumbs],
            [
                '@type'           => 'ItemList',
                'name'            => $heading . ' Bangladesh',
                'description'     => $_seoDesc,
                'url'             => $_canonical,
                'itemListElement' => $products->values()->map(fn ($product, $i) => [
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'name'     => $product->name,
                    'url'      => route('product', $product->slug),
                ])->toArray(),
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($_pageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<div class="mx-auto max-w-shell px-4 py-5 sm:px-6 lg:px-8">

    <x-catalog.breadcrumbs class="mb-4" :items="array_values(array_filter([
        ['label' => 'Home', 'url' => route('home')],
        $brand?->catalogSection ? ['label' => $brand->catalogSection->name, 'url' => route('category', $brand->catalogSection->slug)] : null,
        ['label' => $heading, 'url' => null],
    ]))" />

    <div class="grid grid-cols-12 gap-5">

        {{-- ══ Left rail ══ --}}
        <aside class="col-span-12 lg:col-span-3" aria-label="Catalog navigation">

            {{-- Mobile: the sections as a chip row for a one-tap switch, and
                 the whole tree behind a disclosure. The chips alone left the
                 brands inside a section unreachable on a phone. --}}
            <div class="lg:hidden -mx-4 mb-3 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-hide">
                @foreach($railSections as $railSection)
                    <x-ui.chip :href="route('category', $railSection->slug)"
                               :active="$section?->id === $railSection->id">{{ $railSection->name }}</x-ui.chip>
                @endforeach
            </div>

            <div class="lg:hidden mb-4" x-data="{ open: false }" @keydown.escape="open = false">
                <button type="button" @click="open = !open"
                        :aria-expanded="open ? 'true' : 'false'"
                        aria-controls="catalog-tree-mobile"
                        class="flex w-full items-center justify-between gap-3 rounded-card border border-surface-3 bg-surface-1 px-4 py-3 text-left transition-colors hover:border-accent/50">
                    <span class="min-w-0">
                        <span class="block text-body font-bold text-ink-hi">Categories</span>
                        <span class="block truncate text-meta text-ink-low">{{ $brand?->name ?? $section?->name ?? 'Browse every section' }}</span>
                    </span>
                    <svg class="h-4 w-4 flex-shrink-0 text-ink-low transition-transform" :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <nav id="catalog-tree-mobile" x-show="open" x-cloak aria-label="Categories"
                     class="scroll-slim mt-2 max-h-[60vh] overflow-y-auto rounded-card border border-surface-3 bg-surface-1 p-2">
                    <x-catalog.section-tree :sections="$railSections" :section="$section" :brand="$brand" />
                </nav>
            </div>

            <div class="hidden lg:block space-y-4">
                <nav class="rounded-card border border-surface-3 bg-surface-1" aria-label="Categories">
                    <h2 class="border-b border-surface-3 px-4 py-3 text-body font-bold text-ink-hi">Categories</h2>

                    <div class="scroll-slim max-h-[460px] overflow-y-auto p-2">
                        <x-catalog.section-tree :sections="$railSections" :section="$section" :brand="$brand" />
                    </div>
                </nav>

                @if($featured->isNotEmpty())
                    <section class="rounded-card border border-surface-3 bg-surface-1" aria-labelledby="featured-rail-heading">
                        <h2 id="featured-rail-heading" class="border-b border-surface-3 px-4 py-3 text-body font-bold text-ink-hi">Featured products</h2>
                        <ul class="p-2">
                            @foreach($featured as $featuredProduct)
                                <li>
                                    <a href="{{ route('product', $featuredProduct->slug) }}" class="flex items-center gap-2.5 rounded-control px-2 py-2 transition-colors hover:bg-surface-2">
                                        <x-catalog.artwork :image="$featuredProduct->image ?: $featuredProduct->mainCategory?->image"
                                                           :name="$featuredProduct->name" ratio="aspect-square"
                                                           class="w-9 flex-shrink-0 rounded-chip" :width="36" :height="36" />
                                        <span class="min-w-0">
                                            <span class="block truncate text-caption font-medium text-ink-hi">{{ $featuredProduct->name }}</span>
                                            @if($featuredProduct->regionName())
                                                <span class="block text-meta text-ink-low">{{ $featuredProduct->regionFlag() }} {{ $featuredProduct->regionName() }}</span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </aside>

        {{-- ══ Listing ══ --}}
        <div class="col-span-12 lg:col-span-9">
            <div class="rounded-card border border-surface-3 bg-surface-1 p-4 md:p-5">

                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="text-title md:text-display font-extrabold text-ink-hi">{{ $heading }}</h1>
                        @if($tagline)
                            <p class="mt-1 max-w-2xl text-caption text-ink-mid">{{ $tagline }}</p>
                        @endif
                    </div>

                    <div class="flex flex-shrink-0 items-center gap-2">
                        {{-- Region filter. Real links, so the filtered view is a
                             URL a shopper can share and a crawler can follow. --}}
                        @if(! empty($regionFilters))
                            <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                                <button @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                                        class="flex min-h-[40px] items-center gap-2 rounded-control border border-surface-3 bg-surface-2 px-3 text-caption font-medium text-ink-hi transition-colors hover:border-accent/50">
                                    <span>{{ $activeRegion ? \App\Support\Region::label($activeRegion) : '🌐 All' }}</span>
                                    <svg class="h-3 w-3 text-ink-low" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="open" x-cloak class="absolute right-0 z-30 mt-2 w-52 rounded-card border border-surface-3 bg-surface-1 p-1 shadow-hover">
                                    <a href="{{ request()->fullUrlWithQuery(['region' => null, 'page' => null]) }}"
                                       class="block rounded-control px-3 py-2 text-caption transition-colors hover:bg-surface-2 {{ $activeRegion ? 'text-ink-mid' : 'font-semibold text-accent-hover' }}">🌐 All regions</a>
                                    @foreach($regionFilters as $regionOption)
                                        <a href="{{ request()->fullUrlWithQuery(['region' => $regionOption['code'], 'page' => null]) }}"
                                           class="flex items-center justify-between gap-2 rounded-control px-3 py-2 text-caption transition-colors hover:bg-surface-2 {{ $activeRegion === $regionOption['code'] ? 'font-semibold text-accent-hover' : 'text-ink-mid' }}">
                                            <span class="truncate">{{ $regionOption['flag'] }} {{ $regionOption['name'] }}</span>
                                            <span class="text-meta text-ink-low">{{ $regionOption['count'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                            <button @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                                    class="flex min-h-[40px] items-center gap-2 rounded-control border border-surface-3 bg-surface-2 px-3 text-caption font-medium text-ink-hi transition-colors hover:border-accent/50">
                                <span>{{ \App\Services\CatalogBrowser::SORTS[$activeSort] }}</span>
                                <svg class="h-3 w-3 text-ink-low" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-cloak class="absolute right-0 z-30 mt-2 w-52 rounded-card border border-surface-3 bg-surface-1 p-1 shadow-hover">
                                @foreach(\App\Services\CatalogBrowser::SORTS as $key => $label)
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'page' => null]) }}"
                                       class="block rounded-control px-3 py-2 text-caption transition-colors hover:bg-surface-2 {{ $activeSort === $key ? 'font-semibold text-accent-hover' : 'text-ink-mid' }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @if($products->isEmpty())
                    <div class="rounded-card border border-surface-3 bg-surface-2 p-10 text-center">
                        <p class="text-body font-semibold text-ink-hi">Nothing to show here yet</p>
                        <p class="mx-auto mt-1.5 max-w-sm text-caption text-ink-low">
                            @if($activeRegion)
                                No {{ $heading }} products in that region right now. Try all regions.
                            @else
                                We are restocking. Check back shortly, or tell us what you are after.
                            @endif
                        </p>
                        <div class="mt-5 flex justify-center gap-2">
                            @if($activeRegion)
                                <x-ui.button :href="request()->fullUrlWithQuery(['region' => null, 'page' => null])" variant="secondary" size="sm">Show all regions</x-ui.button>
                            @endif
                            <x-ui.button :href="route('contact')" size="sm">Request a product</x-ui.button>
                        </div>
                    </div>
                @else
                    <ul class="grid grid-cols-2 gap-3 md:grid-cols-3" aria-label="{{ $heading }} products">
                        @foreach($products as $product)
                            <li><x-catalog.product-card :product="$product" class="h-full" /></li>
                        @endforeach
                    </ul>

                    @if($products->hasPages())
                        <div class="mt-6">{{ $products->links('vendor.pagination.storefront') }}</div>
                    @endif
                @endif
            </div>

            {{-- Brand-level redemption steps, linked from every product page. --}}
            @if($brand?->how_to_redeem)
                <section id="how-to-redeem" class="mt-5 rounded-card border border-surface-3 bg-surface-1 p-5 md:p-6" aria-labelledby="redeem-heading">
                    <h2 id="redeem-heading" class="text-lede font-bold text-ink-hi">How to redeem {{ $brand->name }}</h2>
                    <div class="rich-content mt-3">{!! $brand->how_to_redeem !!}</div>
                </section>
            @endif

            @if($_owner->seo_content)
                <section class="mt-5 rounded-card border border-surface-3 bg-surface-1 p-5 md:p-6" aria-labelledby="about-owner-heading">
                    <h2 id="about-owner-heading" class="text-lede font-bold text-ink-hi">About {{ $heading }}</h2>
                    <div class="rich-content mt-3">{!! $_owner->seo_content !!}</div>
                </section>
            @endif
        </div>
    </div>
</div>

@endsection
