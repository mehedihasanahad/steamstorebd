@extends('layouts.storefront')

@php
    $_inStock     = $denominations->filter(fn ($denomination) => $denomination->stock_count > 0);
    $_lowestPrice = $_inStock->min('price_bdt');
    $_payWith     = $paymentMethodNames ? ' with ' . \Illuminate\Support\Arr::join($paymentMethodNames, ', ', ' or ') : '';
    $_image       = $category->image ?: $category->mainCategory?->image;
    $_imageUrl    = $_image ? Storage::disk('public')->url($_image) : null;
    $_description = $category->seo_description
        ?: 'Buy ' . $category->name . ' in Bangladesh' . $_payWith . '. Instant code delivery to email.'
            . ($_lowestPrice ? ' Prices from ৳' . number_format((float) $_lowestPrice) . '.' : '')
            . ' 100% genuine codes.';

    // The delivery promise a shopper can actually rely on: instant only while
    // something is in stock and delivered from the code pool.
    $_deliveryLabel = $_inStock->isEmpty()
        ? 'Restocking'
        : ($_inStock->first()->usesCodePool()
            ? ($_inStock->first()->delivery_eta_label ?: 'Instant delivery')
            : ($_inStock->first()->delivery_eta_label ?: 'Manual delivery'));

    $_tabs = array_filter([
        'description'  => ($category->long_description || $category->description) ? 'Description' : null,
        'instructions' => $category->redemptionInstructions() ? 'Instructions' : null,
        'faq'          => $category->faqEntries() ? 'FAQ' : null,
    ]);
@endphp

@section('title', ($category->seo_title ?: 'Buy ' . $category->name . ' in Bangladesh') . ' — Steam Store BD')
@section('meta_description', $_description)
@section('og_type', 'product')
@section('og_image_alt', 'Buy ' . $category->name . ' in Bangladesh — Steam Store BD')
@if($_imageUrl)
@section('og_image', $_imageUrl)
@endif

@push('schema')
@php
    $_productSchema = [
        '@type'       => 'Product',
        'name'        => $category->name,
        'description' => $_description,
        'brand'       => ['@type' => 'Brand', 'name' => $category->mainCategory->name ?? $category->name],
        'url'         => route('product', $category->slug),
    ];
    if ($_imageUrl) {
        $_productSchema['image'] = $_imageUrl;
    }
    if ($averageRating !== null && $reviewCount > 0) {
        $_productSchema['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => (string) $averageRating,
            'reviewCount' => $reviewCount,
        ];
    }
    if ($denominations->isNotEmpty()) {
        $_productSchema['offers'] = [
            '@type'         => 'AggregateOffer',
            'priceCurrency' => 'BDT',
            'lowPrice'      => (string) $denominations->min('price_bdt'),
            'highPrice'     => (string) $denominations->max('price_bdt'),
            'offerCount'    => $denominations->count(),
            'availability'  => $_inStock->isNotEmpty() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'seller'        => ['@id' => url('/') . '/#organization'],
        ];
    }

    $_breadcrumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')]];
    if ($category->mainCategory?->catalogSection) {
        $_breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $category->mainCategory->catalogSection->name, 'item' => route('category', $category->mainCategory->catalogSection->slug)];
    }
    if ($category->mainCategory) {
        $_breadcrumbs[] = ['@type' => 'ListItem', 'position' => count($_breadcrumbs) + 1, 'name' => $category->mainCategory->name, 'item' => route('brand', $category->mainCategory->slug)];
    }
    $_breadcrumbs[] = ['@type' => 'ListItem', 'position' => count($_breadcrumbs) + 1, 'name' => $category->name, 'item' => route('product', $category->slug)];

    $_graph = [
        ['@type' => 'BreadcrumbList', 'itemListElement' => $_breadcrumbs],
        $_productSchema,
    ];

    if ($category->faqEntries()) {
        $_graph[] = [
            '@type'      => 'FAQPage',
            'mainEntity' => collect($category->faqEntries())->map(fn ($entry) => [
                '@type'          => 'Question',
                'name'           => $entry['question'] ?? '',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $entry['answer'] ?? ''],
            ])->all(),
        ];
    }

    $_pageSchema = ['@context' => 'https://schema.org', '@graph' => $_graph];
@endphp
<script type="application/ld+json">{!! json_encode($_pageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

@php
    // Everything the purchase panel needs about each denomination, resolved
    // once here so the panel never re-derives a price or a cap in the browser.
    $_cards = $denominations->values()->map(fn ($card) => [
        'id'    => $card->id,
        'name'  => $card->name,
        'price' => (float) $card->price_bdt,
        'stock' => $card->stock_count,
        'min'   => max(1, $card->min_quantity),
        'max'   => $card->maxOrderableQuantity(),
        'eta'   => $card->delivery_eta_label,
    ]);
    $_firstInStock = $denominations->values()->search(fn ($card) => $card->stock_count > 0);
@endphp

<div x-data="productPage(@js($_cards), @js($_firstInStock === false ? null : $_firstInStock))">

    {{-- ══ Hero ══ --}}
    <div class="border-b border-surface-3 bg-surface-1">
        <div class="mx-auto max-w-shell px-4 py-5 sm:px-6 lg:px-8">

            <x-catalog.breadcrumbs class="mb-4" :items="array_values(array_filter([
                ['label' => 'Home', 'url' => route('home')],
                $category->mainCategory?->catalogSection ? ['label' => $category->mainCategory->catalogSection->name, 'url' => route('category', $category->mainCategory->catalogSection->slug)] : null,
                $category->mainCategory ? ['label' => $category->mainCategory->name, 'url' => route('brand', $category->mainCategory->slug)] : null,
                ['label' => $category->name, 'url' => null],
            ]))" />

            <div class="flex flex-wrap items-start gap-4">
                <x-catalog.artwork :image="$_image" :name="$category->name" ratio="aspect-square" eager
                                   class="w-16 flex-shrink-0 rounded-card border border-surface-3 md:w-20" :width="80" :height="80" />

                <div class="min-w-0 flex-1">
                    <h1 class="text-title md:text-display font-extrabold text-ink-hi">{{ $category->name }}</h1>

                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2">
                        <x-catalog.rating :rating="$averageRating" :count="$reviewCount" />

                        <span class="inline-flex items-center gap-1.5 text-caption font-medium {{ $_inStock->isNotEmpty() ? 'text-success' : 'text-warning' }}">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                            {{ $_deliveryLabel }}
                        </span>

                        {{-- Region switcher: the same product in another region is a
                             separate row, so this moves between pages rather than
                             filtering this one. --}}
                        @if($regionalSiblings->isNotEmpty())
                            <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                                <button @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                                        class="flex min-h-[32px] items-center gap-1.5 rounded-chip border border-surface-3 bg-surface-2 px-2.5 text-caption font-medium text-ink-hi transition-colors hover:border-accent/50">
                                    <span aria-hidden="true">{{ $category->regionFlag() ?: '🌐' }}</span>
                                    <span>{{ $category->regionName() ?: 'Choose region' }}</span>
                                    <svg class="h-3 w-3 text-ink-low transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="open" x-cloak class="absolute left-0 z-30 mt-2 w-56 rounded-card border border-surface-3 bg-surface-1 p-1 shadow-hover">
                                    <span class="flex items-center gap-2 rounded-control bg-accent/15 px-3 py-2 text-caption font-semibold text-accent-hover">
                                        <span aria-hidden="true">{{ $category->regionFlag() ?: '🌐' }}</span>
                                        {{ $category->regionName() ?: $category->name }}
                                    </span>
                                    @foreach($regionalSiblings as $sibling)
                                        <a href="{{ route('product', $sibling->slug) }}"
                                           class="flex items-center gap-2 rounded-control px-3 py-2 text-caption text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi">
                                            <span aria-hidden="true">{{ $sibling->regionFlag() ?: '🌐' }}</span>
                                            <span class="truncate">{{ $sibling->regionName() ?: $sibling->name }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($category->regionName())
                            <x-catalog.region-chip :region="$category->region" />
                        @endif
                    </div>

                    @if($category->description)
                        <p class="mt-3 flex items-start gap-2 text-caption leading-relaxed text-ink-mid">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-ink-low" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><strong class="text-ink-hi">Important note:</strong> {{ $category->description }}</span>
                        </p>
                    @endif
                </div>

                {{-- Favourites are for signed-in shoppers; a guest is offered the
                     sign-in that makes the control mean something. --}}
                @auth
                    <form method="POST" action="{{ route('favourites.toggle', $category) }}" class="flex-shrink-0">
                        @csrf
                        @php $_saved = auth()->user()->hasFavourited($category->id); @endphp
                        <x-ui.button type="submit" variant="{{ $_saved ? 'outline' : 'secondary' }}" size="sm">
                            <svg class="h-4 w-4" fill="{{ $_saved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            {{ $_saved ? 'Saved' : 'Add to favourite' }}
                        </x-ui.button>
                    </form>
                @else
                    <x-ui.button :href="route('login')" variant="secondary" size="sm" class="flex-shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        Add to favourite
                    </x-ui.button>
                @endauth
            </div>
        </div>
    </div>

    {{-- ══ Body ══ --}}
    <div class="mx-auto max-w-shell px-4 py-5 pb-28 sm:px-6 lg:px-8 lg:pb-section-lg">
        <div class="grid grid-cols-12 gap-5">

            {{-- Denominations + content --}}
            <div class="col-span-12 lg:col-span-8">

                @if($denominations->isEmpty())
                    <div class="rounded-card border border-surface-3 bg-surface-1 p-10 text-center">
                        <p class="text-body font-semibold text-ink-hi">Currently unavailable</p>
                        <p class="mt-1.5 text-caption text-ink-low">This product has no denominations on sale right now. Message us and we will tell you when it is back.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($denominations as $index => $denomination)
                            <x-catalog.denomination-tile :card="$denomination" :index="$index" :image="$denomination->image ?: $_image" />
                        @endforeach
                    </div>
                @endif

                @if(! empty($_tabs))
                    <x-ui.tabs :tabs="$_tabs" class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-4 md:p-5">
                        @if(isset($_tabs['description']))
                            <x-ui.tab-panel name="description">
                                <div class="rich-content">
                                    {!! $category->long_description ?: e($category->description) !!}
                                </div>
                            </x-ui.tab-panel>
                        @endif

                        @if(isset($_tabs['instructions']))
                            <x-ui.tab-panel name="instructions">
                                <div class="rich-content">{!! $category->redemptionInstructions() !!}</div>
                            </x-ui.tab-panel>
                        @endif

                        @if(isset($_tabs['faq']))
                            <x-ui.tab-panel name="faq">
                                <dl class="divide-y divide-surface-3">
                                    @foreach($category->faqEntries() as $entry)
                                        <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="py-3 first:pt-0 last:pb-0">
                                            <dt>
                                                <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                                                        class="flex w-full items-center justify-between gap-3 text-left text-body font-semibold text-ink-hi">
                                                    <span>{{ $entry['question'] ?? '' }}</span>
                                                    <svg class="h-4 w-4 flex-shrink-0 text-ink-low transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </dt>
                                            <dd x-show="open" x-cloak class="mt-2 text-caption leading-relaxed text-ink-mid">{{ $entry['answer'] ?? '' }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </x-ui.tab-panel>
                        @endif
                    </x-ui.tabs>
                @endif
            </div>

            {{-- Purchase panel --}}
            <div class="col-span-12 lg:col-span-4">
                <div class="space-y-4 lg:sticky lg:top-[120px]">

                    @if($denominations->isNotEmpty())
                        <form method="POST" action="{{ route('cart.add') }}" x-ref="form" class="space-y-4">
                            @csrf
                            <input type="hidden" name="gift_card_id" :value="current?.id ?? ''">
                            <input type="hidden" name="quantity" :value="qty">
                            <input type="hidden" name="redirect_to" x-ref="redirect" value="cart">

                            @if($category->needsBuyerInput())
                                <x-catalog.buyer-inputs :schema="$category->buyerInputSchema()" />
                            @endif

                            <div class="rounded-card border border-surface-3 bg-surface-1 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <label id="qty-label" class="text-body font-semibold text-ink-hi">Quantity</label>

                                    <div class="flex items-center rounded-control border border-surface-3 bg-surface-2">
                                        <button type="button" @click="dec()" :disabled="!current || qty <= current.min" aria-label="Decrease quantity"
                                                class="flex h-10 w-10 items-center justify-center text-lede font-bold text-ink-mid transition-colors hover:text-ink-hi disabled:opacity-30 disabled:cursor-not-allowed">&minus;</button>
                                        <span aria-labelledby="qty-label" aria-live="polite"
                                              class="w-10 border-x border-surface-3 py-2 text-center text-body font-bold tabular-nums text-ink-hi" x-text="qty"></span>
                                        <button type="button" @click="inc()" :disabled="!current || qty >= current.max" aria-label="Increase quantity"
                                                class="flex h-10 w-10 items-center justify-center text-lede font-bold text-ink-mid transition-colors hover:text-ink-hi disabled:opacity-30 disabled:cursor-not-allowed">+</button>
                                    </div>
                                </div>

                                <p class="mt-2 text-meta text-ink-low" x-show="current" x-cloak
                                   x-text="'Purchase limit (' + current.min + ' – ' + current.max + ')'"></p>
                                <p class="mt-2 text-meta text-warning" x-show="!current" x-cloak>Select a denomination that is in stock.</p>
                            </div>

                            <div class="rounded-card border border-surface-3 bg-surface-1 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-body font-semibold text-ink-hi">Total</span>
                                    <span class="text-title font-bold tabular-nums text-success" x-text="totalLabel"></span>
                                </div>
                                <p class="mt-1 text-meta text-ink-low" x-show="current && qty > 1" x-cloak
                                   x-text="unitLabel + ' × ' + qty"></p>

                                <div class="mt-4 flex gap-2">
                                    <button type="submit" @click="$refs.redirect.value = 'cart'" :disabled="!current"
                                            aria-label="Add to cart"
                                            class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-control border border-surface-3 bg-surface-2 text-ink-hi transition-colors hover:border-accent/60 disabled:opacity-40 disabled:cursor-not-allowed">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 6M17 13l1.4 6M9 21h.01M19 21h.01"/></svg>
                                    </button>

                                    <button type="submit" @click="$refs.redirect.value = 'checkout'" :disabled="!current"
                                            class="flex h-12 flex-1 items-center justify-center rounded-control bg-success text-body font-bold text-surface-0 transition-[filter] hover:brightness-110 disabled:opacity-40 disabled:cursor-not-allowed">
                                        Buy now
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- Outside the stock guard on purpose: when a product is out
                         of stock the buttons above disappear, and that is exactly
                         when a customer wants to ask when it will be back. The
                         component renders nothing at all when chat is switched
                         off, so it brings its own frame rather than leaving an
                         empty card behind. --}}
                    <x-product-chat-buttons
                        :name="$category->name"
                        :url="route('product', $category->slug)"
                        :slug="$category->slug" />

                    @if($relatedCategories->isNotEmpty())
                        <section class="rounded-card border border-surface-3 bg-surface-1" aria-labelledby="related-products-heading">
                            <h2 id="related-products-heading" class="border-b border-surface-3 px-4 py-3 text-body font-bold text-ink-hi">
                                More from {{ $category->mainCategory->name }}
                            </h2>
                            <ul class="p-2">
                                @foreach($relatedCategories as $related)
                                    <li>
                                        <a href="{{ route('product', $related->slug) }}" class="flex items-center gap-2.5 rounded-control px-2 py-2 transition-colors hover:bg-surface-2">
                                            <x-catalog.artwork :image="$related->image ?: $related->mainCategory?->image" :name="$related->name"
                                                               ratio="aspect-square" class="w-9 flex-shrink-0 rounded-chip" :width="36" :height="36" />
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-caption font-medium text-ink-hi">{{ $related->name }}</span>
                                                <span class="block text-meta text-ink-low">
                                                    @if($related->regionName()){{ $related->regionFlag() }} {{ $related->regionName() }}@endif
                                                    @if($related->min_price_bdt)<span class="text-success">from {{ format_bdt($related->min_price_bdt) }}</span>@endif
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if($referralSettings['enabled'])
                        <div class="rounded-card border border-surface-3 bg-surface-1 p-4">
                            <p class="text-caption font-semibold text-ink-hi">Earn with referrals</p>
                            <p class="mt-1 text-meta leading-relaxed text-ink-low">Share your code, your friend saves at checkout, and your wallet is credited.</p>
                            @if($referralCode)
                                <div x-data="{ copied: false }" class="mt-3 flex items-center gap-2">
                                    <span class="font-mono text-caption font-bold tracking-widest text-accent-hover">{{ $referralCode }}</span>
                                    <x-ui.button variant="secondary" size="sm"
                                                 x-on:click="navigator.clipboard.writeText(@js($referralCode)); copied = true; setTimeout(() => copied = false, 1500)">
                                        <span x-show="!copied">Copy</span><span x-show="copied" x-cloak>Copied</span>
                                    </x-ui.button>
                                </div>
                            @else
                                <x-ui.button :href="route('referral.dashboard')" variant="secondary" size="sm" class="mt-3">Sign in to earn</x-ui.button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile purchase bar. The panel above scrolls away on a phone, and this
         is where the store is mostly used. --}}
    @if($denominations->isNotEmpty())
        <div x-show="current" x-cloak
             class="fixed inset-x-0 bottom-0 z-40 border-t border-surface-3 bg-surface-1/97 px-4 py-3 backdrop-blur lg:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-meta text-ink-low" x-text="current?.name ?? ''"></p>
                    <p class="text-body font-bold tabular-nums text-success" x-text="totalLabel"></p>
                </div>
                <button type="button" @click="$refs.redirect.value = 'checkout'; $refs.form.requestSubmit()"
                        class="flex h-11 flex-shrink-0 items-center rounded-control bg-success px-6 text-body font-bold text-surface-0">
                    Buy now
                </button>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    // The chat buttons live in their own component and need to know what the
    // shopper picked, so the selection is published to a store rather than
    // passed down. One writer, any number of readers.
    Alpine.store('product', { current: null });

    Alpine.data('productPage', (cards, firstInStock) => ({
        cards,
        selected: firstInStock,
        qty: 1,

        init() {
            if (this.current) this.qty = this.current.min;
            this.publish();
        },

        get current() {
            return this.selected === null || this.selected === undefined ? null : this.cards[this.selected];
        },

        get total() {
            return this.current ? this.current.price * this.qty : 0;
        },

        // Formatted here rather than in the markup so the panel and the mobile
        // bar can never disagree about what the order costs.
        get totalLabel() {
            return '৳ ' + Math.round(this.total).toLocaleString('en-US');
        },

        get unitLabel() {
            return '৳ ' + Math.round(this.current?.price ?? 0).toLocaleString('en-US');
        },

        select(index) {
            const card = this.cards[index];
            if (!card || card.max < 1) return;
            this.selected = index;
            this.qty = card.min;
            this.publish();
        },

        // What a reader outside this component needs: which denomination, at
        // what price. Nothing else about the panel is anyone else's business.
        publish() {
            Alpine.store('product').current = this.current
                ? { denom: this.current.name, price: this.current.price }
                : null;
        },

        inc() { if (this.current && this.qty < this.current.max) this.qty++; },
        dec() { if (this.current && this.qty > this.current.min) this.qty--; },
    }));
});
</script>
@endpush

@endsection
