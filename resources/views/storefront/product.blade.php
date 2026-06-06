@extends('layouts.storefront')

@php
    $_brandName  = $category->mainCategory->name ?? null;
    $_titleBrand = $_brandName ? ' | ' . $_brandName . ' Bangladesh' : ' in Bangladesh';
    $_kwBase     = strtolower($category->name);
    $_kwBrand    = $_brandName ? strtolower($_brandName) . ' bangladesh, trusted ' . strtolower($_brandName) . ' bd, ' : '';
@endphp
@section('title', 'Buy ' . $category->name . $_titleBrand . ' | bKash Nagad — Steam Store BD')
@section('meta_description', 'Buy ' . $category->name . ' in Bangladesh with bKash or Nagad. Instant code delivery to email. Starting from ৳' . ($denominations->where('stock_count', '>', 0)->min('price_bdt') ? number_format($denominations->where('stock_count', '>', 0)->min('price_bdt')) : '') . ' BDT. 100% genuine codes. Fast &amp; secure.')
@section('meta_keywords', $_kwBrand . 'buy ' . $_kwBase . ' bangladesh, ' . $_kwBase . ' bkash, ' . $_kwBase . ' nagad, ' . $_kwBase . ' bd price, trusted gift card bd, genuine gift card bangladesh, digital gift card bd 2025')
@section('og_type', 'product')
@section('og_image_alt', 'Buy ' . $category->name . ' in Bangladesh — Steam Store BD')

@push('schema')
@php
    $inStockDenoms = $denominations->where('stock_count', '>', 0);
    $lowestPrice   = $inStockDenoms->min('price_bdt');
    $highestPrice  = $inStockDenoms->max('price_bdt');
    $totalStock    = $denominations->sum('stock_count');

    $_offers = [
        '@type'        => 'AggregateOffer',
        'priceCurrency'=> 'BDT',
        'offerCount'   => $denominations->count(),
        'availability' => $totalStock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'seller'       => ['@type' => 'Organization', 'name' => 'Steam Store BD'],
    ];
    if ($lowestPrice) {
        $_offers['lowPrice']  = (string) $lowestPrice;
        $_offers['highPrice'] = (string) $highestPrice;
    }

    $_productSchema = [
        '@type'       => 'Product',
        'name'        => $category->name . ' Bangladesh',
        'description' => 'Buy ' . $category->name . ' in Bangladesh with bKash or Nagad. Instant digital delivery to email. 100% genuine code.',
        'brand'       => ['@type' => 'Brand', 'name' => $category->mainCategory->name ?? $category->name],
        'seller'      => ['@type' => 'Organization', 'name' => 'Steam Store BD', 'url' => url('/')],
        'url'         => route('product', $category->slug),
        'offers'      => $_offers,
    ];
    if ($category->image) {
        $_productSchema['image'] = Storage::disk('public')->url($category->image);
    }

    $_breadcrumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')]];
    if ($category->mainCategory) {
        $_breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $category->mainCategory->name, 'item' => route('brand', $category->mainCategory->slug)];
    }
    $_breadcrumbs[] = ['@type' => 'ListItem', 'position' => count($_breadcrumbs) + 1, 'name' => $category->name, 'item' => route('product', $category->slug)];

    $_pageSchema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            ['@type' => 'BreadcrumbList', 'itemListElement' => $_breadcrumbs],
            $_productSchema,
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($_pageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div style="background:#F8FAFF; border-bottom:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-400">
            <a href="{{ route('home') }}" class="hover:text-brand-500 transition-colors">Home</a>
            @if($category->mainCategory)
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('brand', $category->mainCategory->slug) }}" class="hover:text-brand-500 transition-colors">{{ $category->mainCategory->name }}</a>
            @endif
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium" style="color:#0E1F35;">{{ $category->name }}</span>
        </nav>
    </div>
</div>

<div style="background:#F8FAFF; min-height:calc(100vh - 64px);">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-0">

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">

        {{-- Left: Product Visual --}}
        <div class="lg:col-span-2">
            <div class="rounded-3xl overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%); border:1px solid rgba(37,99,235,0.2); box-shadow:0 20px 60px rgba(7,20,40,0.18);">
                @if($category->image)
                <div class="relative w-full h-auto max-h-96 overflow-hidden flex items-center justify-center" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%);">
                    <img src="{{ Storage::disk('public')->url($category->image) }}" 
                         alt="{{ $category->name }}" 
                         class="w-full h-full object-contain" 
                         style="max-height:400px;">
                </div>
                @else
                <div class="relative h-52 flex items-center justify-center overflow-hidden">
                    <div class="absolute inset-0 opacity-20" style="background:radial-gradient(circle at 30% 50%,#2563EB 0%,transparent 65%);"></div>
                    <div class="relative text-center">
                        <div class="text-6xl mb-2">🎮</div>
                        <div class="text-white font-black text-xl tracking-[0.2em]">STEAM</div>
                        <div class="text-brand-400 text-sm font-medium mt-1">Gift Card</div>
                    </div>
                    <div class="absolute top-0 right-0 w-32 h-32 opacity-5" style="background:radial-gradient(circle,#fff 0%,transparent 70%);"></div>
                </div>
                @endif

            </div>

            {{-- Trust badges --}}
            <div class="mt-5 grid grid-cols-3 gap-3">
                @foreach([['⚡','Instant'],['🔒','Secure'],['✅','Genuine']] as [$icon,$label])
                <div class="text-center bg-white rounded-xl py-3 border border-gray-100" style="box-shadow:0 1px 4px rgba(7,20,40,0.06);">
                    <div class="text-lg">{{ $icon }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">{{ $label }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Right: Buy Panel --}}
        <div class="lg:col-span-3"
             x-data
             x-init="
                $store.product.denominations = @js($denominations->map(fn($d) => [
                    'id'    => $d->id,
                    'denom' => format_card_denomination($d->denomination, $d->denomination_currency),
                    'bdt'   => number_format($d->denomination_bdt, 0) . ' BDT',
                    'price' => $d->price_bdt,
                    'stock' => $d->stock_count,
                    'slug'  => $d->slug,
                ]));
                $store.product.selected = null;
                $store.product.qty = 1;
             ">

            <div class="bg-white rounded-3xl p-7" style="box-shadow:0 4px 24px rgba(7,20,40,0.08); border:1px solid #E8EEF8;">

                <h1 class="text-2xl font-bold mb-1" style="color:#071428;">{{ $category->name }}</h1>
                @if($category->description)
                <p class="text-gray-500 text-sm mb-6 leading-relaxed">{{ $category->description }}</p>
                @else
                <p class="text-gray-500 text-sm mb-6 leading-relaxed">Add funds to your Steam Wallet instantly. Valid worldwide on Steam platform.</p>
                @endif

                {{-- Select Amount --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-3" style="color:#071428;">Select Amount</label>

                    @if($denominations->isEmpty())
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                        <p class="text-red-500 text-sm font-medium">Currently out of stock. Check back soon.</p>
                    </div>
                    @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($denominations as $index => $denom)
                        <button
                            type="button"
                            @click="$store.product.selected = {{ $index }}; $store.product.qty = 1;"
                            :class="$store.product.selected === {{ $index }}
                                ? 'border-brand-500 bg-blue-50 ring-2 ring-brand-500/20'
                                : 'border-gray-200 bg-white hover:border-brand-400 hover:bg-blue-50/50'"
                            class="relative text-left p-3.5 rounded-2xl border-2 transition-all duration-150 cursor-pointer"
                            {{ $denom->stock_count === 0 ? 'disabled' : '' }}
                            style="{{ $denom->stock_count === 0 ? 'opacity:0.45;cursor:not-allowed;' : '' }}"
                        >
                            <div class="font-bold text-sm" style="color:#071428;">{{ format_card_denomination($denom->denomination, $denom->denomination_currency) }}</div>
                            <div class="text-brand-500 font-semibold text-sm mt-0.5">৳ {{ number_format($denom->price_bdt, 0) }}</div>
                            @if($denom->stock_count === 0)
                            <div class="text-xs text-red-400 mt-1">Out of stock</div>
                            @else
                            <div class="text-xs text-green-500 mt-1">In stock</div>
                            @endif
                            <div x-show="$store.product.selected === {{ $index }}" class="absolute top-2 right-2 w-4 h-4 rounded-full flex items-center justify-center" style="background:#2563EB;">
                                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Quantity + Stock --}}
                <div x-show="$store.product.selected !== null" x-cloak class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-sm font-semibold" style="color:#071428;">Quantity</label>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full" style="background:#F0FDF4; color:#16A34A; border:1px solid #BBF7D0;"
                              x-text="$store.product.current ? $store.product.current.stock + ' available' : ''"></span>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-0 rounded-2xl border-2 border-gray-200 overflow-hidden">
                            <button type="button"
                                @click="if ($store.product.qty > 1) $store.product.qty--"
                                :disabled="$store.product.qty <= 1"
                                class="w-11 h-11 flex items-center justify-center font-bold text-lg text-gray-600 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                                −
                            </button>
                            <span class="w-12 text-center font-bold text-base border-x border-gray-200 py-2.5" style="color:#071428;" x-text="$store.product.qty"></span>
                            <button type="button"
                                @click="if ($store.product.current && $store.product.qty < $store.product.current.stock) $store.product.qty++"
                                :disabled="!$store.product.current || $store.product.qty >= $store.product.current.stock"
                                class="w-11 h-11 flex items-center justify-center font-bold text-lg text-gray-600 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                                +
                            </button>
                        </div>
                        <span class="text-xs text-gray-400" x-show="$store.product.current && $store.product.qty >= $store.product.current.stock">
                            Max available
                        </span>
                    </div>
                </div>

                {{-- Price Summary --}}
                <div x-show="$store.product.selected !== null" x-cloak class="mb-6 rounded-2xl p-4" style="background:#F0F5FF; border:1px solid #DBEAFE;">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total to pay</div>
                            <div class="text-2xl font-black mt-0.5" style="color:#071428;"
                                 x-text="'৳ ' + Number(($store.product.current?.price ?? 0) * $store.product.qty).toLocaleString()"></div>
                            <div class="text-xs text-gray-400 mt-0.5" x-show="$store.product.qty > 1"
                                 x-text="'৳ ' + Number($store.product.current?.price ?? 0).toLocaleString() + ' × ' + $store.product.qty"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500 font-medium uppercase tracking-wider">Card value</div>
                            <div class="text-sm font-bold text-brand-500 mt-0.5" x-text="$store.product.current?.denom ?? ''"></div>
                        </div>
                    </div>
                </div>

                {{-- Delivery Method --}}
                <div class="mb-5 rounded-2xl overflow-hidden" style="border:1px solid #DBEAFE; box-shadow:0 2px 12px rgba(37,99,235,0.08);">
                    <div class="flex items-center gap-2 px-4 py-2.5" style="background:linear-gradient(135deg,#2563EB 0%,#1D4ED8 100%);">
                        <svg class="w-3.5 h-3.5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                        <span class="text-white text-xs font-bold uppercase tracking-widest">Instant Digital Delivery</span>
                    </div>
                    <div class="grid grid-cols-3 divide-x" style="background:#FAFCFF; divide-color:#DBEAFE;">
                        <div class="flex flex-col items-center gap-1.5 px-3 py-3.5 text-center">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                                 style="background:linear-gradient(135deg,#EEF4FF,#DBEAFE);">
                                <svg class="w-4 h-4" style="color:#2563EB;" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold leading-tight" style="color:#071428;">Email</p>
                            <p class="text-[10px] text-gray-400 leading-tight">Sent to your inbox</p>
                        </div>
                        <div class="flex flex-col items-center gap-1.5 px-3 py-3.5 text-center">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                                 style="background:linear-gradient(135deg,#EEF4FF,#DBEAFE);">
                                <svg class="w-4 h-4" style="color:#2563EB;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold leading-tight" style="color:#071428;">Order Page</p>
                            <p class="text-[10px] text-gray-400 leading-tight">Always in account</p>
                        </div>
                        <div class="flex flex-col items-center gap-1.5 px-3 py-3.5 text-center">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                                 style="background:linear-gradient(135deg,#F0FDF4,#DCFCE7);">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold leading-tight text-green-600">Instant</p>
                            <p class="text-[10px] text-gray-400 leading-tight">After payment</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                @if($denominations->isNotEmpty())
                <div class="mb-4"
                     x-data="{
                         submitForm(redirectTo) {
                             $refs.selectedCardId.value = $store.product.current?.id ?? '';
                             $refs.selectedQty.value = $store.product.qty;
                             $refs.redirectTo.value = redirectTo;
                             $refs.buyForm.submit();
                         }
                     }">
                    <form method="POST" action="{{ route('cart.add') }}" x-ref="buyForm">
                        @csrf
                        <input type="hidden" name="gift_card_id" x-ref="selectedCardId" value="">
                        <input type="hidden" name="quantity" x-ref="selectedQty" value="1">
                        <input type="hidden" name="redirect_to" x-ref="redirectTo" value="cart">

                        <div class="flex gap-3">
                            {{-- Add to Cart --}}
                            <button type="button"
                                :disabled="$store.product.selected === null"
                                @click="submitForm('cart')"
                                :class="$store.product.selected !== null
                                    ? 'opacity-100 cursor-pointer hover:bg-blue-50'
                                    : 'opacity-40 cursor-not-allowed'"
                                class="flex-1 py-4 rounded-2xl font-bold text-base transition-all duration-200 border-2 border-brand-500 text-brand-500">
                                🛒 Add to Cart
                            </button>

                            {{-- Buy Now --}}
                            <button type="button"
                                :disabled="$store.product.selected === null"
                                @click="submitForm('checkout')"
                                :class="$store.product.selected !== null
                                    ? 'opacity-100 cursor-pointer hover:opacity-90 hover:shadow-lg'
                                    : 'opacity-40 cursor-not-allowed'"
                                class="flex-1 py-4 rounded-2xl font-bold text-white text-base transition-all duration-200"
                                style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                                ⚡ Buy Now
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                @if($category->mainCategory && $category->mainCategory->how_to_redeem)
                <div class="mt-4 pt-4" style="border-top:1px solid #EEF2FF;">
                    <a href="{{ route('brand', $category->mainCategory->slug) }}#how-to-redeem"
                       class="w-full flex items-center justify-center gap-2 py-3 rounded-2xl font-semibold text-sm transition-colors"
                       style="background:#F0F5FF; border:1px solid #DBEAFE; color:#2563EB;"
                       onmouseover="this.style.background='#E0EAFF'" onmouseout="this.style.background='#F0F5FF'">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        How to Redeem {{ $category->mainCategory->name }}
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($category->long_description)
    <div class="max-w-5xl mx-auto px-0 pb-10 mt-10">
        <div class="bg-white rounded-2xl p-6 md:p-8" style="border:1px solid #E8EEF8; box-shadow:0 2px 16px rgba(7,20,40,0.05);">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h2 class="text-base font-black" style="color:#071428;">About {{ $category->name }}</h2>
            </div>
            <div class="rich-content">
                {!! $category->long_description !!}
            </div>
        </div>
    </div>
    @endif

</div>
</div>

@push('styles')
<style>
    body { background: #F8FAFF !important; }
    [x-cloak] { display: none !important; }
    /* Rich text content styles */
    .rich-content { font-size: 0.9rem; line-height: 1.75; color: #374151; }
    .rich-content h1,.rich-content h2,.rich-content h3,.rich-content h4 { font-weight: 800; color: #111827; margin: 1.2em 0 0.5em; line-height: 1.3; }
    .rich-content h2 { font-size: 1.2rem; }
    .rich-content h3 { font-size: 1.05rem; }
    .rich-content p  { margin: 0.75em 0; color: #4B5563; }
    .rich-content ul,.rich-content ol { margin: 0.75em 0; padding-left: 1.5em; color: #4B5563; }
    .rich-content ul { list-style-type: disc; }
    .rich-content ol { list-style-type: decimal; }
    .rich-content li { margin: 0.35em 0; }
    .rich-content strong,.rich-content b { font-weight: 700; color: #1F2937; }
    .rich-content em,.rich-content i { font-style: italic; }
    .rich-content a { color: #2563EB; text-decoration: underline; }
    .rich-content blockquote { border-left: 3px solid #2563EB; margin: 1em 0; padding: 0.5em 1em; background: #EEF4FF; border-radius: 0 8px 8px 0; }
    .rich-content table { width: 100%; border-collapse: collapse; margin: 1em 0; font-size: 0.85rem; }
    .rich-content th { background: #EEF4FF; color: #1E3A5F; font-weight: 700; padding: 8px 12px; border: 1px solid #DBEAFE; text-align: left; }
    .rich-content td { padding: 8px 12px; border: 1px solid #E5E7EB; color: #4B5563; }
    .rich-content tr:nth-child(even) td { background: #F9FAFB; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('product', {
            denominations: [],
            selected: null,
            qty: 1,
            get current() {
                return this.selected !== null ? this.denominations[this.selected] : null;
            }
        });
    });
</script>
@endpush

@endsection
