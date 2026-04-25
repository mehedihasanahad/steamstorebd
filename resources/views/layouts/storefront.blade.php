<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Steam Store BD — Buy Steam Gift Cards in Bangladesh with bKash')</title>
    <meta name="description" content="@yield('meta_description', 'Buy Steam Wallet gift cards in Bangladesh with bKash. $5, $10, $20, $50, $100 USD denominations. Instant code delivery. 100% genuine Steam codes.')">
    <meta name="keywords" content="@yield('meta_keywords', 'steam gift card bangladesh, steam wallet bd, buy steam gift card bd, steam gift card bkash, steam wallet code bangladesh, steam card bd price')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:site_name" content="Steam Store BD">
    <meta property="og:title" content="@yield('title', 'Steam Store BD — Buy Steam Gift Cards in Bangladesh')">
    <meta property="og:description" content="@yield('meta_description', 'Buy Steam Wallet gift cards in Bangladesh with bKash. Instant code delivery. 100% genuine.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-steam-bd.png'))">
    <meta property="og:locale" content="en_US">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Steam Store BD — Buy Steam Gift Cards in Bangladesh')">
    <meta name="twitter:description" content="@yield('meta_description', 'Buy Steam Wallet gift cards in Bangladesh with bKash. Instant delivery.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-steam-bd.png'))">

    {{-- JSON-LD: Organization + WebSite --}}
    @php
    $_globalSchema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'Organization',
                '@id'         => url('/') . '/#organization',
                'name'        => 'Steam Store BD',
                'url'         => url('/'),
                'logo'        => ['@type' => 'ImageObject', 'url' => asset('images/og-steam-bd.png')],
                'description' => "Bangladesh's trusted marketplace for Steam Wallet gift cards. Instant delivery with bKash payment.",
                'areaServed'  => ['@type' => 'Country', 'name' => 'Bangladesh'],
                'contactPoint'=> ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'availableLanguage' => ['English', 'Bengali']],
            ],
            [
                '@type'       => 'WebSite',
                '@id'         => url('/') . '/#website',
                'url'         => url('/'),
                'name'        => 'Steam Store BD',
                'description' => 'Buy Steam gift cards in Bangladesh with bKash. Instant delivery.',
                'publisher'   => ['@id' => url('/') . '/#organization'],
            ],
        ],
    ];
    @endphp
    <script type="application/ld+json">{!! json_encode($_globalSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    @stack('schema')

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='url(%23g)'/><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%232563EB'/><stop offset='1' stop-color='%231D4ED8'/></linearGradient></defs><text x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='36'>🎮</text></svg>">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='url(%23g)'/><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%232563EB'/><stop offset='1' stop-color='%231D4ED8'/></linearGradient></defs><text x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-size='36'>🎮</text></svg>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                    colors: {
                        /* ── Brand blue palette ── */
                        brand: {
                            50:  '#EEF4FF',
                            100: '#D9E8FF',
                            200: '#B3CFFF',
                            300: '#7AAFF5',
                            400: '#4B8FEF',
                            500: '#2563EB',   /* primary CTA */
                            600: '#1D4ED8',
                            700: '#1E40AF',
                            800: '#1E3A8A',
                            900: '#1E3074',
                        },
                        /* ── Navy surface palette (overrides gray to blue-tinted) ── */
                        gray: {
                            50:  '#EEF4FF',
                            100: '#D4E0F5',
                            200: '#9BB5D5',
                            300: '#7898BB',
                            400: '#557AA0',
                            500: '#3A5E80',
                            600: '#214263',
                            700: '#152E4F',
                            800: '#0E1F35',
                            900: '#071428',
                            950: '#040D1A',
                        },
                        'bkash-pink': '#E2136E',
                    },
                    boxShadow: {
                        'brand-glow': '0 0 24px rgba(37,99,235,0.45)',
                        'brand-glow-lg': '0 0 48px rgba(37,99,235,0.35)',
                        'card': '0 4px 24px rgba(0,0,0,0.4)',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-12px)' },
                        },
                        'pulse-slow': {
                            '0%, 100%': { opacity: '0.6' },
                            '50%': { opacity: '1' },
                        }
                    },
                    animation: {
                        float: 'float 3s ease-in-out infinite',
                        'pulse-slow': 'pulse-slow 3s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --brand-blue: #2563EB;
            --brand-blue-dark: #1D4ED8;
            --brand-navy: #071428;
            --brand-body: #040D1A;
        }
        body { background-color: var(--brand-body); font-family: 'Inter', sans-serif; }

        /* ── Gradient helpers ── */
        .card-gradient   { background: linear-gradient(135deg, #071428 0%, #0D2040 100%); }
        .hero-gradient   { background: linear-gradient(135deg, #071428 0%, #040D1A 80%); }
        .surface         { background: #071428; }
        .surface-2       { background: #0E1F35; }

        /* ── Brand button ── */
        .btn-brand {
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            color: #fff !important;
            transition: all 0.2s ease;
        }
        .btn-brand:hover {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            box-shadow: 0 0 24px rgba(37,99,235,0.5);
        }
        /* keep old alias for backwards compat */
        .btn-steam { background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); color: #fff !important; }
        .btn-steam:hover { background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%); box-shadow: 0 0 24px rgba(37,99,235,0.5); }

        /* ── Shadow alias ── */
        .shadow-steam-glow { box-shadow: 0 0 24px rgba(37,99,235,0.45); }

        /* ── Scrollbar ── */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

        [x-cloak] { display: none !important; }

        /* ── Decorative grid ── */
        .grid-bg {
            background-image: linear-gradient(rgba(37,99,235,0.04) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(37,99,235,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        /* ── Animated orb ── */
        .orb {
            border-radius: 50%;
            filter: blur(80px);
            position: absolute;
            pointer-events: none;
        }
    </style>
    @stack('styles')
</head>
<body class="text-gray-900 font-sans antialiased" style="background:#FFFFFF;">

    {{-- ── Announcement Bar ── --}}
    @php $annActive = site_setting('announcement_bar_active','0'); $annText = site_setting('announcement_bar_text',''); @endphp
    @if($annActive && $annText)
    <div x-data="{ show: !sessionStorage.getItem('ann_dismissed') }" x-show="show" x-cloak
         class="bg-brand-500 text-white text-center py-2.5 px-4 text-sm font-medium relative">
        <span>{{ $annText }}</span>
        <button @click="show=false; sessionStorage.setItem('ann_dismissed','1')"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white font-bold text-xl leading-none">×</button>
    </div>
    @endif

    {{-- ── Navbar ── --}}
    <nav x-data="{ mobileOpen: false }"
         class="sticky top-0 z-50 border-b border-gray-800/80 shadow-xl"
         style="background: rgba(7,20,40,0.96); backdrop-filter: blur(16px);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg flex-shrink-0 shadow-brand-glow" style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">🎮</div>
                    <div class="leading-tight">
                        <div class="font-extrabold text-base text-white tracking-tight leading-none">Steam Store <span class="text-brand-400">BD</span></div>
                        <div class="text-[10px] text-gray-500 font-medium leading-none mt-0.5">Gift Cards</div>
                    </div>
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-7">
                    <a href="{{ route('home') }}"    class="text-gray-300 hover:text-brand-400 transition-colors duration-150 text-sm font-medium">Home</a>
                    <a href="{{ route('faq') }}"     class="text-gray-300 hover:text-brand-400 transition-colors duration-150 text-sm font-medium">FAQ</a>
                    <a href="{{ route('contact') }}" class="text-gray-300 hover:text-brand-400 transition-colors duration-150 text-sm font-medium">Contact</a>
                </div>

                {{-- Right Actions --}}
                @php $cartCount = collect(session('cart', []))->sum('quantity'); @endphp
                <div class="hidden md:flex items-center gap-3">
                    @auth
                        {{-- User dropdown --}}
                        <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl transition-all duration-150 group"
                                    style="border:1px solid rgba(37,99,235,0.2); background:rgba(37,99,235,0.06);"
                                    :style="open ? 'border-color:rgba(37,99,235,0.45); background:rgba(37,99,235,0.12);' : ''">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-black text-white flex-shrink-0"
                                     style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <span class="text-sm font-medium text-gray-300 group-hover:text-white max-w-[100px] truncate">
                                    {{ auth()->user()->name }}
                                </span>
                                <svg class="w-3.5 h-3.5 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" x-cloak @click.outside="open = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-52 rounded-2xl py-1.5 z-50"
                                 style="background:#0E1F35; border:1px solid rgba(37,99,235,0.2); box-shadow:0 16px 40px rgba(0,0,0,0.5);">

                                {{-- User info header --}}
                                <div class="px-4 py-3 border-b" style="border-color:rgba(37,99,235,0.12);">
                                    <p class="text-white text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-gray-400 text-xs truncate mt-0.5">{{ auth()->user()->email }}</p>
                                </div>

                                <div class="py-1.5">
                                    <a href="{{ route('orders.lookup') }}"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                                        <svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        My Orders
                                    </a>
                                    <a href="{{ route('profile.edit') }}"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                                        <svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Profile
                                    </a>
                                </div>

                                <div class="border-t pt-1.5" style="border-color:rgba(37,99,235,0.12);">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-400 hover:text-red-400 hover:bg-red-500/5 transition-colors">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Logout
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-sm font-medium text-gray-300 hover:text-white border border-gray-700 hover:border-brand-500/50 px-4 py-1.5 rounded-lg transition-all duration-150">
                            Sign In
                        </a>
                    @endauth
                    <a href="{{ route('cart') }}"
                       class="btn-brand text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-1.5 relative">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 6M17 13l1.4 6M9 21h.01M19 21h.01"/></svg>
                        Cart
                        @if($cartCount > 0)
                        <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center leading-none">{{ $cartCount }}</span>
                        @endif
                    </a>
                </div>

                {{-- Hamburger --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden text-gray-400 hover:text-white p-2 rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileOpen" x-cloak class="md:hidden border-t border-gray-800" style="background: #071428;">
            <div class="px-4 py-4 space-y-1">
                <a href="{{ route('home') }}"    class="flex items-center gap-2 text-gray-300 hover:text-brand-400 hover:bg-gray-800 px-3 py-2.5 rounded-lg transition-colors text-sm">Home</a>
                <a href="{{ route('faq') }}"     class="flex items-center gap-2 text-gray-300 hover:text-brand-400 hover:bg-gray-800 px-3 py-2.5 rounded-lg transition-colors text-sm">FAQ</a>
                <a href="{{ route('contact') }}" class="flex items-center gap-2 text-gray-300 hover:text-brand-400 hover:bg-gray-800 px-3 py-2.5 rounded-lg transition-colors text-sm">Contact</a>
                @auth
                {{-- Mobile user card --}}
                <div class="rounded-xl p-3 mb-1" style="background:rgba(37,99,235,0.06); border:1px solid rgba(37,99,235,0.15);">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black text-white flex-shrink-0"
                             style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-white text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                            <p class="text-gray-400 text-xs truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('orders.lookup') }}" class="flex items-center gap-2.5 text-gray-300 hover:text-brand-400 hover:bg-white/5 px-2.5 py-2 rounded-lg transition-colors text-sm">
                            <svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            My Orders
                        </a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 text-gray-300 hover:text-brand-400 hover:bg-white/5 px-2.5 py-2 rounded-lg transition-colors text-sm">
                            <svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 text-gray-400 hover:text-red-400 hover:bg-red-500/5 px-2.5 py-2 rounded-lg transition-colors text-sm">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
                @endauth
                <div class="pt-2 border-t border-gray-800 mt-2">
                    <a href="{{ route('cart') }}" class="block btn-brand text-center font-semibold py-2.5 px-4 rounded-xl text-sm">
                        🛒 Cart @if($cartCount > 0) ({{ $cartCount }})@endif
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- ── Flash Toasts ── --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed top-20 right-4 z-50 bg-green-600 border border-green-500/30 text-white px-5 py-3 rounded-2xl shadow-xl flex items-center gap-3 max-w-xs">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
        <button @click="show=false" class="ml-auto text-white/70 hover:text-white text-xl leading-none">×</button>
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
         class="fixed top-20 right-4 z-50 bg-red-600 border border-red-500/30 text-white px-5 py-3 rounded-2xl shadow-xl flex items-center gap-3 max-w-xs">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span class="text-sm font-medium">{{ session('error') }}</span>
        <button @click="show=false" class="ml-auto text-white/70 hover:text-white text-xl leading-none">×</button>
    </div>
    @endif

    {{-- ── Page Content ── --}}
    <main>
        @yield('content')
    </main>

    {{-- ── Footer ── --}}
    <footer style="background: #071428; border-top: 1px solid rgba(37,99,235,0.12);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div>
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 mb-4">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg flex-shrink-0" style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">🎮</div>
                        <div class="leading-tight">
                            <div class="font-extrabold text-base text-white leading-none">Steam Store <span class="text-brand-400">BD</span></div>
                            <div class="text-[10px] text-gray-500 font-medium leading-none mt-0.5">Gift Cards</div>
                        </div>
                    </a>
                    <p class="text-gray-400 text-sm leading-relaxed">Your trusted source for Steam gift cards in Bangladesh. Fast delivery, secure bKash payment.</p>
                    <div class="flex gap-3 mt-5">
                        <span class="inline-flex items-center gap-1.5 text-xs text-brand-400 bg-brand-500/10 border border-brand-500/20 px-3 py-1 rounded-full">⚡ Instant</span>
                        <span class="inline-flex items-center gap-1.5 text-xs text-green-400 bg-green-500/10 border border-green-500/20 px-3 py-1 rounded-full">🔒 Secure</span>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Quick Links</h4>
                    <ul class="space-y-2.5">
                        <li><a href="{{ route('home') }}"          class="text-gray-400 hover:text-brand-400 text-sm transition-colors flex items-center gap-1.5">→ Home</a></li>
                        <li><a href="{{ route('faq') }}"           class="text-gray-400 hover:text-brand-400 text-sm transition-colors flex items-center gap-1.5">→ FAQ</a></li>
                        <li><a href="{{ route('contact') }}"       class="text-gray-400 hover:text-brand-400 text-sm transition-colors flex items-center gap-1.5">→ Contact</a></li>
                        <li><a href="{{ route('orders.lookup') }}" class="text-gray-400 hover:text-brand-400 text-sm transition-colors flex items-center gap-1.5">→ Track Order</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Get in Touch</h4>
                    <ul class="space-y-3 text-sm text-gray-400">
                        @if(site_setting('contact_email'))
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ site_setting('contact_email') }}
                        </li>
                        @endif
                        @if(site_setting('contact_whatsapp'))
                        <li>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', site_setting('contact_whatsapp')) }}"
                               class="flex items-center gap-2 hover:text-brand-400 transition-colors">
                                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WhatsApp Support
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-gray-800/60 flex flex-col md:flex-row items-center justify-between gap-3">
                <p class="text-gray-500 text-xs">Steam Store BD is not affiliated with Valve Corporation. Steam and the Steam logo are trademarks of Valve Corporation.</p>
                <p class="text-gray-600 text-xs">© {{ date('Y') }} Steam Store BD.</p>
            </div>
        </div>
    </footer>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
