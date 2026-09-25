@extends('layouts.storefront')

@php
    $_heading = $offers->title();
    $_scoped  = $section ? $_heading . ' — ' . $section->name : $_heading;
    $_seoDesc = 'Every discounted gift card, top-up and subscription at Steam Store BD, deepest discount first. Pay with bKash or Nagad and get your code in minutes.';
@endphp

@section('title', $_scoped . ' — Steam Store BD')
@section('meta_description', $_seoDesc)

@push('schema')
@php
    $_crumbs = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $_heading, 'item' => route('offers')],
    ];

    if ($section) {
        $_crumbs[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $section->name, 'item' => route('offers', ['section' => $section->slug])];
    }

    $_pageSchema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            ['@type' => 'BreadcrumbList', 'itemListElement' => $_crumbs],
            [
                '@type'           => 'ItemList',
                'name'            => $_scoped,
                'description'     => $_seoDesc,
                'url'             => route('offers'),
                'itemListElement' => $cards->values()->map(fn ($card, $i) => [
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'name'     => $card->name,
                    'url'      => route('product', $card->category->slug),
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
        ['label' => $_heading, 'url' => $section ? route('offers') : null],
        $section ? ['label' => $section->name, 'url' => null] : null,
    ]))" />

    <div class="rounded-card border border-surface-3 bg-surface-1 p-4 md:p-5">

        <div class="mb-5">
            <x-ui.badge tone="success">{{ $totalOffers }} {{ Str::plural('offer', $totalOffers) }} live</x-ui.badge>
            <h1 class="mt-3 text-title md:text-display font-extrabold text-ink-hi">{{ $_heading }}</h1>
            <p class="mt-1.5 max-w-2xl text-body text-ink-mid">
                {{ $offers->subtitle() ?: 'Every card we have discounted right now, deepest discount first. Prices go back up when the offer ends.' }}
            </p>
        </div>

        {{-- Section filter. Real links rather than tabs, so a filtered view is
             a URL a shopper can share and a crawler can follow. All is the
             default and stays first, whatever the catalog order is. --}}
        @if($sections->isNotEmpty())
            <div class="-mx-4 mb-5 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-hide md:mx-0 md:flex-wrap md:overflow-visible md:px-0"
                 role="navigation" aria-label="Filter offers by section">
                <x-ui.chip :href="route('offers')" :active="$section === null">
                    All <span class="text-ink-low">{{ $totalOffers }}</span>
                </x-ui.chip>
                @foreach($sections as $offerSection)
                    <x-ui.chip :href="route('offers', ['section' => $offerSection->slug])"
                               :active="$section?->id === $offerSection->id">
                        {{ $offerSection->name }} <span class="text-ink-low">{{ $offerSection->offers_count }}</span>
                    </x-ui.chip>
                @endforeach
            </div>
        @endif

        @if($cards->isEmpty())
            <div class="rounded-card border border-surface-3 bg-surface-2 p-10 text-center">
                <p class="text-body font-semibold text-ink-hi">No offers here right now</p>
                <p class="mx-auto mt-1.5 max-w-sm text-caption text-ink-low">
                    @if($section)
                        Nothing is discounted in {{ $section->name }} at the moment. The other sections may still have something.
                    @else
                        Everything is at its usual price today. New offers go up regularly — check back soon.
                    @endif
                </p>
                <div class="mt-5 flex justify-center gap-2">
                    @if($section)
                        <x-ui.button :href="route('offers')" variant="secondary" size="sm">Show all offers</x-ui.button>
                    @endif
                    <x-ui.button :href="route('home')" size="sm">Browse the shop</x-ui.button>
                </div>
            </div>
        @else
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6" aria-label="{{ $_scoped }}">
                @foreach($cards as $card)
                    <li><x-catalog.deal-card :card="$card" width="w-full" class="h-full" /></li>
                @endforeach
            </ul>

            @if($cards->hasPages())
                <div class="mt-6">{{ $cards->links('vendor.pagination.storefront') }}</div>
            @endif
        @endif
    </div>
</div>

@endsection
