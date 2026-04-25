@extends('layouts.storefront')

@section('title', 'Buy Steam Gift Cards in Bangladesh | bKash Payment | Steam Store BD')
@section('meta_description', 'Buy Steam Wallet gift cards in Bangladesh with bKash. $5, $10, $20, $50, $100 USD. Instant code delivery to email. Genuine Steam codes at best BDT price.')
@section('meta_keywords', 'steam gift card bangladesh, steam wallet bd, buy steam gift card bkash, steam gift card bd price 2025, steam wallet code bd, steam card 10 dollar bangladesh')

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
                Top Up Your Steam<br>
                <span style="background:linear-gradient(90deg,#4B8FEF,#60A5FA);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Wallet Instantly</span>
            </h1>

            <p class="text-lg text-gray-300 mb-8 leading-relaxed max-w-lg mx-auto">
                Pay with <strong class="text-white">bKash</strong> — receive your gift card code <strong class="text-white">within minutes</strong>.
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
                $maxDenom = $cards->max('denomination_usd');
                $inStockCount = $cards->where('stock_count', '>', 0)->count();
                $denomLabels = $cards->take(5)->map(fn($c) => '$'.number_format($c->denomination_usd,0))->implode(', ');
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

@endsection
