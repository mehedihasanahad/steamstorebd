@extends('layouts.storefront')

@section('title', 'Buy Gift Cards in Bangladesh | bKash Payment — Steam Store BD')
@section('meta_description', 'Steam Store BD — Bangladesh\'s #1 gift card store. Buy Steam, Google Play, App Store & more with bKash. Instant digital delivery. 100% genuine codes at best BDT price.')
@section('meta_keywords', 'gift card bangladesh, buy gift card bd, steam gift card bd, google play gift card bangladesh, gift card bkash, digital gift card bd, steam store bd, gift card buy bangladesh')

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
<script type="application/ld+json">
{!! json_encode($_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@push('styles')
<style>
/* ── Hero ── */
.hero-section { min-height: 420px; }
@media (min-width: 640px)  { .hero-section { min-height: 520px; } }
@media (min-width: 1024px) { .hero-section { min-height: 740px; } }

/* ── Brand card hover ── */
@keyframes borderPulse {
    0%, 100% { opacity: 0.5; }
    50%       { opacity: 1; }
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.brand-card {
    position: relative;
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease;
}
.brand-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 28px 60px rgba(37,99,235,0.22), 0 6px 20px rgba(0,0,0,0.10) !important;
}
.brand-card:hover .brand-card-glow {
    opacity: 1;
    animation: borderPulse 2.5s ease-in-out infinite;
}
.brand-card-glow {
    position: absolute;
    inset: -1.5px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(37,99,235,0.55), rgba(96,165,250,0.35), rgba(37,99,235,0.55));
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    z-index: 0;
}
.brand-card > * { position: relative; z-index: 1; }
.brand-arrow {
    opacity: 0;
    transform: translateX(-5px);
    transition: opacity 0.25s ease, transform 0.25s ease;
    flex-shrink: 0;
}
.brand-card:hover .brand-arrow {
    opacity: 1;
    transform: translateX(0);
}

/* ── Step cards ── */
.step-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.step-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 40px rgba(37,99,235,0.14) !important;
}

/* ── Stat cards ── */
.stat-card {
    transition: transform 0.25s ease, background 0.25s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    background: rgba(255,255,255,0.08) !important;
}

/* ── Payment badges ── */
.pay-badge {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.pay-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}

/* ── Review carousel ── */
.rv-viewport { overflow: hidden; position: relative; }
.rv-viewport::before,
.rv-viewport::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0;
    width: 120px;
    z-index: 2;
    pointer-events: none;
}
.rv-viewport::before { left: 0;  background: linear-gradient(to right, #040D1A, transparent); }
.rv-viewport::after  { right: 0; background: linear-gradient(to left,  #040D1A, transparent); }
.rv-track {
    display: flex;
    align-items: stretch;
    gap: 16px;
    width: max-content;
    padding: 6px 16px 18px;
    animation: rvScroll 55s linear infinite;
}
.rv-track:hover { animation-play-state: paused; }
@keyframes rvScroll {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}
.rv-card {
    width: 290px;
    flex: 0 0 290px;
    background: #0E1F35;
    border: 1px solid rgba(37,99,235,0.18);
    border-radius: 16px;
    padding: 20px 22px 18px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 24px rgba(0,0,0,0.4);
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    position: relative;
}
.rv-card:hover {
    transform: translateY(-4px);
    border-color: rgba(37,99,235,0.45);
    box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 20px rgba(37,99,235,0.12);
}
.rv-quote { font-size: 48px; line-height: 1; color: #2563EB; font-family: Georgia, serif; margin-bottom: -6px; opacity: 0.4; user-select: none; }
.rv-stars-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.rv-stars { display: flex; gap: 2px; }
.rv-badge { font-size: 10px; font-weight: 700; padding: 2px 9px; border-radius: 999px; white-space: nowrap; }
.rv-badge-whatsapp  { background: rgba(34,197,94,0.12);  color: #4ade80; border: 1px solid rgba(34,197,94,0.25); }
.rv-badge-messenger { background: rgba(59,130,246,0.12); color: #7CB3F5; border: 1px solid rgba(59,130,246,0.25); }
.rv-badge-steam     { background: rgba(255,255,255,0.06); color: #9BB5D5; border: 1px solid rgba(255,255,255,0.1); }
.rv-badge-website   { background: rgba(37,99,235,0.12);  color: #7CB3F5; border: 1px solid rgba(37,99,235,0.25); }
.rv-shot { display: block; border-radius: 10px; overflow: hidden; border: 1px solid rgba(37,99,235,0.15); text-decoration: none; margin-bottom: 12px; }
.rv-shot img { width: 100%; height: 140px; object-fit: cover; object-position: top; display: block; }
.rv-shot-label { text-align: center; padding: 4px; font-size: 9px; color: #557AA0; background: rgba(0,0,0,0.3); }
.rv-comment { color: #9BB5D5; font-size: 13px; line-height: 1.65; flex: 1; margin-bottom: 14px; }
.rv-divider { height: 1px; background: rgba(37,99,235,0.1); margin-bottom: 12px; }
.rv-footer { display: flex; align-items: center; justify-content: space-between; }
.rv-reviewer { display: flex; align-items: center; gap: 9px; }
.rv-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg,#2563EB,#1D4ED8); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; color: #fff; flex-shrink: 0; }
.rv-name { color: #ffffff; font-size: 13px; font-weight: 700; line-height: 1; }
.rv-date { color: #557AA0; font-size: 11px; margin-top: 2px; }
.rv-verified { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 700; padding: 3px 7px; border-radius: 999px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.25); color: #4ade80; white-space: nowrap; }
.rv-score-block { display: flex; align-items: center; gap: 16px; background: #0E1F35; border: 1px solid rgba(37,99,235,0.2); border-radius: 14px; padding: 16px 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.3); }
</style>
@endpush

@section('content')

{{-- ══ HERO ══ --}}
<section class="hero-section" style="position:relative; overflow:hidden; background:#040D1A; display:flex; align-items:center;">

    {{-- Background image: covers the section, cropped from center-top on all screens --}}
    <img src="{{ asset('images/hero-image-banner.png') }}" alt="" aria-hidden="true"
         style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center top;opacity:0.32;pointer-events:none;user-select:none;">

    {{-- Overlays --}}
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(4,13,26,0.60) 0%,rgba(4,13,26,0.20) 45%,rgba(4,13,26,0.88) 100%);pointer-events:none;"></div>
    <div style="position:absolute;top:-120px;right:-100px;width:640px;height:640px;background:radial-gradient(circle,rgba(37,99,235,0.20) 0%,transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-80px;left:-60px;width:420px;height:420px;background:radial-gradient(circle,rgba(37,99,235,0.10) 0%,transparent 70%);pointer-events:none;"></div>
    <div class="grid-bg" style="position:absolute;inset:0;opacity:0.14;pointer-events:none;"></div>

    {{-- Content --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 w-full relative">
        <div class="max-w-2xl mx-auto text-center py-12 md:py-16">

            {{-- Live badge --}}
            <div class="inline-flex items-center gap-2 mb-7 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-[0.12em]"
                 style="background:rgba(37,99,235,0.15);border:1px solid rgba(37,99,235,0.35);color:#7CB3F5;">
                <span style="width:7px;height:7px;border-radius:50%;background:#4ade80;display:inline-block;box-shadow:0 0 10px rgba(74,222,128,0.9);flex-shrink:0;"></span>
                Bangladesh's #1 Gift Card Store
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-5" style="letter-spacing:-0.03em;line-height:1.08;">
                Buy Gift Cards<br>
                <span style="background:linear-gradient(95deg,#60A5FA 0%,#4B8FEF 45%,#93C5FD 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                    in Bangladesh Instantly
                </span>
            </h1>

            <p class="text-base md:text-lg text-gray-300 mb-8 leading-relaxed max-w-lg mx-auto">
                Steam, Google Play, App Store, PlayStation &amp; more —
                pay with <strong class="text-white">{{ collect($activePaymentMethods)->pluck('name')->take(3)->implode(', ') }}</strong>,
                receive your code <strong class="text-white">instantly</strong>.
                @if(site_setting('referral_enabled', false))
                Refer friends &amp; <strong class="text-white">earn wallet credit</strong> on every order.
                @endif
            </p>

            {{-- CTAs --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-10">
                <a href="#brands"
                   class="inline-flex items-center justify-center gap-2.5 px-9 py-4 rounded-2xl font-black text-white text-base transition-all duration-200 hover:opacity-90 hover:scale-[1.03] w-full sm:w-auto"
                   style="background:linear-gradient(135deg,#2563EB,#1D4ED8);box-shadow:0 0 40px rgba(37,99,235,0.55);">
                    🛒 Shop Gift Cards
                </a>
                @if(site_setting('referral_enabled', false))
                <a href="{{ route('referral.dashboard') }}"
                   class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl font-bold text-sm transition-all duration-200 hover:opacity-90 hover:scale-[1.03] w-full sm:w-auto"
                   style="background:linear-gradient(135deg,#7C3AED,#6D28D9);box-shadow:0 0 32px rgba(124,58,237,0.50);color:#fff;">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Referral &amp; Wallet
                </a>
                @else
                <a href="#how-it-works"
                   class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl font-semibold text-sm transition-all duration-200 hover:bg-white/10 w-full sm:w-auto"
                   style="color:#9BB5D5;border:1px solid rgba(255,255,255,0.13);">
                    How it works ↓
                </a>
                @endif
            </div>

            {{-- Stats row --}}
            <div class="flex items-center justify-center gap-8 sm:gap-12 pt-7" style="border-top:1px solid rgba(255,255,255,0.07);">
                @foreach([['10,000+','Happy Customers'],['50,000+','Codes Delivered'],['5.0 ★','Rating']] as [$val,$label])
                <div class="text-center">
                    <div class="font-black text-white text-xl md:text-2xl leading-none">{{ $val }}</div>
                    <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ BRANDS / PRODUCTS ══ --}}
<section id="brands" style="background:#FFFFFF; padding:80px 0;">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            @if($mainCategories->isNotEmpty())
            <h2 class="text-3xl md:text-4xl font-black mb-4" style="color:#071428;letter-spacing:-0.025em;">Our Products</h2>
            <p class="text-gray-500 max-w-md mx-auto text-sm">Choose your card, pick a denomination, and complete payment in seconds.</p>
            @else
            <h2 class="text-3xl md:text-4xl font-black mb-4" style="color:#071428;letter-spacing:-0.025em;">Choose Your Gift Card</h2>
            <p class="text-gray-500 max-w-md mx-auto text-sm">Select a product, pick your denomination, and complete payment in seconds.</p>
            @endif
        </div>

        @if($mainCategories->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
            @foreach($mainCategories as $i => $mainCat)
            <a href="{{ route('brand', $mainCat->slug) }}"
               class="brand-card group block bg-white rounded-[20px] overflow-hidden"
               style="border:1.5px solid rgba(37,99,235,0.12); box-shadow:0 2px 16px rgba(37,99,235,0.07);">
                <div class="brand-card-glow"></div>


                @if($mainCat->image)
                <div class="overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%);aspect-ratio:1057/1488;">
                    <img src="{{ Storage::disk('public')->url($mainCat->image) }}"
                         alt="{{ $mainCat->name }}"
                         class="w-full h-full object-contain"
                         loading="lazy">
                </div>
                @else
                <div class="h-44 flex items-center justify-center relative overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%);">
                    <div style="position:absolute;inset:0;background:radial-gradient(circle at 50% 50%,rgba(37,99,235,0.38) 0%,transparent 65%);"></div>
                    <span class="text-5xl relative">{{ $mainCat->icon ?? '🎁' }}</span>
                </div>
                @endif

            </a>
            @endforeach
        </div>

        @elseif($fallbackCategories->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
            @foreach($fallbackCategories as $i => $category)
            <a href="{{ route('product', $category->slug) }}"
               class="brand-card group block bg-white rounded-[20px] overflow-hidden"
               style="border:1.5px solid rgba(37,99,235,0.12); box-shadow:0 2px 16px rgba(37,99,235,0.07);">
                <div class="brand-card-glow"></div>


                @if($category->image)
                <div class="overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%);aspect-ratio:1057/1488;">
                    <img src="{{ Storage::disk('public')->url($category->image) }}"
                         alt="{{ $category->name }}"
                         class="w-full h-full object-contain"
                         loading="lazy">
                </div>
                @else
                <div class="h-44 flex items-center justify-center relative overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%);">
                    <div style="position:absolute;inset:0;background:radial-gradient(circle at 50% 50%,rgba(37,99,235,0.38) 0%,transparent 65%);"></div>
                    <span class="text-5xl relative">{{ $category->icon ?? '🎁' }}</span>
                </div>
                @endif

            </a>
            @endforeach
        </div>

        @else
        <div class="text-center py-24">
            <div class="text-6xl mb-4">🎁</div>
            <p class="text-gray-400 text-lg">Products coming soon. Check back shortly!</p>
        </div>
        @endif

    </div>
</section>

{{-- ══ STATS STRIP ══ --}}
<section style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%); padding:56px 0; position:relative; overflow:hidden;">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 50% -20%,rgba(37,99,235,0.22) 0%,transparent 60%);pointer-events:none;"></div>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            @foreach([
                ['10,000+','Happy Customers','👥'],
                ['50,000+','Codes Sold','🎁'],
                ['< 5 Min','Avg. Delivery','⚡'],
                ['5.0 / 5','Customer Rating','⭐'],
            ] as [$val,$label,$icon])
            <div class="stat-card py-5 px-3 rounded-2xl" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);">
                <div class="text-2xl mb-2">{{ $icon }}</div>
                <div class="font-black text-2xl md:text-3xl text-white leading-none mb-1.5">{{ $val }}</div>
                <div class="text-gray-400 text-xs">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ HOW IT WORKS ══ --}}
<section id="how-it-works" style="background:#F8FAFF; padding:80px 0; border-top:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-[0.12em] mb-4"
                 style="background:#EEF4FF;color:#2563EB;">
                Simple Process
            </div>
            <h2 class="text-3xl md:text-4xl font-black mb-3" style="color:#071428;letter-spacing:-0.025em;">
                Get Your Card in 3 Steps
            </h2>
            <p class="text-gray-500 text-sm">From selection to code delivery — faster than ordering food.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 relative">
            {{-- Connector line (desktop only) --}}
            <div class="hidden md:block absolute" style="top:44px;left:calc(33.33% + 8px);right:calc(33.33% + 8px);height:2px;background:linear-gradient(90deg,rgba(37,99,235,0.15),rgba(37,99,235,0.45),rgba(37,99,235,0.15));"></div>

            @foreach([
                ['01','🛒','Choose Your Card','Browse by brand, pick your denomination and add to cart.'],
                ['02','📱','Pay Securely','Complete your payment in 30 seconds. Instant confirmation, no hassle.'],
                ['03','🎉','Get Code Instantly','Code shown on screen + emailed immediately. Ready to redeem.'],
            ] as [$num,$icon,$title,$desc])
            <div class="step-card bg-white rounded-2xl p-7 text-center relative"
                 style="border:1.5px solid #E8EEF8;box-shadow:0 4px 24px rgba(7,20,40,0.06);">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4"
                     style="background:linear-gradient(135deg,#EEF4FF,#DBEAFE);border:1.5px solid rgba(37,99,235,0.18);">
                    {{ $icon }}
                </div>
                <div class="absolute top-5 right-6 font-black text-3xl" style="color:#BFDBFE;opacity:0.55;">{{ $num }}</div>
                <h3 class="font-bold text-base mb-2" style="color:#071428;">{{ $title }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <a href="#brands"
               class="inline-flex items-center gap-2.5 px-9 py-4 rounded-2xl font-black text-white text-sm transition-all duration-200 hover:opacity-90 hover:scale-[1.02]"
               style="background:linear-gradient(135deg,#2563EB,#1D4ED8);box-shadow:0 0 32px rgba(37,99,235,0.40);">
                🛒 Start Shopping Now
            </a>
        </div>
    </div>
</section>

{{-- ══ REVIEWS ══ --}}
@if($reviews->isNotEmpty())
@php $doubled = $reviews->concat($reviews); @endphp

<section style="background:#071428; padding:80px 0 88px; position:relative; overflow:hidden;">
    <div style="position:absolute;top:-100px;right:-80px;width:480px;height:480px;background:radial-gradient(circle,rgba(37,99,235,0.09) 0%,transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-60px;left:-60px;width:320px;height:320px;background:radial-gradient(circle,rgba(37,99,235,0.06) 0%,transparent 70%);pointer-events:none;"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] mb-3" style="color:#4B8FEF;">Customer Reviews</p>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2" style="letter-spacing:-0.025em;">
                    Trusted by Bangladeshi<br>Gamers Every Day
                </h2>
                <p class="text-sm" style="color:#557AA0;">Every review below is from a real purchase. No fakes, no bots.</p>
            </div>
            <div class="rv-score-block flex-shrink-0">
                <div class="text-center" style="padding-right:16px;border-right:1px solid rgba(37,99,235,0.15);">
                    <div style="font-size:40px;font-weight:900;color:#ffffff;line-height:1;">5.0</div>
                    <div style="font-size:11px;color:#557AA0;margin-top:3px;">out of 5</div>
                </div>
                <div>
                    <div style="display:flex;gap:3px;margin-bottom:5px;">
                        @for($i = 1; $i <= 5; $i++)
                        <svg width="22" height="22" fill="#FBBF24" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <div style="font-size:12px;font-weight:700;color:#ffffff;">Excellent</div>
                </div>
            </div>
        </div>
    </div>

    <div class="rv-viewport">
        <div class="rv-track">
            @foreach($doubled as $review)
            <div class="rv-card">
                <div class="rv-stars-row">
                    <div class="rv-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <svg width="18" height="18" fill="{{ $i <= $review->rating ? '#FBBF24' : 'rgba(255,255,255,0.12)' }}" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        @endfor
                    </div>
                    <span class="rv-badge
                        @if($review->platform === 'whatsapp') rv-badge-whatsapp
                        @elseif($review->platform === 'messenger') rv-badge-messenger
                        @elseif($review->platform === 'steam') rv-badge-steam
                        @else rv-badge-website
                        @endif">
                        @if($review->platform === 'whatsapp') 📱 WhatsApp
                        @elseif($review->platform === 'messenger') 💬 Messenger
                        @elseif($review->platform === 'steam') 🎮 Steam
                        @else 🌐 Website
                        @endif
                    </span>
                </div>
                @if($review->screenshot_path)
                <a href="{{ asset('storage/' . $review->screenshot_path) }}" target="_blank" rel="noopener" class="rv-shot">
                    <img src="{{ asset('storage/' . $review->screenshot_path) }}" alt="Customer proof" loading="lazy">
                    <div class="rv-shot-label">Tap to view full screenshot</div>
                </a>
                @endif
                <div class="rv-quote">"</div>
                <p class="rv-comment">{{ $review->comment }}"</p>
                <div class="rv-divider"></div>
                <div class="rv-footer">
                    <div class="rv-reviewer">
                        <div class="rv-avatar">{{ strtoupper(substr($review->displayName(), 0, 1)) }}</div>
                        <div>
                            <div class="rv-name">{{ $review->displayName() }}</div>
                            <div class="rv-date">{{ $review->created_at->format('d M Y') }}</div>
                        </div>
                    </div>
                    @if($review->is_verified_purchase)
                    <div class="rv-verified">
                        <svg width="9" height="9" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Verified
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="text-center mt-12">
        <a href="#brands"
           class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl font-bold text-white text-sm transition-all duration-200 hover:opacity-90 hover:scale-[1.02]"
           style="background:linear-gradient(135deg,#2563EB,#1D4ED8);box-shadow:0 0 28px rgba(37,99,235,0.40);">
            🛒 Shop Now — Get Your Code in Minutes
        </a>
    </div>
</section>
@endif

{{-- ══ TRUST SECTION ══ --}}
<section style="background:#071428; padding:60px 0; border-top:1px solid rgba(37,99,235,0.1);">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 text-center">
            @php
            $trustItems = [
                ['⚡','Instant Delivery','Code in your email within minutes'],
                ['🔒','Secure Payment', collect($activePaymentMethods)->pluck('name')->implode(' · ')],
                ['🎁','100% Genuine','All codes directly sourced'],
                ['💬','24/7 Support','WhatsApp &amp; Messenger always on'],
            ];
            @endphp
            @foreach($trustItems as [$icon,$title,$desc])
            <div class="py-4 px-3 rounded-2xl" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3"
                     style="background:rgba(37,99,235,0.14);border:1px solid rgba(37,99,235,0.22);">
                    {{ $icon }}
                </div>
                <div class="font-bold text-white text-sm mb-1">{{ $title }}</div>
                <div class="text-gray-400 text-xs leading-relaxed">{!! $desc !!}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ REFERRAL PROGRAM ══ --}}
@php $referralEnabled = \App\Models\SiteSetting::get('referral_enabled', false); @endphp
@if($referralEnabled)
<section style="background:linear-gradient(135deg,#071428 0%,#0D2040 60%,#1a3a6b 100%); padding:80px 0; position:relative; overflow:hidden;">
    {{-- Decorative circles --}}
    <div style="position:absolute;top:-80px;right:-80px;width:320px;height:320px;border-radius:50%;background:rgba(37,99,235,0.08);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-60px;left:-60px;width:240px;height:240px;border-radius:50%;background:rgba(37,99,235,0.06);pointer-events:none;"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold mb-4"
                 style="background:rgba(37,99,235,0.2);color:#60A5FA;border:1px solid rgba(37,99,235,0.3);">
                REFERRAL PROGRAM
            </div>
            <h2 class="text-2xl md:text-3xl font-black mb-3 text-white" style="letter-spacing:-0.02em;">
                Share &amp; Earn Together
            </h2>
            <p class="text-sm md:text-base max-w-xl mx-auto" style="color:#8BAFD4;">
                Share your unique referral link with friends. When they buy, you both get rewarded — instantly to your wallet.
            </p>
        </div>

        {{-- 3-step flow --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            @foreach([
                ['icon'=>'🔗','step'=>'1','title'=>'Share Your Code','desc'=>'Every account gets a unique referral code. Share it with friends, family, or on social media.'],
                ['icon'=>'🛒','step'=>'2','title'=>'Friend Buys','desc'=>'Your friend enters your code at checkout and gets an instant discount on their order.'],
                ['icon'=>'💰','step'=>'3','title'=>'Both Get Rewarded','desc'=>'After their purchase is confirmed, you earn wallet credit — usable on your next order.'],
            ] as $step)
            <div class="rounded-2xl p-6 text-center relative"
                 style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);backdrop-filter:blur(8px);">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4"
                     style="background:rgba(37,99,235,0.2);border:1px solid rgba(37,99,235,0.3);">
                    {{ $step['icon'] }}
                </div>
                <div class="absolute top-4 right-4 text-xs font-black opacity-20 text-white" style="font-size:32px;">{{ $step['step'] }}</div>
                <h3 class="font-bold text-white mb-2">{{ $step['title'] }}</h3>
                <p class="text-sm" style="color:#8BAFD4;">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="text-center">
            @auth
            <div class="inline-flex flex-col sm:flex-row items-center gap-4 bg-white/5 border border-white/10 rounded-2xl px-6 py-5">
                <div class="text-left">
                    <p class="text-xs font-semibold mb-1" style="color:#8BAFD4;">Your Referral Code</p>
                    <div class="flex items-center gap-3">
                        <span class="text-2xl font-black font-mono tracking-widest text-white"
                              id="home-ref-code">{{ auth()->user()->referral_code ?? '—' }}</span>
                        <button onclick="navigator.clipboard.writeText('{{ auth()->user()->referral_code ?? '' }}');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)"
                                class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                style="background:rgba(37,99,235,0.3);color:#93C5FD;border:1px solid rgba(37,99,235,0.4);">
                            Copy
                        </button>
                    </div>
                </div>
                <div class="w-px h-10 hidden sm:block" style="background:rgba(255,255,255,0.1);"></div>
                <a href="{{ route('referral.dashboard') }}"
                   class="text-sm font-bold px-5 py-2.5 rounded-xl transition-all text-white"
                   style="background:#2563EB;">
                    View Dashboard →
                </a>
            </div>
            @else
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2 font-bold px-8 py-4 rounded-2xl text-white text-base transition-all hover:opacity-90 hover:shadow-lg"
               style="background:#2563EB;">
                Create Account &amp; Get Your Code →
            </a>
            <p class="text-xs mt-3" style="color:#557AA0;">Free to join. Referral code generated instantly on signup.</p>
            @endauth
        </div>
    </div>
</section>
@endif

{{-- ══ SEO CONTENT ══ --}}
<section style="background:#F8FAFF; padding:64px 0; border-top:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-2xl md:text-3xl font-black mb-3" style="color:#071428;letter-spacing:-0.02em;">
                Bangladesh's Most Trusted Gift Card Store
            </h2>
            <p class="text-gray-500 max-w-2xl mx-auto text-sm leading-relaxed">
                Your one-stop destination to <strong>buy gift cards in Bangladesh</strong> — Steam, Google Play, App Store, PlayStation &amp; more — all with bKash payment. Fast, safe, and 100% genuine.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8;box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <h3 class="font-bold text-base mb-3" style="color:#071428;">Buy Gift Cards in Bangladesh</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Looking to <strong>buy gift cards in Bangladesh</strong>? <strong>Steam Store BD</strong> offers instant digital delivery of 100% genuine codes across all major brands. Pay securely with <strong>bKash or Nagad</strong> and receive your code within minutes. No waiting, no hassle.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8;box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <h3 class="font-bold text-base mb-3" style="color:#071428;">Best BDT Prices, Every Day</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Get the best <strong>gift card prices in BDT</strong>. Whether you want Steam Wallet, Google Play, App Store or PlayStation — <strong>Steam Store BD</strong> offers competitive BDT rates with no hidden charges and transparent pricing on every denomination.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8;box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <h3 class="font-bold text-base mb-3" style="color:#071428;">Why Choose Steam Store BD?</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    <strong>Steam Store BD</strong> is Bangladesh's #1 marketplace for digital gift cards. We source all codes directly, ensuring 100% genuine cards. Every purchase is backed by our replacement guarantee. Trusted by 10,000+ Bangladeshi customers.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8;box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <h3 class="font-bold text-base mb-3" style="color:#071428;">How to Buy a Gift Card with bKash</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Buying a <strong>digital gift card in Bangladesh</strong> has never been easier. Select your brand, pick a denomination, and pay with bKash. Your code is delivered instantly to your email. The fastest way to top up any platform — no bank card needed.
                </p>
            </div>
        </div>
    </div>
</section>

@endsection
