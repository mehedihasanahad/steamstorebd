@extends('layouts.storefront')

@section('title', $term ? 'Search: ' . $term . ' — Steam Store BD' : 'Search — Steam Store BD')
@section('robots', 'noindex, follow')
@section('meta_description', 'Search gift cards, game top-ups, keys and subscriptions at Steam Store BD.')

@section('content')

<div class="mx-auto max-w-shell px-4 py-5 sm:px-6 lg:px-8">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Search', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">
        @if($term)Results for “{{ $term }}”@else Search the catalog @endif
    </h1>
    @if($term)
        <p class="mt-1 text-caption text-ink-low">{{ $products->count() + $brands->count() }} {{ Str::plural('match', $products->count() + $brands->count()) }}</p>
    @endif

    <div class="mt-4 max-w-xl"><x-ui.search-box id="page-search" /></div>

    @if($brands->isNotEmpty())
        <section class="mt-6" aria-labelledby="search-brands-heading">
            <h2 id="search-brands-heading" class="mb-3 text-lede font-bold text-ink-hi">Brands</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($brands as $brand)
                    <x-ui.chip :href="route('brand', $brand->slug)">{{ $brand->name }}</x-ui.chip>
                @endforeach
            </div>
        </section>
    @endif

    @if($products->isNotEmpty())
        <section class="mt-6" aria-labelledby="search-products-heading">
            <h2 id="search-products-heading" class="mb-3 text-lede font-bold text-ink-hi">Products</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach($products as $product)
                    <x-catalog.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @elseif($term)
        <div class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-10 text-center">
            <p class="text-body font-semibold text-ink-hi">Nothing matched “{{ $term }}”</p>
            <p class="mx-auto mt-1.5 max-w-sm text-caption text-ink-low">Try a brand name, or tell us what you want and we will look into stocking it.</p>
            <x-ui.button :href="route('contact')" size="sm" class="mt-5">Request a product</x-ui.button>
        </div>
    @endif

    {{-- Empty state and no-result state both land on the same next step:
         the sections, which is the only navigation that always works. --}}
    @if($sections->isNotEmpty())
        <section class="mt-section md:mt-section-lg pb-section" aria-labelledby="search-sections-heading">
            <h2 id="search-sections-heading" class="mb-3 text-lede font-bold text-ink-hi">Browse by section</h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach($sections as $section)
                    <a href="{{ route('category', $section->slug) }}"
                       class="rounded-card border border-surface-3 bg-surface-1 p-4 transition-colors hover:border-accent/50">
                        <p class="text-body font-semibold text-ink-hi">{{ $section->name }}</p>
                        <p class="mt-1 text-meta text-ink-low">{{ $section->mainCategories->count() }} {{ Str::plural('brand', $section->mainCategories->count()) }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

@endsection
