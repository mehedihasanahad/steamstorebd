@extends('layouts.storefront')

@section('title', 'Contact Us — Steam Store BD | Steam Gift Card Bangladesh Support')
@section('meta_description', 'Contact Steam Store BD for help with your Steam gift card order in Bangladesh. Send us a message and get a response within 5–10 minutes.')
@section('meta_keywords', 'contact steam store bd, steam gift card support bangladesh, steam store bd help, steam gift card problem bangladesh')

@section('content')

{{-- Hero --}}
<div class="relative overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#040D1A 100%); border-bottom:1px solid rgba(37,99,235,0.15);">
    <div class="absolute inset-0 grid-bg opacity-40 pointer-events-none"></div>
    <div class="orb absolute -top-32 -right-32 w-96 h-96 opacity-10" style="background:radial-gradient(circle,#2563EB,transparent);"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14 relative">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-brand-400 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-400">Contact Us</span>
        </nav>

        <div class="flex flex-wrap items-center gap-2 mb-5">
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold bg-green-500/10 text-green-400 border border-green-500/20">⚡ Response within 5–10 min</span>
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold bg-brand-500/10 text-brand-400 border border-brand-500/20">🛡️ Secure & Private</span>
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20">💬 Friendly Support</span>
        </div>

        <h1 class="text-3xl sm:text-4xl font-black text-white leading-tight mb-4">
            Get in Touch<br>
            <span style="background:linear-gradient(135deg,#60a5fa,#4B8FEF); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">We're Here to Help</span>
        </h1>
        <p class="text-gray-400 text-base sm:text-lg max-w-2xl leading-relaxed">
            Have a question about your order or gift card? Send us a message and we'll get back to you fast.
        </p>
    </div>
</div>

{{-- Main --}}
<div style="background:#040D1A; min-height:60vh;">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Success message --}}
    @if(session('success'))
    <div class="flex items-start gap-4 rounded-2xl p-5 mb-8" style="background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.25);">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(34,197,94,0.15);">
            <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div>
            <p class="text-green-400 font-bold text-sm mb-0.5">Message Sent!</p>
            <p class="text-green-300/80 text-sm">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

        {{-- Left: Info cards --}}
        <div class="lg:col-span-2 flex flex-col gap-4">

            {{-- Response time --}}
            <div class="rounded-2xl p-5" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.15);">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,99,235,0.15);">
                        <svg class="w-4 h-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-white font-bold text-sm">Response Time</p>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed">We typically reply within <span class="text-green-400 font-semibold">5–10 minutes</span> during business hours.</p>
            </div>

            {{-- Order issues --}}
            <div class="rounded-2xl p-5" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.15);">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,99,235,0.15);">
                        <svg class="w-4 h-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="text-white font-bold text-sm">Order Issues?</p>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed mb-3">For faster help with an existing order, check your order status first.</p>
                <a href="{{ route('orders.lookup') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-400 hover:text-brand-300 transition-colors">
                    Track My Order
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            {{-- How to redeem --}}
            <div class="rounded-2xl p-5" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.15);">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,99,235,0.15);">
                        <svg class="w-4 h-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-white font-bold text-sm">New to Steam?</p>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed mb-3">Learn how to redeem your Steam gift card code step by step.</p>
                <a href="{{ route('how-to-redeem') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-400 hover:text-brand-300 transition-colors">
                    Redemption Guide
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            {{-- Common topics --}}
            <div class="rounded-2xl p-5" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.15);">
                <p class="text-white font-bold text-sm mb-3">Common Topics</p>
                <ul class="space-y-2">
                    @foreach(["Didn't receive my code", "Wrong card amount", "Payment not confirmed", "Code already redeemed", "Other question"] as $topic)
                    <li class="flex items-center gap-2 text-gray-400 text-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-500 flex-shrink-0"></span>
                        {{ $topic }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Right: Contact form --}}
        <div class="lg:col-span-3">
            <div class="rounded-2xl p-6 sm:p-8 h-full" style="background:#071428; border:1px solid rgba(37,99,235,0.15);">
                <div class="flex items-center gap-3 mb-7">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(37,99,235,0.15);">
                        <svg class="w-5 h-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-white font-black text-lg">Send a Message</h2>
                        <p class="text-gray-500 text-xs">We'll reply within 5–10 minutes</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('contact.submit') }}" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Your Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   placeholder="e.g. Rahim Uddin"
                                   class="w-full rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none transition-colors"
                                   style="background:#0A1828; border:1px solid rgba(37,99,235,0.2);"
                                   onfocus="this.style.borderColor='rgba(37,99,235,0.6)'" onblur="this.style.borderColor='rgba(37,99,235,0.2)'">
                            @error('name') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   placeholder="your@email.com"
                                   class="w-full rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none transition-colors"
                                   style="background:#0A1828; border:1px solid rgba(37,99,235,0.2);"
                                   onfocus="this.style.borderColor='rgba(37,99,235,0.6)'" onblur="this.style.borderColor='rgba(37,99,235,0.2)'">
                            @error('email') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Your Message</label>
                            <textarea name="message" rows="6" required
                                      placeholder="Describe your issue or question in detail…"
                                      class="w-full rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none transition-colors resize-none"
                                      style="background:#0A1828; border:1px solid rgba(37,99,235,0.2);"
                                      onfocus="this.style.borderColor='rgba(37,99,235,0.6)'" onblur="this.style.borderColor='rgba(37,99,235,0.2)'">{{ old('message') }}</textarea>
                            @error('message') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full mt-6 relative overflow-hidden rounded-2xl font-bold py-3.5 text-white text-sm transition-all"
                            style="background:linear-gradient(135deg,#2563EB,#1D4ED8); box-shadow:0 4px 20px rgba(37,99,235,0.35);"
                            :class="loading ? 'opacity-75 cursor-wait' : 'hover:shadow-[0_6px_28px_rgba(37,99,235,0.5)]'">
                        <span x-show="!loading" class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            Send Message
                        </span>
                        <span x-show="loading" x-cloak class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            Sending…
                        </span>
                    </button>

                    <p class="text-center text-gray-600 text-xs mt-4">
                        By sending a message you agree to our
                        <a href="{{ route('faq') }}" class="text-gray-500 hover:text-gray-400 underline underline-offset-2 transition-colors">FAQ & policies</a>.
                    </p>
                </form>
            </div>
        </div>
    </div>

</div>
</div>

@endsection
