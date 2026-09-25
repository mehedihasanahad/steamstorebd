<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-HWW7WY3CHK"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-HWW7WY3CHK');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Steam Store BD — Buy Gift Cards in Bangladesh | bKash Nagad')</title>
    <meta name="description" content="@yield('meta_description', 'Steam Store BD — Bangladesh\'s trusted gift card store. Buy Steam, Google Play, App Store & more with bKash or Nagad. Instant digital delivery to email. 100% genuine codes at best BDT price.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="{{ config('storefront.theme_color') }}">

    {{-- Open Graph --}}
    <meta property="og:site_name" content="Steam Store BD">
    <meta property="og:title" content="@yield('title', 'Steam Store BD — Buy Gift Cards in Bangladesh')">
    <meta property="og:description" content="@yield('meta_description', 'Buy digital gift cards in Bangladesh with bKash or Nagad. Instant delivery. 100% genuine codes at best BDT price.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:image" content="@yield('og_image', asset('images/hero-image-banner.png'))">
    <meta property="og:image:alt" content="@yield('og_image_alt', 'Steam Store BD — Buy Gift Cards in Bangladesh with bKash')">
    <meta property="og:locale" content="en_US">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Steam Store BD — Buy Gift Cards in Bangladesh')">
    <meta name="twitter:description" content="@yield('meta_description', 'Buy digital gift cards in Bangladesh with bKash or Nagad. Instant delivery. Best BDT price.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/hero-image-banner.png'))">
    <meta name="twitter:image:alt" content="@yield('og_image_alt', 'Steam Store BD — Gift Cards Bangladesh')">

    {{-- JSON-LD: Organization + WebSite --}}
    @php
    $_facebookPage = site_setting('messenger_page_username') ?: site_setting('product_chat_messenger_username');
    $_organization = [
        '@type'           => 'Organization',
        '@id'             => url('/') . '/#organization',
        'name'            => 'Steam Store BD',
        'alternateName'   => ['Gift Card BD', 'Steam Gift Card BD'],
        'url'             => url('/'),
        'logo'            => ['@type' => 'ImageObject', 'url' => asset('images/icons/icon-512.png'), 'width' => 512, 'height' => 512],
        'description'     => "Bangladesh's trusted digital goods store: gift cards, game top-ups, game keys and subscriptions, paid for with local mobile wallets. Instant delivery, 100% genuine.",
        'areaServed'      => ['@type' => 'Country', 'name' => 'Bangladesh'],
        'contactPoint'    => array_filter([
            '@type'             => 'ContactPoint',
            'contactType'       => 'customer support',
            'email'             => site_setting('contact_email') ?: null,
            'telephone'         => site_setting('contact_whatsapp') ?: null,
            'availableLanguage' => ['English', 'Bengali'],
        ]),
        'hasMerchantReturnPolicy' => [
            '@type'                => 'MerchantReturnPolicy',
            'applicableCountry'    => 'BD',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
            'merchantReturnLink'   => route('refund-policy'),
        ],
    ];
    if ($_facebookPage) {
        $_organization['sameAs'] = ['https://www.facebook.com/' . $_facebookPage];
    }
    $_globalSchema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            $_organization,
            [
                '@type'         => 'WebSite',
                '@id'           => url('/') . '/#website',
                'url'           => url('/'),
                'name'          => 'Steam Store BD',
                'alternateName' => 'Gift Card BD',
                'description'   => 'Buy digital gift cards in Bangladesh with local mobile wallets. Instant code delivery. Best BDT price.',
                'publisher'     => ['@id' => url('/') . '/#organization'],
            ],
        ],
    ];
    @endphp
    <script type="application/ld+json">{!! json_encode($_globalSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    @stack('schema')

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    {{-- Resource hints: preconnect before any external requests ──────────── --}}
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Google Fonts ── kept as <link> (faster than @import in CSS) ── --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Compiled storefront CSS (Tailwind + design tokens) ── --}}
    @vite(['resources/css/storefront.css'])
    @stack('styles')
</head>
<body class="bg-surface-0 text-ink-mid font-sans antialiased">

<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded-control focus:bg-accent focus:px-4 focus:py-2 focus:text-body focus:text-white">
    Skip to content
</a>

@php
    $cartCount = collect(session('cart', []))->sum('quantity');
    $catalogMenu = $catalogMenu ?? [];
@endphp

{{-- ── Announcement bar ── --}}
@php $annActive = site_setting('announcement_bar_active','0'); $annText = site_setting('announcement_bar_text',''); @endphp
@if($annActive && $annText)
<div x-data="{ show: !sessionStorage.getItem('ann_dismissed') }" x-show="show" x-cloak
     class="relative bg-accent px-4 py-2 text-center text-caption font-medium text-white">
    <span>{{ $annText }}</span>
    <button @click="show=false; sessionStorage.setItem('ann_dismissed','1')" aria-label="Dismiss announcement"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-lede leading-none text-white/70 hover:text-white">&times;</button>
</div>
@endif

{{-- ── Header ── --}}
<header x-data="{ mobileOpen: false, mobileSearch: false }"
        class="sticky top-0 z-50 border-b border-surface-3 bg-surface-1/95 backdrop-blur">

    <div class="mx-auto flex h-[60px] max-w-shell items-center gap-3 px-4 sm:px-6 lg:px-8">

        {{-- Hamburger (mobile) --}}
        <button @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen ? 'true' : 'false'" aria-label="Open menu"
                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-control text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi lg:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex flex-shrink-0 items-center gap-2">
            <img src="{{ asset('images/logo.svg') }}" alt="" width="32" height="32" class="h-8 w-8 rounded-control" aria-hidden="true">
            <span class="hidden leading-tight sm:block">
                <span class="block text-body font-extrabold tracking-tight text-ink-hi">Steam Store <span class="text-accent-hover">BD</span></span>
                <span class="block text-meta font-medium text-ink-low">Digital goods</span>
            </span>
        </a>

        {{-- Search: the widest thing in the bar on desktop --}}
        <div class="mx-auto hidden w-full max-w-xl md:block">
            <x-ui.search-box id="header-search" />
        </div>

        {{-- Right-hand actions --}}
        <div class="ml-auto flex flex-shrink-0 items-center gap-1 md:ml-0">

            <button @click="mobileSearch = !mobileSearch" aria-label="Search"
                    class="flex h-11 w-11 items-center justify-center rounded-control text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi md:hidden">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            </button>

            @auth
                <a href="{{ route('favourites') }}" aria-label="Favourites"
                   class="hidden h-11 w-11 items-center justify-center rounded-control text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi sm:flex">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </a>
            @endauth

            <a href="{{ route('cart') }}" aria-label="Cart{{ $cartCount > 0 ? ' — ' . $cartCount . ' item(s)' : '' }}"
               class="relative flex h-11 w-11 items-center justify-center rounded-control text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 6M17 13l1.4 6M9 21h.01M19 21h.01"/></svg>
                @if($cartCount > 0)
                    <span class="absolute right-1 top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-accent px-1 text-[10px] font-bold leading-none text-white">{{ $cartCount }}</span>
                @endif
            </a>

            @auth
                <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                    <button @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                            class="flex min-h-[44px] items-center gap-2 rounded-control px-2 transition-colors hover:bg-surface-2">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-control bg-accent text-caption font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden max-w-[110px] truncate text-caption font-medium text-ink-mid lg:block">{{ auth()->user()->name }}</span>
                        <svg class="hidden h-3 w-3 text-ink-low transition-transform lg:block" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute right-0 z-50 mt-2 w-56 rounded-card border border-surface-3 bg-surface-1 py-1 shadow-hover">

                        <div class="border-b border-surface-3 px-4 py-3">
                            <p class="truncate text-caption font-semibold text-ink-hi">{{ auth()->user()->name }}</p>
                            <p class="mt-0.5 truncate text-meta text-ink-low">{{ auth()->user()->email }}</p>
                            @if(site_setting('referral_enabled', false))
                                <p class="mt-2 text-meta font-bold text-success">Wallet: {{ format_bdt(auth()->user()->wallet_balance ?? 0) }}</p>
                            @endif
                        </div>

                        <a href="{{ route('orders.lookup') }}" class="block px-4 py-2.5 text-caption text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi">My Orders</a>
                        <a href="{{ route('favourites') }}" class="block px-4 py-2.5 text-caption text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi">Favourites</a>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-caption text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi">Profile</a>
                        @if(site_setting('referral_enabled', false))
                            <a href="{{ route('referral.dashboard') }}" class="block px-4 py-2.5 text-caption text-ink-mid transition-colors hover:bg-surface-2 hover:text-ink-hi">Referral &amp; Wallet</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="border-t border-surface-3 pt-1">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2.5 text-left text-caption text-ink-low transition-colors hover:bg-surface-2 hover:text-danger">Logout</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="hidden min-h-[44px] items-center px-3 text-caption font-medium text-ink-mid transition-colors hover:text-ink-hi sm:flex">Sign in</a>
                <x-ui.button :href="route('register')" size="sm" class="hidden sm:inline-flex">Sign up</x-ui.button>
            @endauth
        </div>
    </div>

    {{-- Mobile search: a full-width row rather than a modal, so the keyboard
         does not fight a floating panel on a small screen. --}}
    <div x-show="mobileSearch" x-cloak class="border-t border-surface-3 px-4 py-3 md:hidden">
        <x-ui.search-box id="mobile-search" />
    </div>

    {{-- Desktop catalog nav + mega-panel --}}
    <x-catalog.mega-menu :menu="$catalogMenu" />

    {{-- Mobile drawer: the same tree as an accordion --}}
    <div x-show="mobileOpen" x-cloak class="max-h-[75vh] overflow-y-auto border-t border-surface-3 bg-surface-1 lg:hidden">
        <nav class="px-4 py-3" aria-label="Catalog">
            @foreach($catalogMenu as $section)
                <div x-data="{ expanded: false }" class="border-b border-surface-3 last:border-b-0">
                    <div class="flex items-center">
                        <a href="{{ $section['url'] }}" class="flex-1 py-3 text-caption font-semibold uppercase tracking-wide text-ink-hi">{{ $section['name'] }}</a>
                        @if(! empty($section['brands']))
                            <button @click="expanded = !expanded" :aria-expanded="expanded ? 'true' : 'false'"
                                    aria-label="Show {{ $section['name'] }} brands"
                                    class="flex h-11 w-11 items-center justify-center text-ink-low">
                                <svg class="h-4 w-4 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        @endif
                    </div>

                    <ul x-show="expanded" x-cloak class="pb-2">
                        @foreach($section['brands'] as $brand)
                            <li><a href="{{ $brand['url'] }}" class="block py-2 pl-3 text-caption text-ink-mid transition-colors hover:text-ink-hi">{{ $brand['name'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="mt-3 grid grid-cols-2 gap-2 border-t border-surface-3 pt-3">
                <a href="{{ route('faq') }}" class="rounded-control bg-surface-2 py-2.5 text-center text-caption font-medium text-ink-mid">FAQ</a>
                <a href="{{ route('contact') }}" class="rounded-control bg-surface-2 py-2.5 text-center text-caption font-medium text-ink-mid">Contact</a>
            </div>

            @guest
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <x-ui.button :href="route('login')" variant="secondary" size="sm">Sign in</x-ui.button>
                    <x-ui.button :href="route('register')" size="sm">Sign up</x-ui.button>
                </div>
            @endguest
        </nav>
    </div>
</header>

{{-- ── Flash messages ── --}}
@if(session('success') || session('error'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-cloak
     role="status"
     class="fixed right-4 top-20 z-[60] flex max-w-xs items-center gap-3 rounded-card border px-4 py-3 shadow-hover
            {{ session('success') ? 'border-success/40 bg-surface-1 text-success' : 'border-danger/40 bg-surface-1 text-danger' }}">
    <span class="text-caption font-medium">{{ session('success') ?: session('error') }}</span>
    <button @click="show = false" class="ml-auto text-lede leading-none text-ink-low hover:text-ink-hi" aria-label="Dismiss">&times;</button>
</div>
@endif

{{-- ── Page content ── --}}
<main id="main">
    @yield('content')
</main>

{{-- ── Footer ── --}}
<footer class="border-t border-surface-3 bg-surface-1">
    <div class="mx-auto max-w-shell px-4 py-section sm:px-6 lg:px-8 md:py-section-lg">
        <div class="grid grid-cols-2 gap-8 lg:grid-cols-5">

            <div class="col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="mb-3 flex items-center gap-2">
                    <img src="{{ asset('images/logo.svg') }}" alt="" width="32" height="32" class="h-8 w-8 rounded-control" aria-hidden="true">
                    <span class="text-body font-extrabold text-ink-hi">Steam Store <span class="text-accent-hover">BD</span></span>
                </a>
                <p class="text-caption leading-relaxed text-ink-low">Digital gift cards, game top-ups, keys and subscriptions in Bangladesh. Paid with bKash and Nagad, delivered to your inbox.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.badge tone="accent">Instant delivery</x-ui.badge>
                    <x-ui.badge tone="success">Secure payment</x-ui.badge>
                </div>
            </div>

            @if(($footerBrands ?? collect())->isNotEmpty())
            <div>
                <h2 class="mb-3 text-meta font-bold uppercase tracking-widest text-ink-hi">Brands</h2>
                <ul class="space-y-2">
                    @foreach($footerBrands->take(8) as $footerBrand)
                        <li><a href="{{ route('brand', $footerBrand->slug) }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">{{ $footerBrand->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div>
                <h2 class="mb-3 text-meta font-bold uppercase tracking-widest text-ink-hi">Help</h2>
                <ul class="space-y-2">
                    <li><a href="{{ route('faq') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">FAQ</a></li>
                    <li><a href="{{ route('how-to-redeem') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">How to Redeem</a></li>
                    <li><a href="{{ route('orders.lookup') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">Track Your Order</a></li>
                    <li><a href="{{ route('contact') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">Contact Us</a></li>
                    @if(site_setting('exclusive_offers_enabled', true))
                        <li><a href="{{ route('offers') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">Today's Offers</a></li>
                    @endif
                    @if(site_setting('reseller_program_enabled', false))
                        <li><a href="{{ route('reseller') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">Become a Reseller</a></li>
                    @endif
                </ul>
            </div>

            <div>
                <h2 class="mb-3 text-meta font-bold uppercase tracking-widest text-ink-hi">Company</h2>
                <ul class="space-y-2">
                    <li><a href="{{ route('about') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">About Us</a></li>
                    <li><a href="{{ route('refund-policy') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">Refund Policy</a></li>
                    <li><a href="{{ route('privacy-policy') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-caption text-ink-low transition-colors hover:text-accent-hover">Terms of Service</a></li>
                </ul>
            </div>

            <div>
                <h2 class="mb-3 text-meta font-bold uppercase tracking-widest text-ink-hi">Get in touch</h2>
                <ul class="space-y-2 text-caption text-ink-low">
                    @if(site_setting('contact_email'))
                        <li><a href="mailto:{{ site_setting('contact_email') }}" class="transition-colors hover:text-accent-hover">{{ site_setting('contact_email') }}</a></li>
                    @endif
                    @if(site_setting('contact_whatsapp'))
                        <li>{{ site_setting('contact_whatsapp') }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-col items-center justify-between gap-3 border-t border-surface-3 pt-6 md:flex-row">
            <p class="text-meta leading-relaxed text-ink-low">Steam Store BD is an independent reseller. Steam and the Steam logo are trademarks of Valve Corporation. Google Play is a trademark of Google LLC. App Store is a trademark of Apple Inc. All brand names are property of their respective owners.</p>
            <p class="flex-shrink-0 text-meta text-ink-low">&copy; {{ date('Y') }} Steam Store BD.</p>
        </div>
    </div>
</footer>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.11/dist/cdn.min.js"></script>
@stack('scripts')

{{-- ── Floating chat buttons ── --}}
@php
    $waOn   = (bool) site_setting('whatsapp_chat_enabled', false);
    $waNum  = preg_replace('/[^0-9]/', '', site_setting('whatsapp_chat_number', ''));
    $waMsg  = site_setting('whatsapp_chat_message', 'Hello! I want to buy a gift card.');
    $msOn   = (bool) site_setting('messenger_chat_enabled', false);
    $msUser = site_setting('messenger_page_username', '');
@endphp

@if($waOn && $waNum || $msOn && $msUser)
<div class="chat-float-dock fixed bottom-5 right-4 z-[70] flex flex-col items-end gap-3">
    @if($msOn && $msUser)
    <div x-data="{ tip: false }" class="relative">
        <div x-show="tip" x-cloak class="chat-tip">Chat on Messenger</div>
        <a href="https://m.me/{{ $msUser }}" target="_blank" rel="noopener noreferrer"
           @mouseenter="tip=true" @mouseleave="tip=false"
           class="chat-float-btn ms-float" aria-label="Chat on Messenger">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="white" aria-hidden="true">
                <path d="M12 0C5.373 0 0 4.975 0 11.111c0 3.497 1.745 6.616 4.472 8.652V24l4.086-2.242c1.09.301 2.246.465 3.442.465 6.627 0 12-4.975 12-11.112S18.627 0 12 0zm1.194 14.963l-3.055-3.26-5.963 3.26L10.426 8.4l3.129 3.26 5.889-3.26-6.25 6.563z"/>
            </svg>
        </a>
    </div>
    @endif

    @if($waOn && $waNum)
    <div x-data="{ tip: false }" class="relative">
        <div x-show="tip" x-cloak class="chat-tip">Chat on WhatsApp</div>
        <a href="https://wa.me/{{ $waNum }}?text={{ rawurlencode($waMsg) }}" target="_blank" rel="noopener noreferrer"
           @mouseenter="tip=true" @mouseleave="tip=false"
           class="chat-float-btn wa-float" aria-label="Chat on WhatsApp">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="white" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </a>
    </div>
    @endif
</div>
@endif
</body>
</html>
