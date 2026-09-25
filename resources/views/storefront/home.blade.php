@extends('layouts.storefront')

@section('title', 'Buy Gift Cards, Game Top-Ups & Subscriptions in Bangladesh — Steam Store BD')
@section('meta_description', 'Steam Store BD — gift cards, game top-ups, game keys and subscriptions in Bangladesh. Pay with bKash or Nagad, get your code instantly. 100% genuine.')

@push('schema')
@php
$_itemList = $mainCategories->isNotEmpty() ? $mainCategories : $fallbackCategories;
$_schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'ItemList',
    'name'     => 'Gift Cards Bangladesh',
    'description' => 'Buy digital gift cards in Bangladesh with bKash payment',
    'url'      => url('/'),
    'itemListElement' => $_itemList->values()->map(fn($item, $i) => [
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'name'     => $item->name,
        'url'      => $mainCategories->isNotEmpty() ? route('brand', $item->slug) : route('product', $item->slug),
    ])->toArray(),
];
@endphp
<script type="application/ld+json">{!! json_encode($_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<div class="mx-auto max-w-shell px-4 sm:px-6 lg:px-8">

    {{-- ══ 1 · Hero slider ══ --}}
    @if($banners->isNotEmpty())
        {{-- The slider is artwork, not a heading, so the page still needs to
             say what it is — for a screen reader and for a crawler, both of
             which would otherwise find a homepage with no h1 at all. --}}
        <h1 class="sr-only">Gift cards, game top-ups, keys and subscriptions in Bangladesh</h1>

        <div class="pt-4">
            <x-ui.hero-slider :banners="$banners" />
        </div>
    @else
        {{-- No campaign loaded: a plain statement of what the shop sells, which
             is more useful than an empty carousel frame. --}}
        <section class="mt-4 rounded-card border border-surface-3 bg-surface-1 px-5 py-8 md:px-10 md:py-12">
            <h1 class="text-title md:text-display font-extrabold text-ink-hi">Gift cards, top-ups and keys — delivered in minutes</h1>
            <p class="mt-2 max-w-2xl text-body text-ink-mid">
                Pay with bKash, Nagad or Rocket and get your code by e-mail and in your account. 100% genuine, at the best BDT price.
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach($sections->take(4) as $section)
                    <x-ui.chip :href="route('category', $section->slug)">{{ $section->name }}</x-ui.chip>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ══ 2 · Exclusive offers ══ --}}
    {{-- Switched on, named and subtitled in Site Settings; ordered by how deep
         the discount is, so the best saving leads. View all opens /offers,
         which carries the rest of them. --}}
    @if($offers->enabled() && $offerCards->isNotEmpty())
        <x-catalog.section-rail :title="$offers->title()"
                                :subtitle="$offers->subtitle() ?: null"
                                :view-all="route('offers')"
                                id="section-exclusive-offers"
                                class="mt-section md:mt-section-lg">
            @foreach($offerCards as $card)
                <x-catalog.deal-card :card="$card" />
            @endforeach
        </x-catalog.section-rail>
    @endif

    {{-- ══ 3 · Featured items ══ --}}
    @if($featuredProducts->isNotEmpty())
        <x-catalog.section-rail title="Featured" class="mt-section md:mt-section-lg">
            @foreach($featuredProducts as $product)
                <x-catalog.product-card :product="$product" width="w-40 sm:w-48" />
            @endforeach
        </x-catalog.section-rail>
    @endif

    {{-- ══ 4 · One row per catalog section ══ --}}
    {{-- Slider or grid is the section's own choice, made in admin: how many
         brands a vertical holds is what decides which shape reads better, and
         that is known where the catalog is filled, not here. The rail fixes
         its cards' width; the grid lets the column set it. --}}
    @foreach($sections as $section)
        @if($section->isGrid())
            <x-catalog.section-grid :title="$section->name"
                                    :view-all="route('category', $section->slug)"
                                    :id="'section-' . $section->slug"
                                    class="mt-section md:mt-section-lg">
                @foreach($section->mainCategories as $brand)
                    <x-catalog.brand-card :brand="$brand" width="w-full" />
                @endforeach
            </x-catalog.section-grid>
        @else
            <x-catalog.section-rail :title="$section->name"
                                    :view-all="route('category', $section->slug)"
                                    :id="'section-' . $section->slug"
                                    class="mt-section md:mt-section-lg">
                @foreach($section->mainCategories as $brand)
                    <x-catalog.brand-card :brand="$brand" />
                @endforeach
            </x-catalog.section-rail>
        @endif
    @endforeach

    {{-- The safety net: a database with no sections configured still shows
         everything it can sell, keyed off products rather than brands. --}}
    @if($sections->isEmpty() && $mainCategories->isNotEmpty())
        <x-catalog.section-rail title="Our products" class="mt-section md:mt-section-lg">
            @foreach($mainCategories as $brand)
                <x-catalog.brand-card :brand="$brand" />
            @endforeach
        </x-catalog.section-rail>
    @endif

    @if($fallbackCategories->isNotEmpty())
        <section aria-labelledby="all-products-heading" class="mt-section md:mt-section-lg">
            <h2 id="all-products-heading" class="mb-3 text-lede md:text-title font-bold uppercase tracking-wide text-ink-hi">Choose your gift card</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach($fallbackCategories as $category)
                    <x-catalog.product-card :product="$category" :show-price="false" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ══ Trust strip ══ --}}
    <section aria-label="Why buy here" class="mt-section md:mt-section-lg grid grid-cols-2 gap-3 lg:grid-cols-4">
        @php
            $trustItems = [
                ['Instant delivery', 'Your code lands in your inbox within minutes.'],
                ['Secure payment', collect($activePaymentMethods)->pluck('name')->implode(' · ') ?: 'bKash · Nagad · Rocket'],
                ['100% genuine', 'Every code sourced directly and checked before sale.'],
                ['Support that answers', 'WhatsApp and Messenger, every day.'],
            ];
        @endphp
        @foreach($trustItems as [$title, $desc])
            <div class="rounded-card border border-surface-3 bg-surface-1 p-4">
                <p class="text-caption font-semibold text-ink-hi">{{ $title }}</p>
                <p class="mt-1 text-meta leading-relaxed text-ink-low">{{ $desc }}</p>
            </div>
        @endforeach
    </section>

    {{-- ══ 5 · Customer reviews ══ --}}
    @if($reviews->isNotEmpty())
        <x-catalog.section-rail title="What buyers say" class="mt-section md:mt-section-lg">
            @foreach($reviews as $review)
                <article class="w-72 rounded-card border border-surface-3 bg-surface-1 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <x-ui.stars :rating="$review->rating" />
                        @if($review->is_verified_purchase)
                            <x-ui.badge tone="success">Verified</x-ui.badge>
                        @endif
                    </div>
                    <p class="mt-3 text-caption leading-relaxed text-ink-mid line-clamp-5">{{ $review->comment }}</p>
                    <p class="mt-3 text-meta font-semibold text-ink-hi">
                        {{ $review->displayName() }}
                        <span class="font-normal text-ink-low">· {{ $review->platformLabel() }}</span>
                    </p>
                </article>
            @endforeach
        </x-catalog.section-rail>
    @endif

    {{-- ══ 6 · Referral programme ══ --}}
    @php
        $referralEnabled  = site_setting('referral_enabled', false);
        $refRewardAmt     = (float) site_setting('referral_owner_reward_amount', 0);
        $refDiscountType  = site_setting('referral_discount_type', 'flat');
        $refDiscountVal   = (float) site_setting('referral_discount_value', 0);
        $refDiscountCap   = (float) site_setting('referral_max_discount_cap', 0);
        $refDiscountLabel = $refDiscountType === 'percentage'
            ? $refDiscountVal . '% off' . ($refDiscountCap > 0 ? ' (up to ' . format_bdt($refDiscountCap) . ')' : '')
            : format_bdt($refDiscountVal) . ' off';
    @endphp
    @if($referralEnabled)
        <section aria-labelledby="referral-heading" class="mt-section md:mt-section-lg rounded-card border border-surface-3 bg-surface-1 p-5 md:p-8">
            <x-ui.badge tone="accent">Referral programme</x-ui.badge>
            <h2 id="referral-heading" class="mt-3 text-title font-bold text-ink-hi">Share and earn together</h2>
            <p class="mt-1.5 max-w-2xl text-body text-ink-mid">
                Share your code. Your friend saves on their first order, and your wallet is credited once it is confirmed.
            </p>

            <ol class="mt-6 grid gap-3 md:grid-cols-3">
                @foreach([
                    ['1', 'Share your code', 'Every account gets a unique referral code the moment you sign up.'],
                    ['2', 'Friend gets ' . $refDiscountLabel, 'They enter your code at checkout and it comes off their first order.'],
                    ['3', 'You earn ' . format_bdt($refRewardAmt), 'Credited to your wallet as soon as their order is confirmed.'],
                ] as [$step, $title, $desc])
                    <li class="rounded-card border border-surface-3 bg-surface-2 p-4">
                        <span class="text-meta font-bold text-accent-hover">Step {{ $step }}</span>
                        <p class="mt-1.5 text-caption font-semibold text-ink-hi">{{ $title }}</p>
                        <p class="mt-1 text-meta leading-relaxed text-ink-low">{{ $desc }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="mt-6">
                @auth
                    <x-ui.copy-script />
                    <div x-data="copyable(@js(auth()->user()->referral_code ?? ''))" class="flex flex-wrap items-center gap-3">
                        <span class="rounded-control border border-surface-3 bg-surface-2 px-4 py-2.5 font-mono text-body font-bold tracking-widest text-ink-hi">
                            {{ auth()->user()->referral_code ?? '—' }}
                        </span>
                        <x-ui.button variant="secondary" size="sm" x-on:click="copy()">
                            <span x-show="! copied && ! failed">Copy code</span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </x-ui.button>
                        <x-ui.button :href="route('referral.dashboard')" size="sm">View dashboard</x-ui.button>
                    </div>
                @else
                    <x-ui.button :href="route('register')">Create an account and get your code</x-ui.button>
                    <p class="mt-2 text-meta text-ink-low">Free to join. Your code is generated on signup.</p>
                @endauth
            </div>
        </section>
    @endif

    {{-- ══ 7 · Reseller programme ══ --}}
    @if(site_setting('reseller_program_enabled', false))
        <section aria-labelledby="reseller-heading" class="mt-section md:mt-section-lg rounded-card border border-surface-3 bg-surface-1 p-5 md:p-8">
            <x-ui.badge tone="success">Reseller programme</x-ui.badge>
            <h2 id="reseller-heading" class="mt-3 text-title font-bold text-ink-hi">Selling gift cards? Partner with us</h2>
            <p class="mt-1.5 max-w-2xl text-body text-ink-mid">
                Facebook page sellers, shops and gaming zones — wholesale pricing, priority delivery and bulk stock, so you can serve your own customers faster.
            </p>

            <div class="mt-6 grid gap-3 md:grid-cols-3">
                @foreach([
                    ['Wholesale pricing', 'Dedicated bulk rates on every brand we stock. The more you move, the better your rate.'],
                    ['Priority delivery', 'Your orders jump the queue, so you never keep your own customer waiting.'],
                    ['Dedicated support', 'A direct WhatsApp line to our team — no tickets, no queue, straight to a human.'],
                ] as [$title, $desc])
                    <div class="rounded-card border border-surface-3 bg-surface-2 p-4">
                        <p class="text-caption font-semibold text-ink-hi">{{ $title }}</p>
                        <p class="mt-1 text-meta leading-relaxed text-ink-low">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                <x-ui.button :href="route('reseller')" variant="success">Become a reseller</x-ui.button>
                <p class="mt-2 text-meta text-ink-low">Free to apply. No trade licence needed.</p>
            </div>
        </section>
    @endif

    {{-- ══ SEO content ══ --}}
    <section aria-labelledby="about-heading" class="mt-section md:mt-section-lg pb-section md:pb-section-lg">
        <h2 id="about-heading" class="text-title font-bold text-ink-hi">Bangladesh's most trusted digital goods store</h2>
        <p class="mt-1.5 max-w-3xl text-body text-ink-mid">
            One place to <strong class="text-ink-hi">buy gift cards in Bangladesh</strong> — Steam, Google Play, App Store, PlayStation and more — plus game top-ups, game keys and subscriptions, all paid with bKash.
        </p>

        <div class="mt-5 grid gap-3 md:grid-cols-2">
            @foreach([
                ['Buy gift cards in Bangladesh', 'Steam Store BD delivers 100% genuine codes instantly. Pay securely with bKash or Nagad and receive your code within minutes — no waiting, no bank card.'],
                ['Best BDT prices, every day', 'Competitive BDT rates on every denomination, with no hidden charges and transparent pricing across every brand we stock.'],
                ['Why choose Steam Store BD', 'We source every code directly, so every card is genuine, and every purchase is backed by our replacement guarantee. Trusted by 10,000+ Bangladeshi customers.'],
                ['How to buy with bKash', 'Pick a brand, choose a denomination, pay with bKash. The code is delivered to your e-mail and stays in your account. The fastest way to top up any platform.'],
            ] as [$title, $body])
                <div class="rounded-card border border-surface-3 bg-surface-1 p-5">
                    <h3 class="text-body font-semibold text-ink-hi">{{ $title }}</h3>
                    <p class="mt-2 text-caption leading-relaxed text-ink-mid">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </section>
</div>

@endsection
