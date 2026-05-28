@extends('layouts.storefront')

@section('title', 'Buy Steam Gift Cards in Bangladesh | bKash — Steam Store BD')
@section('meta_description', 'Steam Store BD is Bangladesh\'s #1 Steam gift card store. Buy Steam Wallet codes with bKash — $5, $10, $20, $50, $100 USD. Instant delivery to email. Best BDT price. 100% genuine.')
@section('meta_keywords', 'steam store bd, steam store bd, steam gift card bd, steam gift card bangladesh, steam wallet bangladesh, buy steam gift card bangladesh, steam gift card sell bd, steam gift card buy bangladesh, steam wallet top up bd, steam card bd price, steam wallet code bangladesh, steam gift card bd price 2025, steam wallet gift card bd, steam gift card bkash, steam gift card sell bangladesh')

@push('schema')
@php
$_schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'ItemList',
    'name'     => 'Steam Gift Cards Bangladesh',
    'description' => 'Buy Steam Wallet gift cards in Bangladesh with bKash payment',
    'url'      => url('/'),
    'itemListElement' => $categories->values()->map(fn($cat, $i) => [
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'name'     => $cat->name,
        'url'      => route('product', $cat->slug),
    ])->toArray(),
];
@endphp
<script type="application/ld+json">
{!! json_encode($_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')

{{-- ══ HERO ══ --}}
<section style="position:relative; overflow:hidden; background:#040D1A;">
    {{-- Hero background image --}}
    <div style="position:absolute;inset:0;background-image:url('{{ asset('images/hero-image.png') }}');background-size:cover;background-position:center top;background-repeat:no-repeat;opacity:0.35;"></div>
    {{-- Dark overlay gradients for readability --}}
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom, rgba(4,13,26,0.55) 0%, rgba(4,13,26,0.25) 40%, rgba(4,13,26,0.70) 100%);"></div>
    <div style="position:absolute;inset:0;background:linear-gradient(to right, rgba(4,13,26,0.4) 0%, transparent 50%, rgba(4,13,26,0.4) 100%);"></div>
    {{-- Blue glow accents --}}
    <div style="position:absolute;top:-80px;right:-60px;width:480px;height:480px;background:radial-gradient(circle,rgba(37,99,235,0.20) 0%,transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-60px;left:-40px;width:320px;height:320px;background:radial-gradient(circle,rgba(37,99,235,0.12) 0%,transparent 70%);pointer-events:none;"></div>
    {{-- Grid --}}
    <div class="grid-bg" style="position:absolute;inset:0;opacity:0.25;"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28 relative">
        <div class="max-w-2xl mx-auto text-center">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 mb-6 px-4 py-1.5 rounded-full text-sm font-medium" style="background:rgba(37,99,235,0.15);border:1px solid rgba(37,99,235,0.3);color:#7CB3F5;">
                🇧🇩 &nbsp;Bangladesh's #1 Steam Gift Card Store
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-[1.1] mb-5" style="letter-spacing:-0.03em;">
                Buy Steam Gift Cards<br>
                <span style="background:linear-gradient(90deg,#4B8FEF,#60A5FA);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">in Bangladesh Instantly</span>
            </h1>

            <p class="text-lg text-gray-300 mb-8 leading-relaxed max-w-lg mx-auto">
                <strong class="text-white">Steam Store BD</strong> — pay with <strong class="text-white">bKash</strong> and receive your Steam gift card code <strong class="text-white">within minutes</strong>.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                {{-- Primary CTA --}}
                <a href="#products"
                   class="inline-flex items-center gap-2.5 px-8 py-4 rounded-2xl font-bold text-white text-base transition-all duration-200 hover:opacity-90 hover:scale-[1.02]"
                   style="background:linear-gradient(135deg,#2563EB,#1D4ED8);box-shadow:0 0 30px rgba(37,99,235,0.4);">
                    🛒 Shop Gift Cards
                </a>
            </div>

            {{-- Stats strip --}}
            <div class="flex items-center justify-center gap-8 mt-12 pt-8" style="border-top:1px solid rgba(255,255,255,0.07);">
                @foreach([['10,000+','Happy Customers'],['⚡','Instant Delivery'],['100%','Genuine Codes']] as [$num, $label])
                <div class="text-center">
                    <div class="font-bold text-white text-lg">{{ $num }}</div>
                    <div class="text-gray-400 text-xs mt-0.5">{{ $label }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ PRODUCTS ══ --}}
<section id="products" style="background:#FFFFFF; padding:72px 0;">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-12">
            <div class="inline-block px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-widest mb-3" style="background:#EEF4FF;color:#2563EB;">Gift Cards</div>
            <h2 class="text-3xl md:text-4xl font-black" style="color:#071428;letter-spacing:-0.02em;">Choose Your Steam Wallet</h2>
            <p class="text-gray-500 mt-3 max-w-md mx-auto">Select a product, pick your denomination, and pay with bKash in seconds.</p>
        </div>

        {{-- Category product cards --}}
        @if($categories->isEmpty())
        <div class="text-center py-20">
            <div class="text-6xl mb-4">🎮</div>
            <p class="text-gray-400 text-lg">Products coming soon. Check back shortly!</p>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($categories as $category)
            @php
                $cards = $category->giftCards->where('is_active', true);
                $minPrice = $cards->min('price_bdt');
                $maxDenom = $cards->max('denomination');
                $inStockCount = $cards->where('stock_count', '>', 0)->count();
                $denomLabels = $cards->take(5)->map(fn($c) => format_card_denomination($c->denomination, $c->denomination_currency))->implode(', ');
            @endphp
            <a href="{{ route('product', $category->slug) }}"
               class="group block bg-white rounded-3xl overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
               style="border:2px solid rgba(37,99,235,0.2); box-shadow:0 10px 40px rgba(37,99,235,0.15); position:relative;">
                {{-- Wow border glow effect --}}
                <div class="absolute inset-0 rounded-3xl pointer-events-none" style="border:2px solid transparent; background:linear-gradient(135deg,rgba(37,99,235,0.3),rgba(59,130,246,0.2)) border-box; -webkit-mask:linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite:xor; mask-composite:exclude;"></div>

                {{-- Card hero --}}
                @if($category->image)
                <div class="relative overflow-hidden flex items-center justify-center" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%); aspect-ratio: 1057/1488;">
                    <img src="{{ Storage::disk('public')->url($category->image) }}" 
                         alt="{{ $category->name }}" 
                         class="w-full h-full object-contain">
                    {{-- In-stock badge overlay --}}
                    <div class="absolute inset-0 flex items-end justify-center pb-3">
                        @if($inStockCount > 0)
                        <div class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold text-white" 
                             style="background:linear-gradient(135deg,rgba(34,197,94,0.95),rgba(16,185,129,0.95)); border:2px solid rgba(34,197,94,0.5); box-shadow:0 8px 24px rgba(34,197,94,0.35);">
                            <span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span>
                            In Stock
                        </div>
                        @else
                        <div class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold text-white" 
                             style="background:linear-gradient(135deg,rgba(239,68,68,0.95),rgba(220,38,38,0.95)); border:2px solid rgba(239,68,68,0.5); box-shadow:0 8px 24px rgba(239,68,68,0.35);">
                            <span class="w-2.5 h-2.5 rounded-full bg-white"></span>
                            Out of Stock
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="relative h-44 flex items-center justify-center overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%);">
                    <div style="position:absolute;inset:0;background:radial-gradient(circle at 30% 50%,rgba(37,99,235,0.3) 0%,transparent 65%);"></div>
                    <div class="relative text-center">
                        <div class="text-5xl mb-2">{{ $category->icon ?? '🎮' }}</div>
                        <div class="text-white font-black text-sm tracking-[0.25em] uppercase opacity-80">STEAM</div>
                    </div>
                    {{-- In-stock badge for fallback --}}
                    <div class="absolute top-3 right-3">
                        @if($inStockCount > 0)
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);color:#4ADE80;">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse inline-block"></span>
                            In Stock
                        </div>
                        @else
                        <div class="px-2.5 py-1 rounded-full text-xs font-semibold" style="background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#F87171;">
                            Out of Stock
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Card body --}}
                <div class="p-3 text-center">
                    <h3 class="font-bold text-sm group-hover:text-brand-500 transition-colors mb-1" style="color:#071428;">{{ $category->name }}</h3>
                    <div class="text-xs text-gray-400 font-medium mb-2">Starting from</div>
                    <div class="font-black text-base mb-3" style="color:#2563EB;">৳ {{ number_format($minPrice, 0) }}</div>
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg font-semibold text-xs text-white transition-all group-hover:shadow-md" style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                        Select options
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- ══ HOW IT WORKS ══ --}}
<section style="background:#F8FAFF; padding:72px 0; border-top:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <div class="inline-block px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-widest mb-3" style="background:#EEF4FF;color:#2563EB;">Simple Process</div>
            <h2 class="text-3xl font-black" style="color:#071428;letter-spacing:-0.02em;">Get Your Card in 3 Steps</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                ['01','🛒','Choose & Select','Pick your Steam wallet type and select the denomination that fits your budget.'],
                ['02','📱','Pay with bKash','Complete payment securely via bKash — takes seconds.'],
                ['03','📧','Get Code Instantly','Your gift card code appears on screen and is emailed to you immediately.'],
            ] as [$num,$icon,$title,$desc])
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8;box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background:#EEF4FF;">{{ $icon }}</div>
                    <span class="font-black text-2xl" style="color:#BFDBFE;">{{ $num }}</span>
                </div>
                <h3 class="font-bold text-base mb-2" style="color:#071428;">{{ $title }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ REVIEWS ══ --}}
@if($reviews->isNotEmpty())
@push('styles')
<style>
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
    flex-direction: row;
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

/* Dark navy card — matches site theme */
.rv-card {
    width: 290px;
    flex: 0 0 290px;
    background: #0E1F35;
    border: 1px solid rgba(37,99,235,0.18);
    border-radius: 16px;
    padding: 20px 22px 18px;
    display: flex;
    flex-direction: column;
    gap: 0;
    box-shadow: 0 4px 24px rgba(0,0,0,0.4);
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    position: relative;
}
.rv-card:hover {
    transform: translateY(-4px);
    border-color: rgba(37,99,235,0.45);
    box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 20px rgba(37,99,235,0.12);
}

/* Quote mark */
.rv-quote {
    font-size: 48px;
    line-height: 1;
    color: #2563EB;
    font-family: Georgia, serif;
    margin-bottom: -6px;
    opacity: 0.4;
    user-select: none;
}

/* Stars row */
.rv-stars-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.rv-stars { display: flex; gap: 2px; }

/* Platform badge — dark theme */
.rv-badge {
    font-size: 10px; font-weight: 700;
    padding: 2px 9px; border-radius: 999px;
    white-space: nowrap; letter-spacing: 0.02em;
}
.rv-badge-whatsapp  { background: rgba(34,197,94,0.12);  color: #4ade80; border: 1px solid rgba(34,197,94,0.25); }
.rv-badge-messenger { background: rgba(59,130,246,0.12); color: #7CB3F5; border: 1px solid rgba(59,130,246,0.25); }
.rv-badge-steam     { background: rgba(255,255,255,0.06); color: #9BB5D5; border: 1px solid rgba(255,255,255,0.1); }
.rv-badge-website   { background: rgba(37,99,235,0.12);  color: #7CB3F5; border: 1px solid rgba(37,99,235,0.25); }

/* Screenshot */
.rv-shot { display: block; border-radius: 10px; overflow: hidden; border: 1px solid rgba(37,99,235,0.15); text-decoration: none; margin-bottom: 12px; }
.rv-shot img { width: 100%; height: 140px; object-fit: cover; object-position: top; display: block; }
.rv-shot-label { text-align: center; padding: 4px; font-size: 9px; color: #557AA0; background: rgba(0,0,0,0.3); }

/* Comment */
.rv-comment { color: #9BB5D5; font-size: 13px; line-height: 1.65; flex: 1; margin-bottom: 14px; }

/* Divider */
.rv-divider { height: 1px; background: rgba(37,99,235,0.1); margin-bottom: 12px; }

/* Footer */
.rv-footer { display: flex; align-items: center; justify-content: space-between; }
.rv-reviewer { display: flex; align-items: center; gap: 9px; }
.rv-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, #2563EB, #1D4ED8);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 900; color: #fff; flex-shrink: 0;
}
.rv-name { color: #ffffff; font-size: 13px; font-weight: 700; line-height: 1; }
.rv-date { color: #557AA0; font-size: 11px; margin-top: 2px; }

/* Verified badge — dark theme */
.rv-verified {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 700;
    padding: 3px 7px; border-radius: 999px;
    background: rgba(34,197,94,0.1);
    border: 1px solid rgba(34,197,94,0.25);
    color: #4ade80;
    white-space: nowrap;
}

/* Score block — dark theme */
.rv-score-block {
    display: flex; align-items: center; gap: 16px;
    background: #0E1F35;
    border: 1px solid rgba(37,99,235,0.2);
    border-radius: 14px;
    padding: 16px 22px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.3);
}
</style>
@endpush

@php $doubled = $reviews->concat($reviews); @endphp

<section style="background:#071428; padding:80px 0 88px; position:relative; overflow:hidden;">
    <div style="position:absolute;top:-100px;right:-80px;width:480px;height:480px;background:radial-gradient(circle,rgba(37,99,235,0.09) 0%,transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-60px;left:-60px;width:320px;height:320px;background:radial-gradient(circle,rgba(37,99,235,0.06) 0%,transparent 70%);pointer-events:none;"></div>

    {{-- ── Header ── --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-8">

            {{-- Left: text --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] mb-3" style="color:#4B8FEF;">
                    Customer Reviews
                </p>
                <h2 class="text-3xl md:text-4xl font-black text-white mb-2" style="letter-spacing:-0.025em;">
                    Trusted by Bangladeshi<br>Gamers Every Day
                </h2>
                <p class="text-sm" style="color:#557AA0;">Every review below is from a real purchase. No fakes, no bots.</p>
            </div>

            {{-- Right: score block --}}
            <div class="rv-score-block flex-shrink-0">
                <div class="text-center" style="padding-right:16px; border-right:1px solid rgba(37,99,235,0.15);">
                    <div style="font-size:40px; font-weight:900; color:#ffffff; line-height:1;">5.0</div>
                    <div style="font-size:11px; color:#557AA0; margin-top:3px;">out of 5</div>
                </div>
                <div>
                    <div style="display:flex; gap:3px; margin-bottom:5px;">
                        @for($i = 1; $i <= 5; $i++)
                        <svg width="22" height="22" fill="#FBBF24" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <div style="font-size:12px; font-weight:700; color:#ffffff;">Excellent</div>
                    {{-- <div style="font-size:11px; color:#557AA0;">{{ $reviews->count() }}+ verified reviews</div> --}}
                </div>
            </div>
        </div>
    </div>

    {{-- ── Carousel ── --}}
    <div class="rv-viewport">
        <div class="rv-track">
            @foreach($doubled as $review)
            <div class="rv-card">

                {{-- Stars + platform --}}
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

                {{-- Screenshot --}}
                @if($review->screenshot_path)
                <a href="{{ asset('storage/' . $review->screenshot_path) }}" target="_blank" rel="noopener" class="rv-shot">
                    <img src="{{ asset('storage/' . $review->screenshot_path) }}" alt="Customer proof" loading="lazy">
                    <div class="rv-shot-label">Tap to view full screenshot</div>
                </a>
                @endif

                {{-- Quote + comment --}}
                <div class="rv-quote">"</div>
                <p class="rv-comment">{{ $review->comment }}"</p>

                <div class="rv-divider"></div>

                {{-- Footer --}}
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

    {{-- CTA --}}
    <div class="text-center mt-12">
        <a href="#products"
           class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl font-bold text-white text-sm transition-all duration-200 hover:opacity-90"
           style="background:linear-gradient(135deg,#2563EB,#1D4ED8);box-shadow:0 0 28px rgba(37,99,235,0.4);">
            🛒 Shop Now
        </a>
    </div>
</section>
@endif

{{-- ══ TRUST SECTION ══ --}}
<section style="background:#071428; padding:56px 0;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            @foreach([
                ['⚡','Instant Delivery','Codes in your email within minutes'],
                ['🔒','bKash Secured','Encrypted tokenized payment'],
                ['🎮','100% Genuine','All codes directly sourced'],
                ['💬','Support 24/7','We\'re here when you need us'],
            ] as [$icon,$title,$desc])
            <div>
                <div class="text-3xl mb-2">{{ $icon }}</div>
                <div class="font-bold text-white text-sm mb-1">{{ $title }}</div>
                <div class="text-gray-400 text-xs leading-relaxed">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ SEO CONTENT ══ --}}
<section style="background:#F8FAFF; padding:64px 0; border-top:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h2 class="text-2xl md:text-3xl font-black mb-3" style="color:#071428;letter-spacing:-0.02em;">
                Steam Store BD — Bangladesh's Most Trusted Steam Gift Card Store
            </h2>
            <p class="text-gray-500 max-w-2xl mx-auto text-sm leading-relaxed">
                Your one-stop destination to <strong>buy Steam gift cards in Bangladesh</strong> and top up your <strong>Steam Store BD</strong> with bKash — fast, safe, and genuine.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8; box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <h3 class="font-bold text-base mb-3" style="color:#071428;">Buy Steam Gift Cards in Bangladesh</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Looking to <strong>buy a Steam gift card in Bangladesh</strong>? <strong>Steam Store BD</strong> is your most trusted <strong>Steam Store BD</strong> top-up solution. We offer instant digital delivery of genuine Steam codes — no waiting, no hassle. Pay securely with <strong>bKash</strong> and receive your code within minutes. Available denominations: $5, $10, $20, $50, and $100 USD — priced in BDT.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8; box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <h3 class="font-bold text-base mb-3" style="color:#071428;">Steam Gift Card BD — Best BDT Price</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Get the best <strong>Steam gift card BD price</strong> in BDT. Whether you want to <strong>buy a Steam gift card</strong> for gaming or <strong>sell Steam gift card</strong> codes as gifts — <strong>Steam Store BD</strong> provides competitive BDT rates and instant delivery. All prices are transparent, with no hidden charges.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8; box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <h3 class="font-bold text-base mb-3" style="color:#071428;">Why Choose Steam Store BD?</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    <strong>Steam Store BD</strong> is Bangladesh's #1 marketplace for <strong>Steam Wallet</strong> top-ups and <strong>Steam gift cards</strong>. We source all codes directly, ensuring 100% genuine <strong>Steam gift card</strong> codes. Every purchase is backed by our guarantee — if a code doesn't work, we replace it immediately. Trusted by 10,000+ Bangladeshi gamers.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8; box-shadow:0 2px 8px rgba(7,20,40,0.04);">
                <h3 class="font-bold text-base mb-3" style="color:#071428;">How to Buy Steam Gift Card BD with bKash</h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Buying a <strong>Steam wallet gift card in Bangladesh</strong> has never been easier. Select your <strong>Steam gift card</strong> denomination, add to cart, and pay with bKash. Your <strong>Steam wallet code</strong> is delivered instantly to your email. The fastest way to top up <strong>Steam wallet in Bangladesh</strong> — no bank card needed.
                </p>
            </div>
        </div>
    </div>
</section>

@endsection
