@extends('layouts.storefront')

@section('title', 'Page Not Found — Steam Store BD')
@section('meta_description', 'The page you are looking for does not exist or has moved.')
@section('robots', 'noindex, follow')

@section('content')

<section style="background:linear-gradient(180deg,#040D1A 0%,#091525 100%); position:relative; overflow:hidden; padding:72px 0 80px;">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 50% 30%,rgba(37,99,235,0.18) 0%,transparent 60%);pointer-events:none;"></div>
    <div class="grid-bg" style="position:absolute;inset:0;opacity:0.10;"></div>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center">
        <p class="text-xs font-bold uppercase tracking-[0.18em] mb-3" style="color:#4B8FEF;">Error 404</p>
        <h1 class="text-3xl md:text-4xl font-black text-white mb-3" style="letter-spacing:-0.02em;">This page doesn't exist</h1>
        <p class="text-sm md:text-base mb-8" style="color:#8BAFD4;">The gift card or page you're looking for may have moved or is no longer available.</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}"
               class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl font-bold text-white text-sm w-full sm:w-auto"
               style="background:linear-gradient(135deg,#2563EB,#1D4ED8);box-shadow:0 0 28px rgba(37,99,235,0.40);">
                Browse Gift Cards
            </a>
            <a href="{{ route('contact') }}"
               class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl font-semibold text-sm w-full sm:w-auto transition-colors hover:bg-white/10"
               style="color:#9BB5D5;border:1px solid rgba(255,255,255,0.13);">
                Contact Support
            </a>
        </div>
    </div>
</section>

@if(($footerBrands ?? collect())->isNotEmpty())
<section style="background:#F8FAFF; padding:48px 0 64px; border-top:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-lg font-black mb-5 text-center" style="color:#071428;">Popular brands</h2>
        <div class="flex flex-wrap justify-center gap-3">
            @foreach($footerBrands as $brand)
            <a href="{{ route('brand', $brand->slug) }}"
               class="px-5 py-2.5 rounded-xl bg-white text-sm font-semibold transition-colors hover:text-blue-600"
               style="border:1px solid #DBEAFE; color:#071428;">
                {{ $brand->name }}
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
