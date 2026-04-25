@extends('layouts.storefront')

@section('title', 'Buy ' . $card->name . ' in Bangladesh | Steam Gift Card BD — Steam Store BD')
@section('meta_description', 'Buy ' . $card->name . ' in Bangladesh with bKash. Price: ' . format_bdt($card->price_bdt) . '. Instant Steam gift card code delivery to email. 100% genuine — Steam Store BD.')
@section('meta_keywords', 'buy ' . strtolower($card->name) . ' bangladesh, ' . strtolower($card->name) . ' bkash, steam gift card bd, steam wallet code bangladesh, steam gift card sell bd')
@section('og_type', 'product')

@push('schema')
@php
$_cardSchema = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',                    'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $card->category->name,     'item' => route('product', $card->category->slug)],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $card->name,               'item' => route('card.detail', $card->slug)],
            ],
        ],
        [
            '@type'       => 'Product',
            'name'        => $card->name . ' — Bangladesh',
            'description' => 'Buy ' . $card->name . ' in Bangladesh. Pay with bKash — instant Steam gift card code delivery. 100% genuine Steam code.',
            'brand'       => ['@type' => 'Brand', 'name' => 'Steam'],
            'url'         => route('card.detail', $card->slug),
            'offers'      => [
                '@type'          => 'Offer',
                'priceCurrency'  => 'BDT',
                'price'          => (string) $card->price_bdt,
                'availability'   => $card->stock_count > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'seller'         => ['@type' => 'Organization', 'name' => 'Steam Store BD', 'url' => url('/')],
                'url'            => route('card.detail', $card->slug),
            ],
        ],
        [
            '@type'      => 'FAQPage',
            'mainEntity' => [
                ['@type' => 'Question', 'name' => 'When will I receive my ' . $card->name . ' code?',        'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Immediately after bKash payment confirmation — your Steam code appears on screen and is emailed within minutes.']],
                ['@type' => 'Question', 'name' => 'Is ' . $card->name . ' a genuine Steam gift card code?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes, all codes sold on Steam Store BD are 100% authentic Steam codes valid worldwide.']],
                ['@type' => 'Question', 'name' => 'Can I pay for ' . $card->name . ' with bKash in Bangladesh?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes! We use bKash Tokenized Checkout supporting all bKash accounts.']],
            ],
        ],
    ],
];
@endphp
<script type="application/ld+json">{!! json_encode($_cardSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-400 mb-8 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-brand-400">Home</a>
        <span>/</span>
        <a href="{{ route('product', $card->category->slug) }}" class="hover:text-brand-400">{{ $card->category->name }}</a>
        <span>/</span>
        <span class="text-white">{{ $card->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">

        {{-- Card Visual --}}
        <div>
            <div class="card-gradient rounded-3xl p-10 shadow-steam-glow border border-brand-500/30 relative overflow-hidden">
                <div class="absolute inset-0 opacity-20" style="background: radial-gradient(circle at 30% 50%, #2563EB 0%, transparent 60%);"></div>
                <div class="relative text-center">
                    <div class="text-8xl mb-4">🎮</div>
                    <div class="text-brand-400 font-bold text-3xl mb-2">STEAM</div>
                    <div class="text-white font-bold text-4xl">{{ format_card_denomination($card->denomination, $card->denomination_currency) }}</div>
                    <div class="text-gray-300 text-lg mt-1">{{ $card->denomination_currency }} Gift Card</div>
                </div>
            </div>
        </div>

        {{-- Card Info --}}
        <div x-data="{ quantity: 1, maxQty: {{ min(5, $card->stock_count) }} }">
            @if($card->badge_text)
            <span class="inline-block bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full mb-3">{{ $card->badge_text }}</span>
            @endif

            <h1 class="text-3xl font-bold text-white mb-2">{{ $card->name }}</h1>
            <p class="text-gray-400 mb-4">{{ $card->category->name }}</p>

            {{-- Price --}}
            <div class="mb-4">
                <span class="text-4xl font-extrabold text-brand-400">{{ format_bdt($card->price_bdt) }}</span>
                @if($card->price_bdt < $card->denomination_bdt)
                <span class="text-gray-500 text-lg line-through ml-3">{{ format_bdt($card->denomination_bdt) }}</span>
                @endif
            </div>

            {{-- Stock --}}
            <div class="flex items-center gap-2 mb-6">
                @if($card->stock_count > 0)
                    <span class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></span>
                    <span class="text-green-400 font-medium">In Stock</span>
                    <span class="text-gray-500 text-sm">({{ $card->stock_count }} available)</span>
                @else
                    <span class="w-3 h-3 rounded-full bg-red-400"></span>
                    <span class="text-red-400 font-medium">Out of Stock</span>
                @endif
            </div>

            @if($card->stock_count > 0)
            {{-- Quantity --}}
            <div class="mb-6">
                <label class="block text-sm text-gray-400 mb-2">Quantity</label>
                <div class="flex items-center gap-3">
                    <button @click="if(quantity > 1) quantity--"
                            class="w-10 h-10 rounded-lg bg-gray-800 border border-gray-600 text-white font-bold hover:border-brand-400 transition-colors">−</button>
                    <span x-text="quantity" class="text-white font-bold text-xl w-8 text-center"></span>
                    <button @click="if(quantity < maxQty) quantity++"
                            class="w-10 h-10 rounded-lg bg-gray-800 border border-gray-600 text-white font-bold hover:border-brand-400 transition-colors">+</button>
                    <span class="text-gray-400 text-sm ml-2">max {{ min(5, $card->stock_count) }}</span>
                </div>
            </div>

            {{-- Buy CTA --}}
            <form method="POST" action="{{ route('cart.add') }}" id="buyForm">
                @csrf
                <input type="hidden" name="gift_card_id" value="{{ $card->id }}">
                <input type="hidden" name="quantity" x-model="quantity">
                <button type="submit"
                        class="w-full btn-steam font-bold py-4 rounded-2xl text-lg transition-all duration-200 hover:shadow-steam-glow">
                    🛒 Add to Cart — <span x-text="'৳ ' + ({{ $card->price_bdt }} * quantity).toLocaleString()"></span>
                </button>
            </form>
            @else
            <div class="bg-gray-800 border border-red-500/30 rounded-2xl p-6 text-center">
                <div class="text-3xl mb-2">😔</div>
                <p class="text-red-400 font-semibold">This card is currently out of stock</p>
                <p class="text-gray-400 text-sm mt-1">Check back soon or browse other cards</p>
            </div>
            @endif

            {{-- Trust mini badges --}}
            <div class="grid grid-cols-3 gap-3 mt-6">
                <div class="bg-gray-900 rounded-xl p-3 text-center border border-gray-700">
                    <div class="text-lg">⚡</div>
                    <div class="text-xs text-gray-400 mt-1">Instant</div>
                </div>
                <div class="bg-gray-900 rounded-xl p-3 text-center border border-gray-700">
                    <div class="text-lg">🔒</div>
                    <div class="text-xs text-gray-400 mt-1">Secure</div>
                </div>
                <div class="bg-gray-900 rounded-xl p-3 text-center border border-gray-700">
                    <div class="text-lg">✅</div>
                    <div class="text-xs text-gray-400 mt-1">Genuine</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Description & FAQ accordions --}}
    <div x-data="{ open: null }" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
        <div>
            <h2 class="text-xl font-bold text-white mb-4">Description</h2>
            @if($card->description)
            <div class="bg-gray-900 rounded-2xl p-6 border border-gray-700/50 text-gray-300 text-sm leading-relaxed">
                {!! nl2br(e($card->description)) !!}
            </div>
            @endif

            {{-- How to Redeem --}}
            <div class="mt-6 bg-gray-900 rounded-2xl border border-gray-700/50 overflow-hidden">
                <button @click="open = open === 'redeem' ? null : 'redeem'"
                        class="w-full flex items-center justify-between p-5 text-left">
                    <span class="font-semibold text-white">How to Redeem</span>
                    <svg class="w-5 h-5 text-brand-400 transition-transform" :class="open === 'redeem' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open === 'redeem'" x-cloak class="px-5 pb-5 text-gray-400 text-sm space-y-2">
                    <p>1. Open Steam and sign into your account.</p>
                    <p>2. Click your Steam username in the top right.</p>
                    <p>3. Select "Account Details" → "Add Funds to Your Steam Wallet".</p>
                    <p>4. Click "Redeem a Steam Gift Card or Wallet Code".</p>
                    <p>5. Enter your code and click "Continue".</p>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-xl font-bold text-white mb-4">Frequently Asked Questions</h2>
            <div class="space-y-2">
                @foreach([
                    ['q' => 'When will I receive my code?', 'a' => 'Immediately after payment confirmation — it appears on screen and is emailed to you within minutes.'],
                    ['q' => 'Is this a genuine Steam code?', 'a' => 'Yes, all our codes are 100% authentic and sourced directly. They work worldwide on Steam.'],
                    ['q' => 'Can I use bKash Mobile Banking?', 'a' => 'Yes! We use bKash Tokenized Checkout which supports all bKash accounts including personal and agent.'],
                    ['q' => 'What if my code doesn\'t work?', 'a' => 'Contact our support immediately. We guarantee every code we sell.'],
                    ['q' => 'Can I buy multiple cards?', 'a' => 'Yes, you can purchase up to 5 of the same card in one order.'],
                ] as $idx => $faq)
                <div class="bg-gray-900 rounded-xl border border-gray-700/50 overflow-hidden">
                    <button @click="open = open === {{ $idx }} ? null : {{ $idx }}"
                            class="w-full flex items-center justify-between p-4 text-left">
                        <span class="text-sm font-medium text-gray-200">{{ $faq['q'] }}</span>
                        <svg class="w-4 h-4 text-brand-400 flex-shrink-0 ml-3 transition-transform" :class="open === {{ $idx }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open === {{ $idx }}" x-cloak class="px-4 pb-4 text-gray-400 text-sm">{{ $faq['a'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Related Cards --}}
    @if($relatedCards->count() > 0)
    <div>
        <h2 class="text-2xl font-bold text-white mb-6">Related Cards</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($relatedCards as $related)
            <a href="{{ route('card.detail', $related->slug) }}">
                <x-gift-card-card :card="$related" />
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Mobile bottom bar --}}
@if($card->stock_count > 0)
<div class="fixed bottom-0 left-0 right-0 z-40 md:hidden bg-gray-900/95 backdrop-blur-md border-t border-gray-700 p-4">
    <form method="POST" action="{{ route('cart.add') }}">
        @csrf
        <input type="hidden" name="gift_card_id" value="{{ $card->id }}">
        <input type="hidden" name="quantity" value="1">
        <button type="submit" class="w-full btn-steam font-bold py-4 rounded-xl text-lg">
            🛒 Buy Now — {{ format_bdt($card->price_bdt) }}
        </button>
    </form>
</div>
@endif
@endsection
