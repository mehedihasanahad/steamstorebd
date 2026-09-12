@extends('layouts.storefront')

@section('title', 'Wholesale Gift Card Reseller Program — ' . site_setting('site_name', 'Steam Store BD'))
@section('meta_description', 'Join the ' . site_setting('site_name', 'Steam Store BD') . ' reseller program: wholesale gift card prices, priority delivery and bulk stock for sellers in Bangladesh.')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
<style>
    /* Tom Select, themed to match the other inputs on this form. Shared by the
       single-select (platform) and the multi-select (categories).

       The bare <select> is styled too, so the moment before the script boots
       still looks on-brand instead of showing a native control. The
       :not(.ts-hidden-accessible) guard drops those styles the instant Tom
       Select takes over — it keeps the original select in the DOM, visually
       hidden but still focusable, so the HTML5 required check keeps working. */
    select.rs-select:not(.ts-hidden-accessible) {
        width: 100%; min-height: 48px; padding: 12px 16px; font-size: 14px;
        color: #fff; background: #0A1828; border: 1px solid rgba(37,99,235,0.2);
        border-radius: 12px;
    }

    /* ── shared ── */
    .ts-wrapper.rs-select { padding: 0; border: 0; background: none; min-height: 0; }
    .ts-wrapper.rs-select .ts-control {
        background: #0A1828; border: 1px solid rgba(37,99,235,0.2); border-radius: 12px;
        box-shadow: none; min-height: 48px; color: #fff; font-size: 14px;
        transition: border-color .15s;
    }
    .ts-wrapper.rs-select.focus .ts-control { border-color: rgba(37,99,235,0.6); box-shadow: none; }
    .ts-wrapper.rs-select.input-active .ts-control { background: #0A1828; }
    .ts-wrapper.rs-select .ts-control > input { color: #fff; font-size: 14px; margin: 0; }
    .ts-wrapper.rs-select .ts-control > input::placeholder { color: #557AA0; }

    /* ── single (Where Do You Sell?) ── */
    .ts-wrapper.rs-select.single .ts-control { padding: 12px 16px; }
    .ts-wrapper.rs-select.single .ts-control > .item { color: #fff; }
    .ts-wrapper.rs-select.single .ts-control:after { border-top-color: #557AA0; }
    .ts-wrapper.rs-select.single.dropdown-active .ts-control:after { border-bottom-color: #557AA0; }

    /* ── multi (gift card categories) ── */
    .ts-wrapper.rs-select.multi .ts-control { padding: 8px 12px; gap: 6px; }
    .ts-wrapper.rs-select.multi .ts-control > input { min-width: 8rem; }
    .ts-wrapper.rs-select.multi .ts-control > .item {
        background: rgba(37,99,235,0.18); border: 1px solid rgba(37,99,235,0.5);
        color: #fff; border-radius: 9999px; padding: 5px 10px 5px 14px;
        font-size: 13px; font-weight: 600; display: inline-flex; align-items: center;
    }
    .ts-wrapper.rs-select.plugin-remove_button .item .remove {
        border-left: 0; color: #9BB5D5; padding: 0 6px 0 8px; font-size: 15px; line-height: 1;
    }
    .ts-wrapper.rs-select.plugin-remove_button .item .remove:hover { color: #fff; background: none; }

    /* ── dropdown ── */
    .ts-wrapper.rs-select .ts-dropdown {
        background: #0A1828; border: 1px solid rgba(37,99,235,0.3);
        border-radius: 12px; margin-top: 6px; box-shadow: 0 8px 28px rgba(0,0,0,0.5);
        overflow: hidden;
    }
    .ts-wrapper.rs-select .ts-dropdown .option {
        color: #8BAFD4; font-size: 14px; padding: 11px 16px;
    }
    .ts-wrapper.rs-select .ts-dropdown .option.active { background: rgba(37,99,235,0.2); color: #fff; }
    .ts-wrapper.rs-select .ts-dropdown .option.selected { color: #fff; }
    .ts-wrapper.rs-select .ts-dropdown .no-results { color: #557AA0; font-size: 14px; padding: 11px 16px; }
</style>
@endpush

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Leave the native selects in place if the CDN script did not load —
        // they still submit selling_platform and gift_card_types[] on their own.
        if (typeof TomSelect === 'undefined') {
            return;
        }

        var noResults = function (label) {
            return function () {
                return '<div class="no-results">No ' + label + ' found.</div>';
            };
        };

        var platform = document.getElementById('rs-platform');

        if (platform) {
            new TomSelect(platform, {
                maxItems: 1,
                // The empty prompt option exists for the no-JS fallback; keep it
                // out of the dropdown and show it as placeholder text instead.
                allowEmptyOption: false,
                placeholder: 'Search or select where you sell…',
                render: { no_results: noResults('platform') },
            });
        }

        var types = document.getElementById('rs-types');

        if (types) {
            new TomSelect(types, {
                plugins: ['remove_button'],
                maxItems: null,
                hideSelected: true,
                closeAfterSelect: false,
                placeholder: 'Search and select categories…',
                render: { no_results: noResults('category') },
            });
        }
    });
</script>
@endpush

@section('content')

@php
    $submittedNumber = session('reseller_application_number');
    $platforms       = $program->platformOptions();
    $brandOptions    = $program->giftCardTypeOptions();
    $benefits        = $program->benefits();
    $oldTypes        = array_values((array) old('gift_card_types', []));
@endphp

{{-- ══ HERO ══ --}}
<div class="relative overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#040D1A 100%); border-bottom:1px solid rgba(37,99,235,0.15);">
    <div class="absolute inset-0 grid-bg opacity-40 pointer-events-none"></div>
    <div class="orb absolute -top-32 -right-32 w-96 h-96 opacity-10" style="background:radial-gradient(circle,#2563EB,transparent);"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 relative">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-brand-400 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-400">Become a Reseller</span>
        </nav>

        <div class="flex flex-wrap items-center gap-2 mb-5">
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold" style="background:rgba(37,99,235,0.1); color:#4B8FEF; border:1px solid rgba(37,99,235,0.2);">🤝 Reseller Program</span>
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold" style="background:rgba(34,197,94,0.1); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">⚡ Reply {{ $program->responseTime() }}</span>
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold" style="background:rgba(168,85,247,0.1); color:#c084fc; border:1px solid rgba(168,85,247,0.2);">💰 Wholesale Pricing</span>
        </div>

        <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white leading-tight mb-4">
            {{ $program->heroTitle() }}
        </h1>
        <p class="text-gray-400 text-base sm:text-lg max-w-2xl leading-relaxed mb-8">
            {{ $program->heroSubtitle() }}
        </p>

        <a href="#apply"
           class="inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl font-black text-white text-base transition-all duration-200 hover:opacity-90 hover:scale-[1.03]"
           style="background:linear-gradient(135deg,#2563EB,#1D4ED8); box-shadow:0 0 40px rgba(37,99,235,0.45);">
            Apply Now — It&rsquo;s Free
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        </a>

        <div class="flex flex-wrap items-center gap-8 sm:gap-12 pt-10 mt-10" style="border-top:1px solid rgba(255,255,255,0.07);">
            @foreach([['No Fee','To join the program'],['24/7','Stock availability'],['100%','Genuine codes']] as [$val,$label])
            <div>
                <div class="font-black text-white text-xl md:text-2xl leading-none">{{ $val }}</div>
                <div class="text-gray-500 text-xs mt-1">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ══ BENEFITS ══ --}}
<section style="background:#040D1A; padding:72px 0;">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold mb-4"
                 style="background:rgba(37,99,235,0.15); color:#60A5FA; border:1px solid rgba(37,99,235,0.25);">
                WHY RESELL WITH US
            </div>
            <h2 class="text-2xl md:text-3xl font-black mb-3 text-white" style="letter-spacing:-0.02em;">
                Built For People Who Sell
            </h2>
            <p class="text-sm md:text-base max-w-xl mx-auto" style="color:#8BAFD4;">
                Everything you need to serve your own customers faster and at a better margin.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($benefits as $benefit)
            <div class="rounded-2xl p-6 transition-all duration-200"
                 style="background:#0E1F35; border:1px solid rgba(37,99,235,0.15);"
                 onmouseover="this.style.borderColor='rgba(37,99,235,0.45)'; this.style.transform='translateY(-3px)';"
                 onmouseout="this.style.borderColor='rgba(37,99,235,0.15)'; this.style.transform='translateY(0)';">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl mb-4"
                     style="background:rgba(37,99,235,0.15); border:1px solid rgba(37,99,235,0.25);">
                    {{ $benefit['icon'] }}
                </div>
                <h3 class="font-bold text-white mb-2 text-base">{{ $benefit['title'] }}</h3>
                @if($benefit['description'] !== '')
                <p class="text-sm leading-relaxed" style="color:#8BAFD4;">{{ $benefit['description'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ HOW IT WORKS ══ --}}
<section style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%); padding:72px 0; position:relative; overflow:hidden;">
    <div style="position:absolute;top:-80px;right:-80px;width:320px;height:320px;border-radius:50%;background:rgba(37,99,235,0.07);pointer-events:none;"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="text-center mb-12">
            <h2 class="text-2xl md:text-3xl font-black mb-3 text-white" style="letter-spacing:-0.02em;">
                Three Steps To Get Started
            </h2>
            <p class="text-sm md:text-base max-w-xl mx-auto" style="color:#8BAFD4;">
                No paperwork, no joining fee. Just a quick conversation to make sure we are a good fit.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                ['icon'=>'📝','step'=>'1','title'=>'Submit Your Application','desc'=>'Fill in the short form below. It takes less than two minutes — no documents needed.'],
                ['icon'=>'📞','step'=>'2','title'=>'We Verify You','desc'=>'Our team reviews your details and contacts you on WhatsApp to confirm your business.'],
                ['icon'=>'🚀','step'=>'3','title'=>'Start Selling','desc'=>'Once approved you get your reseller price list and can place your first bulk order right away.'],
            ] as $step)
            <div class="rounded-2xl p-6 text-center relative"
                 style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4"
                     style="background:rgba(37,99,235,0.2); border:1px solid rgba(37,99,235,0.3);">
                    {{ $step['icon'] }}
                </div>
                <div class="absolute top-4 right-4 font-black opacity-20 text-white" style="font-size:32px;">{{ $step['step'] }}</div>
                <h3 class="font-bold text-white mb-2">{{ $step['title'] }}</h3>
                <p class="text-sm" style="color:#8BAFD4;">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ APPLICATION FORM / SUCCESS ══ --}}
<section id="apply" style="background:#040D1A; padding:72px 0; scroll-margin-top:80px;">
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

@if($submittedNumber)
    {{-- ── Success state ── --}}
    <div class="rounded-3xl p-8 sm:p-10 text-center" style="background:#071428; border:1px solid rgba(34,197,94,0.3);">
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6"
             style="background:rgba(34,197,94,0.12); border:1px solid rgba(34,197,94,0.3);">
            <svg class="w-10 h-10 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>

        <h2 class="text-2xl sm:text-3xl font-black text-white mb-3">Application Received! 🎉</h2>
        <p class="text-gray-400 text-base leading-relaxed max-w-lg mx-auto mb-6">
            Thanks for applying. We have sent a confirmation to your email, and our team will review your
            application and get back to you <strong class="text-green-400">{{ $program->responseTime() }}</strong>.
        </p>

        <div class="inline-flex flex-col items-center gap-1 px-6 py-4 rounded-2xl mb-8"
             style="background:rgba(37,99,235,0.08); border:1px solid rgba(37,99,235,0.25);">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color:#8BAFD4;">Your Application ID</span>
            <span class="text-xl font-black font-mono tracking-widest text-white">{{ $submittedNumber }}</span>
        </div>

        <div class="rounded-2xl p-6 text-left mb-8" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.15);">
            <p class="text-white font-bold text-sm mb-4">What happens next</p>
            <ol class="space-y-3">
                @foreach([
                    'Our team reviews your application details',
                    'We message you on WhatsApp to verify your business',
                    'Once approved, you receive your reseller price list by email',
                ] as $i => $next)
                <li class="flex items-start gap-3 text-sm" style="color:#8BAFD4;">
                    <span class="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold text-brand-400"
                          style="background:rgba(37,99,235,0.15); border:1px solid rgba(37,99,235,0.25);">{{ $i + 1 }}</span>
                    {{ $next }}
                </li>
                @endforeach
            </ol>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl font-bold text-white text-sm transition-all hover:opacity-90"
               style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                Continue Shopping
            </a>
            <a href="{{ route('contact') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl font-semibold text-sm transition-all hover:bg-white/5"
               style="color:#9BB5D5; border:1px solid rgba(255,255,255,0.13);">
                Contact Support
            </a>
        </div>
    </div>
@else
    {{-- ── Application form ── --}}
    <div class="text-center mb-10">
        <h2 class="text-2xl md:text-3xl font-black mb-3 text-white" style="letter-spacing:-0.02em;">
            Apply To Become a Reseller
        </h2>
        <p class="text-sm md:text-base max-w-xl mx-auto" style="color:#8BAFD4;">
            Takes under two minutes. We reply {{ $program->responseTime() }}.
        </p>
    </div>

    @if($errors->any())
    <div class="flex items-start gap-4 rounded-2xl p-5 mb-8" style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25);">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(239,68,68,0.15);">
            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-red-400 font-bold text-sm mb-0.5">Please check the form</p>
            <p class="text-red-300/80 text-sm">{{ $errors->count() === 1 ? 'One field needs your attention.' : $errors->count() . ' fields need your attention.' }}</p>
        </div>
    </div>
    @endif

    <div class="rounded-3xl p-6 sm:p-8" style="background:#071428; border:1px solid rgba(37,99,235,0.15);">
        <form method="POST" action="{{ route('reseller.submit') }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <div class="space-y-6">

                {{-- Name --}}
                <div>
                    <label for="rs-name" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                        Your Name <span class="text-red-400">*</span>
                    </label>
                    <input id="rs-name" type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                           placeholder="e.g. Rahim Uddin"
                           class="w-full rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none transition-colors"
                           style="background:#0A1828; border:1px solid rgba(37,99,235,0.2);"
                           onfocus="this.style.borderColor='rgba(37,99,235,0.6)'" onblur="this.style.borderColor='rgba(37,99,235,0.2)'">
                    @error('name') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="rs-email" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                        Email Address <span class="text-red-400">*</span>
                    </label>
                    <input id="rs-email" type="email" name="email" value="{{ old('email') }}" required maxlength="150"
                           placeholder="your@email.com"
                           class="w-full rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none transition-colors"
                           style="background:#0A1828; border:1px solid rgba(37,99,235,0.2);"
                           onfocus="this.style.borderColor='rgba(37,99,235,0.6)'" onblur="this.style.borderColor='rgba(37,99,235,0.2)'">
                    <p class="text-gray-600 text-xs mt-1.5">We send your approval and price list here.</p>
                    @error('email') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                {{-- Phone + WhatsApp --}}
                {{-- Grid and its shared helper line are wrapped together so the
                     form's space-y rhythm treats them as one field group. --}}
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="rs-phone" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                Phone Number <span class="text-red-400">*</span>
                            </label>
                            <input id="rs-phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="20"
                                   inputmode="tel" placeholder="01712345678"
                                   class="w-full rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none transition-colors"
                                   style="background:#0A1828; border:1px solid rgba(37,99,235,0.2);"
                                   onfocus="this.style.borderColor='rgba(37,99,235,0.6)'" onblur="this.style.borderColor='rgba(37,99,235,0.2)'">
                            @error('phone') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="rs-whatsapp" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                WhatsApp Number <span class="text-red-400">*</span>
                            </label>
                            <input id="rs-whatsapp" type="tel" name="whatsapp_number" value="{{ old('whatsapp_number') }}" required maxlength="20"
                                   inputmode="tel" placeholder="01712345678"
                                   class="w-full rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none transition-colors"
                                   style="background:#0A1828; border:1px solid rgba(37,99,235,0.2);"
                                   onfocus="this.style.borderColor='rgba(37,99,235,0.6)'" onblur="this.style.borderColor='rgba(37,99,235,0.2)'">
                            @error('whatsapp_number') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <p class="text-gray-600 text-xs mt-2">Bangladeshi mobile numbers only. We verify every applicant on WhatsApp.</p>
                </div>

                {{-- Selling platform --}}
                <div>
                    <label for="rs-platform" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                        Where Do You Sell? <span class="text-red-400">*</span>
                    </label>
                    <select id="rs-platform" name="selling_platform" required
                            autocomplete="off"
                            class="rs-select">
                        <option value="" disabled {{ old('selling_platform') ? '' : 'selected' }}>Select your selling platform</option>
                        @foreach($platforms as $value => $label)
                        <option value="{{ $value }}" @selected(old('selling_platform') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-gray-600 text-xs mt-2">Type to search, or pick from the list.</p>
                    @error('selling_platform') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                {{-- Gift card types --}}
                <div>
                    <label for="rs-types" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                        Which Gift Cards Do You Want To Sell? <span class="text-red-400">*</span>
                    </label>
                    <select id="rs-types" name="gift_card_types[]" multiple required
                            autocomplete="off"
                            placeholder="Search and select categories…"
                            class="rs-select">
                        @foreach($brandOptions as $slug => $label)
                        <option value="{{ $slug }}" @selected(in_array($slug, $oldTypes, true))>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-gray-600 text-xs mt-2">Type to search. Pick every category you plan to stock — you can change this later.</p>
                    @error('gift_card_types') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                    @error('gift_card_types.*') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" :disabled="loading"
                    class="w-full mt-8 relative overflow-hidden rounded-2xl font-bold py-4 text-white text-sm transition-all"
                    style="background:linear-gradient(135deg,#2563EB,#1D4ED8); box-shadow:0 4px 20px rgba(37,99,235,0.35);"
                    :class="loading ? 'opacity-75 cursor-wait' : 'hover:shadow-[0_6px_28px_rgba(37,99,235,0.5)]'">
                <span x-show="!loading" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Submit Application
                </span>
                <span x-show="loading" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    Submitting…
                </span>
            </button>

            <p class="text-center text-gray-600 text-xs mt-4">
                Free to apply. We never share your details — see our
                <a href="{{ route('faq') }}" class="text-gray-500 hover:text-gray-400 underline underline-offset-2 transition-colors">FAQ &amp; policies</a>.
            </p>
        </form>
    </div>
@endif

</div>
</section>

{{-- ══ FAQ ══ --}}
<section style="background:#071428; padding:64px 0; border-top:1px solid rgba(37,99,235,0.12);">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl md:text-2xl font-black text-white mb-8 text-center">Reseller FAQ</h2>

        <div class="space-y-3" x-data="{ open: null }">
            @foreach([
                ['q'=>'Is there any fee to join?','a'=>'No. Applying and joining the reseller program is completely free. You only pay for the gift cards you order.'],
                ['q'=>'How long does approval take?','a'=>'We review every application manually and reply ' . $program->responseTime() . '. We will contact you on WhatsApp to verify your business before approving.'],
                ['q'=>'Do I need a trade license?','a'=>'No trade license or paperwork is required. We just need to confirm you are a genuine seller — a Facebook page, shop or gaming zone with real selling history is enough.'],
                ['q'=>'What is the minimum order?','a'=>'Reseller pricing starts from bulk quantities. We will share the exact tiers and minimums with you once your application is approved.'],
                ['q'=>'How do I pay for reseller orders?','a'=>'The same way as regular orders — bKash, Nagad or Rocket. Approved resellers get priority verification so your codes arrive faster.'],
                ['q'=>'What if my application is declined?','a'=>'We will email you the reason. You are welcome to fix it and apply again at any time, and you can keep buying from us as a regular customer.'],
            ] as $i => $faq)
            <div class="rounded-2xl overflow-hidden" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.15);">
                <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}"
                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-white/5">
                    <span class="text-white font-semibold text-sm">{{ $faq['q'] }}</span>
                    <svg class="w-4 h-4 text-brand-400 flex-shrink-0 transition-transform duration-200"
                         :style="open === {{ $i }} ? 'transform:rotate(180deg)' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}" x-cloak>
                    <p class="px-5 pb-4 text-sm leading-relaxed" style="color:#8BAFD4;">{{ $faq['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
