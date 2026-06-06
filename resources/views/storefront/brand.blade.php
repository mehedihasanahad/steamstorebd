@extends('layouts.storefront')

@section('title', ($mainCategory->seo_title ?: 'Buy ' . $mainCategory->name . ' in Bangladesh | bKash') . ' — Steam Store BD')
@section('meta_description', $mainCategory->seo_description ?: 'Buy ' . $mainCategory->name . ' in Bangladesh with bKash. Instant code delivery to email. 100% genuine codes at best BDT price. Steam Store BD.')
@php
    $_mcName = strtolower($mainCategory->name);
@endphp
@section('meta_keywords', $mainCategory->seo_keywords ?: 'buy ' . $_mcName . ' bangladesh, trusted ' . $_mcName . ' bd, ' . $_mcName . ' bkash, ' . $_mcName . ' nagad, ' . $_mcName . ' bd price 2025, genuine ' . $_mcName . ' bangladesh, digital gift card bd, gift card bkash nagad bangladesh')
@section('og_type', 'website')
@section('og_image_alt', 'Buy ' . $mainCategory->name . ' in Bangladesh — Steam Store BD')
@if($mainCategory->image)
@section('og_image', Storage::disk('public')->url($mainCategory->image))
@endif

@push('schema')
@php
    $_inStockCount = $categories->sum(fn($cat) => $cat->giftCards->where('stock_count', '>', 0)->count());
    $_pageSchema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $mainCategory->name, 'item' => route('brand', $mainCategory->slug)],
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type'    => 'ItemList',
                'name'     => $mainCategory->name . ' Bangladesh',
                'description' => 'Buy ' . $mainCategory->name . ' in Bangladesh with bKash payment',
                'url'      => route('brand', $mainCategory->slug),
                'itemListElement' => $categories->values()->map(fn($cat, $i) => [
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'name'     => $cat->name,
                    'url'      => route('product', $cat->slug),
                ])->toArray(),
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($_pageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@push('styles')
<style>
.brand-card {
    position: relative;
    transition: transform 0.28s cubic-bezier(.22,.68,0,1.2), box-shadow 0.28s ease;
}
.brand-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 24px 56px rgba(37,99,235,0.24), 0 4px 16px rgba(0,0,0,0.10) !important;
}
.brand-card:hover .brand-card-glow { opacity: 1; }
.brand-card-glow {
    position: absolute;
    inset: -1px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(37,99,235,0.5), rgba(96,165,250,0.3), rgba(37,99,235,0.5));
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    z-index: 0;
}
.brand-card > * { position: relative; z-index: 1; }
.denom-pill {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    background: rgba(37,99,235,0.08);
    color: #2563EB;
    border: 1px solid rgba(37,99,235,0.18);
    white-space: nowrap;
}
/* Rich text editor content reset */
.rich-content { font-size: 0.9rem; line-height: 1.75; color: #374151; }
.rich-content h1, .rich-content h2, .rich-content h3,
.rich-content h4, .rich-content h5, .rich-content h6 {
    font-weight: 800; color: #111827; margin: 1.2em 0 0.5em; line-height: 1.3;
}
.rich-content h1 { font-size: 1.5rem; }
.rich-content h2 { font-size: 1.25rem; }
.rich-content h3 { font-size: 1.1rem; }
.rich-content h4 { font-size: 1rem; }
.rich-content p  { margin: 0.75em 0; color: #4B5563; }
.rich-content ul, .rich-content ol { margin: 0.75em 0; padding-left: 1.5em; color: #4B5563; }
.rich-content ul { list-style-type: disc; }
.rich-content ol { list-style-type: decimal; }
.rich-content li { margin: 0.35em 0; }
.rich-content strong, .rich-content b { font-weight: 700; color: #1F2937; }
.rich-content em, .rich-content i { font-style: italic; }
.rich-content u { text-decoration: underline; }
.rich-content a { color: #2563EB; text-decoration: underline; }
.rich-content a:hover { color: #1D4ED8; }
.rich-content blockquote {
    border-left: 3px solid #2563EB; margin: 1em 0;
    padding: 0.5em 1em; background: #EEF4FF; border-radius: 0 8px 8px 0; color: #374151;
}
.rich-content code {
    background: #F3F4F6; border-radius: 4px; padding: 2px 6px;
    font-size: 0.82em; font-family: monospace; color: #DC2626;
}
.rich-content pre {
    background: #1E293B; color: #E2E8F0; padding: 1em 1.25em;
    border-radius: 10px; overflow-x: auto; margin: 1em 0; font-size: 0.82em;
}
.rich-content pre code { background: none; padding: 0; color: inherit; }
.rich-content hr { border: none; border-top: 1px solid #E5E7EB; margin: 1.5em 0; }
.rich-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 0.75em 0; }
.rich-content table { width: 100%; border-collapse: collapse; margin: 1em 0; font-size: 0.85rem; }
.rich-content th { background: #EEF4FF; color: #1E3A5F; font-weight: 700; padding: 8px 12px; border: 1px solid #DBEAFE; text-align: left; }
.rich-content td { padding: 8px 12px; border: 1px solid #E5E7EB; color: #4B5563; }
.rich-content tr:nth-child(even) td { background: #F9FAFB; }
</style>
@endpush

@section('content')

{{-- Compact Brand Header (breadcrumb + identity in one section) --}}
<section style="background:linear-gradient(180deg,#040D1A 0%,#091525 100%); position:relative; overflow:hidden; padding:24px 0 30px;">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 75% 50%,rgba(37,99,235,0.18) 0%,transparent 60%);pointer-events:none;"></div>
    <div class="grid-bg" style="position:absolute;inset:0;opacity:0.10;"></div>

    {{-- Large brand image as subtle background accent on desktop --}}
    @if($mainCategory->image)
    <div style="position:absolute;right:5%;top:50%;transform:translateY(-50%);width:220px;height:220px;opacity:0.07;pointer-events:none;" class="hidden xl:block">
        <img src="{{ Storage::disk('public')->url($mainCategory->image) }}" alt="" aria-hidden="true" class="w-full h-full object-contain">
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-1.5 text-xs mb-4" style="color:#4A6080;">
            <a href="{{ route('home') }}" class="hover:text-blue-400 transition-colors">Home</a>
            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span style="color:#8FAFC8;">{{ $mainCategory->name }}</span>
        </nav>

        {{-- Brand identity row --}}
        <div class="flex items-center gap-4 md:gap-5">
            @if($mainCategory->image)
            <div class="w-14 h-14 md:w-[72px] md:h-[72px] flex-shrink-0 rounded-xl overflow-hidden">
                <img src="{{ Storage::disk('public')->url($mainCategory->image) }}"
                     alt="{{ $mainCategory->name }}"
                     class="w-full h-full object-contain">
            </div>
            @else
            <div class="w-14 h-14 md:w-[72px] md:h-[72px] flex-shrink-0 rounded-xl flex items-center justify-center text-3xl">
                {{ $mainCategory->icon ?? '🎁' }}
            </div>
            @endif

            <div class="flex-1 min-w-0">
                <h1 class="text-xl md:text-2xl lg:text-3xl font-black text-white mb-1" style="letter-spacing:-0.02em; line-height:1.2;">
                    {{ $mainCategory->name }}
                </h1>
                @if($mainCategory->description)
                <p class="text-sm mb-2 leading-relaxed" style="color:#7BA8C8;">{{ $mainCategory->description }}</p>
                @endif
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-medium" style="color:#4A7A9B;">⚡ Instant delivery · bKash · Nagad</span>
                    {{-- Mobile: small pill button --}}
                    @if($mainCategory->how_to_redeem)
                    <a href="#how-to-redeem"
                       class="sm:hidden inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                       style="background:rgba(37,99,235,0.15);border:1px solid rgba(37,99,235,0.3);color:#7CB3F5;">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        How to Redeem
                    </a>
                    @endif
                </div>
            </div>

            {{-- Desktop: solid blue button on the right --}}
            @if($mainCategory->how_to_redeem)
            <a href="#how-to-redeem"
               class="hidden sm:inline-flex flex-shrink-0 items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm text-white transition-all"
               style="background:linear-gradient(135deg,#2563EB,#1D4ED8); box-shadow:0 4px 14px rgba(37,99,235,0.4);"
               onmouseover="this.style.boxShadow='0 6px 20px rgba(37,99,235,0.55)';this.style.transform='translateY(-1px)'"
               onmouseout="this.style.boxShadow='0 4px 14px rgba(37,99,235,0.4)';this.style.transform='translateY(0)'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                How to Redeem
            </a>
            @endif
        </div>
    </div>
</section>

{{-- Category Cards Grid --}}
<section style="background:#EEF3FF; padding:36px 0 64px;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($categories->isEmpty())
        <div class="text-center py-24">
            <div class="text-5xl mb-4">🎁</div>
            <p class="text-gray-400 text-lg">Products coming soon. Check back shortly!</p>
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-5">
            @foreach($categories as $category)
            @php
                $cards        = $category->giftCards->where('is_active', true);
                $minPrice     = $cards->min('price_bdt');
                $inStockCount = $cards->where('stock_count', '>', 0)->count();
            @endphp
            <a href="{{ route('product', $category->slug) }}"
               class="brand-card group block bg-white rounded-2xl overflow-hidden"
               style="border:1.5px solid rgba(37,99,235,0.13); box-shadow:0 4px 18px rgba(37,99,235,0.08);">
                <div class="brand-card-glow"></div>

                @if($category->image)
                <div class="relative overflow-hidden flex items-center justify-center" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%); aspect-ratio:1057/1488;">
                    <img src="{{ Storage::disk('public')->url($category->image) }}"
                         alt="{{ $category->name }}"
                         class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-[1.03]">
                    <div class="absolute inset-0 flex items-end justify-center pb-3">
                        @if($inStockCount > 0)
                        <div class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold text-white"
                             style="background:linear-gradient(135deg,rgba(34,197,94,0.95),rgba(16,185,129,0.95));border:2px solid rgba(34,197,94,0.5);box-shadow:0 8px 24px rgba(34,197,94,0.35);">
                            <span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span>In Stock
                        </div>
                        @else
                        <div class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold text-white"
                             style="background:linear-gradient(135deg,rgba(239,68,68,0.95),rgba(220,38,38,0.95));border:2px solid rgba(239,68,68,0.5);">
                            <span class="w-2.5 h-2.5 rounded-full bg-white"></span>Out of Stock
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="relative h-44 flex items-center justify-center overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%);">
                    <div style="position:absolute;inset:0;background:radial-gradient(circle at 30% 50%,rgba(37,99,235,0.3) 0%,transparent 65%);"></div>
                    <div class="relative text-center">
                        <div class="text-5xl mb-2">{{ $category->icon ?? '🎁' }}</div>
                        <div class="text-white font-black text-sm tracking-[0.25em] uppercase opacity-80">GIFT CARD</div>
                    </div>
                    <div class="absolute top-3 right-3">
                        @if($inStockCount > 0)
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);color:#4ADE80;">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse inline-block"></span>In Stock
                        </div>
                        @else
                        <div class="px-2.5 py-1 rounded-full text-xs font-semibold" style="background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#F87171;">
                            Out of Stock
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <div class="p-3 text-center">
                    <h3 class="font-bold text-sm group-hover:text-blue-600 transition-colors mb-1" style="color:#071428;">{{ $category->name }}</h3>
                    <div class="text-xs text-gray-400 font-medium mb-2">Starting from</div>
                    <div class="font-black text-base mb-3" style="color:#2563EB;">৳ {{ number_format($minPrice, 0) }}</div>
                    <div class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg font-semibold text-xs text-white group-hover:shadow-md"
                         style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                        Select options
                        <svg class="w-3 h-3 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif

    </div>
</section>

{{-- How to Redeem --}}
@if($mainCategory->how_to_redeem)
<section id="how-to-redeem" style="background:#F8FAFF; padding:56px 0; border-top:1px solid #E2EAF8;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#2563EB,#1D4ED8); box-shadow:0 4px 12px rgba(37,99,235,0.3);">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div>
                <h2 class="text-lg md:text-xl font-black" style="color:#071428; letter-spacing:-0.01em;">
                    How to Redeem {{ $mainCategory->name }}
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">Follow these steps after receiving your code</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 md:p-8" style="border:1px solid #E2EAF8; box-shadow:0 2px 16px rgba(37,99,235,0.07);">
            <div class="rich-content">
                {!! $mainCategory->how_to_redeem !!}
            </div>
        </div>
    </div>
</section>
@endif

{{-- SEO Content --}}
@if($mainCategory->seo_content)
<section style="background:#F8FAFF; padding:64px 0; border-top:1px solid #E8EEF8;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#2563EB,#1D4ED8); box-shadow:0 4px 12px rgba(37,99,235,0.3);">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h2 class="text-lg md:text-xl font-black" style="color:#071428; letter-spacing:-0.01em;">About {{ $mainCategory->name }}</h2>
                <p class="text-xs text-gray-400 mt-0.5">Everything you need to know</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 md:p-8" style="border:1px solid #E2EAF8; box-shadow:0 2px 16px rgba(37,99,235,0.07);">
            <div class="rich-content">
                {!! $mainCategory->seo_content !!}
            </div>
        </div>
    </div>
</section>
@endif

@endsection
