@extends('layouts.storefront')

@section('title', 'Customer Reviews & Ratings — Steam Store BD')
@section('meta_description', 'Real reviews from Steam Store BD customers in Bangladesh. Ratings for gift cards, game top-ups, keys and subscriptions, with delivery time and payment feedback.')

@php
    // Every bar width this page can render, written out so Tailwind's scanner
    // compiles them. A computed `style` attribute would be simpler and is
    // exactly what the design-system guard exists to keep out.
    $_barWidths = [
        'w-0', 'w-1/12', 'w-2/12', 'w-3/12', 'w-4/12', 'w-5/12', 'w-6/12',
        'w-7/12', 'w-8/12', 'w-9/12', 'w-10/12', 'w-11/12', 'w-full',
    ];
@endphp

@push('schema')
@php
    // A shop rating itself is not evidence, and Google has ignored review
    // markup an entity writes about itself since 2019. So this page publishes
    // the reviews as an ItemList — honest, machine-readable, each one tied to
    // the product it was actually left for — and claims no AggregateRating for
    // the business. The numbers a crawler can trust live on the profiles
    // linked further down, which is the whole point of listing them.
    $_reviewItems = $reviews->values()->map(function ($review, $index) use ($reviews) {
        $item = [
            '@type'         => 'Review',
            '@id'           => route('reviews') . '#review-' . $review->id,
            'author'        => ['@type' => 'Person', 'name' => $review->displayName()],
            'datePublished' => $review->created_at?->toDateString(),
            'reviewBody'    => $review->comment,
            'reviewRating'  => [
                '@type'       => 'Rating',
                'ratingValue' => (string) $review->rating,
                'bestRating'  => '5',
                'worstRating' => '1',
            ],
        ];

        if ($review->giftCardCategory) {
            $item['itemReviewed'] = [
                '@type' => 'Product',
                'name'  => $review->giftCardCategory->name,
                'url'   => route('product', $review->giftCardCategory->slug),
            ];
        }

        return [
            '@type'    => 'ListItem',
            'position' => ($reviews->firstItem() ?? 1) + $index,
            'item'     => array_filter($item),
        ];
    })->all();

    $_reviewSchema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'CollectionPage',
        'name'       => 'Customer reviews',
        'url'        => route('reviews'),
        'isPartOf'   => ['@id' => url('/') . '/#website'],
        'about'      => ['@id' => url('/') . '/#organization'],
        'mainEntity' => [
            '@type'           => 'ItemList',
            'numberOfItems'   => $reviewCount,
            'itemListElement' => $_reviewItems,
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($_reviewSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<div class="mx-auto max-w-4xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Reviews', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">Customer reviews</h1>
    <p class="mt-1 text-body text-ink-mid">
        What buyers say after ordering gift cards, top-ups, keys and subscriptions from Steam Store BD.
    </p>

    @if($reviewCount > 0)
        {{-- ══ Summary ══ --}}
        <div class="mt-6 grid gap-6 rounded-card border border-surface-3 bg-surface-1 p-6 sm:grid-cols-[auto,1fr] sm:gap-8">
            <div class="text-center sm:text-left">
                <p class="text-display font-extrabold tabular-nums text-ink-hi">{{ number_format($averageRating, 1) }}</p>
                <div class="mt-1 flex justify-center sm:justify-start">
                    <x-ui.stars :rating="$averageRating" size="w-4 h-4" />
                </div>
                <p class="mt-1 text-caption text-ink-low">{{ number_format($reviewCount) }} approved {{ Str::plural('review', $reviewCount) }}</p>
            </div>

            <div class="space-y-1.5">
                @foreach($breakdown as $stars => $count)
                    @php
                        $_share = $reviewCount > 0 ? $count / $reviewCount : 0;
                        $_width = $_barWidths[(int) round($_share * 12)];
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-10 flex-shrink-0 text-meta font-semibold tabular-nums text-ink-mid">{{ $stars }} ★</span>
                        <span class="h-2 flex-1 overflow-hidden rounded-chip bg-surface-2">
                            <span class="block h-full {{ $_width }} rounded-chip bg-warning"></span>
                        </span>
                        <span class="w-10 flex-shrink-0 text-right text-meta tabular-nums text-ink-low">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══ Where these came from ══ --}}
    <div class="mt-4 rounded-card border border-surface-3 bg-surface-1 p-5">
        <h2 class="text-body font-semibold text-ink-hi">Where these reviews come from</h2>
        <p class="mt-1 text-caption leading-relaxed text-ink-mid">
            Every review below was left by someone who ordered from us — on the website after a completed
            order, or in a WhatsApp, Messenger or Steam conversation with our support team. We publish them
            after checking the order behind them, and we do not delete a review for being critical.
        </p>

        @if($socialProfiles->links()->isNotEmpty())
            <p class="mt-3 text-caption leading-relaxed text-ink-mid">
                Reviews we cannot edit live on our public profiles:
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($socialProfiles->links() as $profile)
                    <a href="{{ $profile['url'] }}" target="_blank" rel="noopener nofollow"
                       class="inline-flex items-center gap-1.5 rounded-chip border border-surface-3 bg-surface-2 px-3 py-1.5 text-meta font-semibold text-ink-mid transition-colors hover:border-accent/45 hover:text-accent-hover">
                        {{ $profile['label'] }}
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M17.5 6.5L10 14M16 13v5a1 1 0 01-1 1H6a1 1 0 01-1-1V9a1 1 0 011-1h5"/></svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ══ The reviews ══ --}}
    @if($reviews->isEmpty())
        <div class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-8 text-center">
            <p class="text-body font-semibold text-ink-hi">No reviews published yet</p>
            <p class="mt-1 text-caption text-ink-low">Order something and you could be the first to review us.</p>
            <x-ui.button :href="route('home')" class="mt-4">Browse the catalog</x-ui.button>
        </div>
    @else
        <div class="mt-6 space-y-3">
            @foreach($reviews as $review)
                <article id="review-{{ $review->id }}" class="scroll-mt-24 rounded-card border border-surface-3 bg-surface-1 p-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.stars :rating="$review->rating" />
                        @if($review->is_verified_purchase)
                            <x-ui.badge tone="success">Verified purchase</x-ui.badge>
                        @endif
                        @if($review->giftCardCategory)
                            <a href="{{ route('product', $review->giftCardCategory->slug) }}"
                               class="text-meta font-semibold text-accent transition-colors hover:text-accent-hover">
                                {{ $review->giftCardCategory->name }}
                            </a>
                        @endif
                    </div>

                    <p class="mt-3 text-caption leading-relaxed text-ink-mid">{{ $review->comment }}</p>

                    <p class="mt-3 text-meta text-ink-low">
                        <span class="font-semibold text-ink-hi">{{ $review->displayName() }}</span>
                        · {{ $review->platformLabel() }}
                        @if($review->created_at)
                            ·
                            <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('j M Y') }}</time>
                        @endif
                        ·
                        <a href="{{ route('reviews') }}#review-{{ $review->id }}" class="transition-colors hover:text-accent-hover">Link</a>
                    </p>
                </article>
            @endforeach
        </div>

        @if($reviews->hasPages())
            <div class="mt-6">{{ $reviews->links('vendor.pagination.storefront') }}</div>
        @endif
    @endif

    <div class="mt-8 rounded-card border border-surface-3 bg-surface-1 p-6 text-center">
        <p class="text-body font-semibold text-ink-hi">Bought from us before?</p>
        <p class="mt-1 text-caption text-ink-low">Order something and come back here — leaving a review takes a minute.</p>
        <x-ui.button :href="route('orders.lookup')" class="mt-4">Find your order</x-ui.button>
    </div>
</div>

@endsection
