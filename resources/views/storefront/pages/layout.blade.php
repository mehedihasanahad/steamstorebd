@extends('layouts.storefront')

@push('styles')
<style>
.legal-content { font-size: 0.95rem; line-height: 1.75; color: #374151; }
.legal-content h2 { font-size: 1.15rem; font-weight: 800; color: #071428; margin: 2em 0 0.6em; line-height: 1.3; }
.legal-content h2:first-child { margin-top: 0; }
.legal-content p { margin: 0.75em 0; color: #4B5563; }
.legal-content ul, .legal-content ol { margin: 0.75em 0; padding-left: 1.5em; color: #4B5563; }
.legal-content ul { list-style-type: disc; }
.legal-content ol { list-style-type: decimal; }
.legal-content li { margin: 0.4em 0; }
.legal-content strong { font-weight: 700; color: #1F2937; }
.legal-content a { color: #2563EB; text-decoration: underline; text-underline-offset: 2px; }
.legal-content a:hover { color: #1D4ED8; }
</style>
@endpush

@section('content')

<section style="background:linear-gradient(180deg,#040D1A 0%,#091525 100%); position:relative; overflow:hidden; padding:36px 0 40px;">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(37,99,235,0.15) 0%,transparent 60%);pointer-events:none;"></div>
    <div class="grid-bg" style="position:absolute;inset:0;opacity:0.10;"></div>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <nav class="flex items-center gap-1.5 text-xs mb-4" style="color:#4A6080;" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-blue-400 transition-colors">Home</a>
            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span style="color:#8FAFC8;">@yield('heading')</span>
        </nav>
        <h1 class="text-2xl md:text-3xl font-black text-white mb-2" style="letter-spacing:-0.02em;">@yield('heading')</h1>
        @hasSection('updated')
        <p class="text-sm" style="color:#557AA0;">Last updated: @yield('updated')</p>
        @endif
    </div>
</section>

<div style="background:#F0F4FF; padding:40px 0 64px;">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <article class="legal-content bg-white rounded-2xl p-6 md:p-10" style="border:1px solid #E2EAF8; box-shadow:0 2px 16px rgba(37,99,235,0.06);">
            @yield('page_content')
        </article>
    </div>
</div>

@endsection
