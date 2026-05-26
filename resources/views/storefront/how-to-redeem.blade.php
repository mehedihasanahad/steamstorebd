@extends('layouts.storefront')

@section('title', 'How to Redeem Steam Gift Card in Bangladesh — Step by Step Guide')
@section('meta_description', 'Simple step-by-step guide to redeem your Steam gift card code in Bangladesh. Works on PC, Mac, and mobile. Add Steam Wallet funds in under 2 minutes.')

@section('content')

{{-- Hero --}}
<div class="relative overflow-hidden" style="background:linear-gradient(135deg,#071428 0%,#040D1A 100%); border-bottom:1px solid rgba(37,99,235,0.15);">
    <div class="absolute inset-0 grid-bg opacity-40 pointer-events-none"></div>
    <div class="orb absolute -top-32 -right-32 w-96 h-96 opacity-10" style="background:radial-gradient(circle,#2563EB,transparent);"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14 relative">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
            <a href="{{ route('home') }}" class="hover:text-brand-400 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-400">How to Redeem</span>
        </nav>

        <div class="flex flex-wrap items-center gap-2 mb-5">
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold bg-green-500/10 text-green-400 border border-green-500/20">✅ Works for all Steam gift cards</span>
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold bg-brand-500/10 text-brand-400 border border-brand-500/20">⏱ Under 2 minutes</span>
            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20">🌍 PC · Mac · Mobile</span>
        </div>

        <h1 class="text-3xl sm:text-4xl font-black text-white leading-tight mb-4">
            How to Redeem Your<br>
            <span style="background:linear-gradient(135deg,#60a5fa,#4B8FEF); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">Steam Gift Card</span>
        </h1>
        <p class="text-gray-400 text-base sm:text-lg max-w-2xl leading-relaxed">
            Got your code? Follow these simple steps to add funds to your Steam Wallet — no experience needed.
        </p>
    </div>
</div>

{{-- Main --}}
<div style="background:#040D1A; min-height:60vh;">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Where to find code info bar --}}
    <div class="flex items-start gap-4 rounded-2xl p-5 mb-12" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.2);">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,99,235,0.15);">
            <svg class="w-5 h-5 text-brand-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
        </div>
        <div>
            <p class="text-white font-bold text-sm mb-1">Where is my Steam code?</p>
            <p class="text-gray-400 text-sm leading-relaxed">After payment on Steam Store BD, your code is delivered <span class="text-white font-semibold">instantly via email</span>. You can also find it anytime in <a href="{{ route('orders.lookup') }}" class="text-brand-400 hover:text-brand-300 underline underline-offset-2 transition-colors">My Orders</a> after logging in.</p>
        </div>
    </div>

    {{-- Steps --}}
    <h2 class="text-xl font-bold text-white mb-8">Step-by-Step Redemption Guide</h2>

    @php
    $steps = [
        [
            'num'   => '01',
            'emoji' => '🖥️',
            'title' => 'Open Steam',
            'desc'  => 'Launch the Steam desktop app on your Windows or Mac computer. Alternatively, open any web browser and go to store.steampowered.com.',
            'tip'   => null,
        ],
        [
            'num'   => '02',
            'emoji' => '🔑',
            'title' => 'Sign In to Your Account',
            'desc'  => 'Log in with your Steam username and password. Make sure you are signed into the correct account you want to add funds to.',
            'tip'   => 'Important: The funds will go to whichever account is currently signed in.',
        ],
        [
            'num'   => '03',
            'emoji' => '👤',
            'title' => 'Click Your Username',
            'desc'  => 'Look at the top-right corner of the Steam window or website. Click on your username or profile name to open the dropdown menu.',
            'tip'   => null,
        ],
        [
            'num'   => '04',
            'emoji' => '⚙️',
            'title' => 'Select "Account Details"',
            'desc'  => 'From the dropdown menu, click "Account Details". This opens your wallet page where you can see your current Steam Wallet balance.',
            'tip'   => null,
        ],
        [
            'num'   => '05',
            'emoji' => '💰',
            'title' => 'Click "Add funds to your Steam Wallet"',
            'desc'  => 'On the Account Details page, you will see your wallet balance. Below it, click the green "Add funds to your Steam Wallet" button.',
            'tip'   => null,
        ],
        [
            'num'   => '06',
            'emoji' => '🎁',
            'title' => 'Choose "Redeem a Steam Gift Card or Wallet Code"',
            'desc'  => 'A list of payment options will appear. Scroll to the bottom and select "Redeem a Steam Gift Card or Wallet Code" — do not choose a credit card or other method.',
            'tip'   => null,
        ],
        [
            'num'   => '07',
            'emoji' => '⌨️',
            'title' => 'Enter Your Code',
            'desc'  => 'Type or paste your Steam code into the text box. Your code is 15 characters in the format XXXXX-XXXXX-XXXXX. Copy from your email to avoid typos.',
            'tip'   => 'Tip: Use Ctrl+C to copy from your email, then Ctrl+V to paste here — no typo risk!',
        ],
        [
            'num'   => '08',
            'emoji' => '✅',
            'title' => 'Click "Continue" — Done!',
            'desc'  => 'Click the Continue button and confirm. Your Steam Wallet balance will be credited immediately with the full gift card value. Start shopping!',
            'tip'   => null,
        ],
    ];
    @endphp

    <div class="relative">
        {{-- Connecting line --}}
        <div class="absolute left-[22px] top-10 bottom-10 w-0.5 hidden sm:block"
             style="background:linear-gradient(to bottom, #2563EB 0%, rgba(37,99,235,0.08) 100%);"></div>

        <div class="space-y-4">
            @foreach($steps as $step)
            <div class="flex gap-4 sm:gap-6 group">
                {{-- Circle --}}
                <div class="flex-shrink-0 w-11 h-11 rounded-full z-10 flex items-center justify-center font-black text-white text-xs"
                     style="background:linear-gradient(135deg,#2563EB,#1D4ED8); box-shadow:0 0 0 4px #040D1A, 0 4px 16px rgba(37,99,235,0.4);">
                    {{ $step['num'] }}
                </div>

                {{-- Card --}}
                <div class="flex-1 mb-0.5 rounded-2xl p-5 transition-all duration-200 group-hover:border-brand-500/30"
                     style="background:#0E1F35; border:1px solid rgba(37,99,235,0.12);">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl leading-none flex-shrink-0 mt-0.5">{{ $step['emoji'] }}</span>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-white font-bold text-sm sm:text-base mb-1.5">{{ $step['title'] }}</h3>
                            <p class="text-gray-400 text-sm leading-relaxed">{{ $step['desc'] }}</p>
                            @if($step['tip'])
                            <div class="mt-3 flex items-start gap-2 text-xs rounded-xl px-3 py-2.5"
                                 style="background:rgba(234,179,8,0.07); border:1px solid rgba(234,179,8,0.18); color:#fbbf24;">
                                <span class="flex-shrink-0 mt-0.5">💡</span>
                                <span>{{ $step['tip'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Platform support --}}
    <div class="mt-14">
        <h2 class="text-xl font-bold text-white mb-5">Works on All Platforms</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach([
                ['🖥️', 'Steam Desktop App', 'Windows & Mac', 'The fastest way — full Steam experience'],
                ['🌐', 'Web Browser', 'store.steampowered.com', 'Chrome, Firefox, Edge — any modern browser'],
                ['📱', 'Steam Mobile App', 'iOS & Android', 'Free on App Store & Google Play'],
            ] as [$icon, $title, $sub, $desc])
            <div class="rounded-2xl p-5 text-center" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.12);">
                <div class="text-4xl mb-3">{{ $icon }}</div>
                <p class="text-white font-bold text-sm">{{ $title }}</p>
                <p class="text-brand-400 text-xs font-medium mt-1">{{ $sub }}</p>
                <p class="text-gray-500 text-xs mt-2 leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Common Issues --}}
    <div class="mt-14">
        <h2 class="text-xl font-bold text-white mb-5">Common Issues & Fixes</h2>
        <div class="space-y-2">
            @php
            $issues = [
                ['"Code already redeemed" error',   'The code was already activated. Contact our support team — we verify every code before shipping and will resolve it quickly.'],
                ['"Invalid code" error',             'Double-check for typos. Copy and paste the code directly from your email. Steam codes are not case-sensitive.'],
                ['"Not available in your region"',   'Steam gift cards from Steam Store BD are valid in Bangladesh. If you see this error, please contact support immediately.'],
                ['I didn\'t receive my email',       'Check your spam or junk folder first. You can also find your code in My Orders after logging into your Steam Store BD account.'],
                ['Code box won\'t accept my code',   'Remove any spaces before or after the code. Make sure you\'re entering it in the correct "Redeem a Gift Card" section, not the payment field.'],
            ];
            @endphp
            @foreach($issues as [$question, $answer])
            <div x-data="{ open: false }" class="rounded-2xl overflow-hidden" style="border:1px solid rgba(37,99,235,0.12);">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-5 py-4 text-left transition-colors"
                        :style="open ? 'background:#0E1F35;' : 'background:#071428;'">
                    <span class="text-white text-sm font-semibold pr-4">{{ $question }}</span>
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak style="background:#071428; border-top:1px solid rgba(37,99,235,0.1);">
                    <p class="px-5 py-4 text-gray-400 text-sm leading-relaxed">{{ $answer }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Still need help --}}
    <div class="mt-8 flex items-center gap-4 rounded-2xl p-5" style="background:#0E1F35; border:1px solid rgba(37,99,235,0.15);">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,99,235,0.15);">
            <svg class="w-5 h-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        </div>
        <div class="flex-1">
            <p class="text-white font-bold text-sm mb-0.5">Still need help?</p>
            <p class="text-gray-400 text-xs">Our support team is ready to assist you.</p>
        </div>
        <a href="{{ route('contact') }}" class="btn-brand px-5 py-2.5 rounded-xl text-sm font-semibold flex-shrink-0">Contact Us</a>
    </div>

    {{-- CTA --}}
    <div class="mt-12 rounded-3xl p-8 sm:p-10 text-center overflow-hidden relative"
         style="background:linear-gradient(135deg,#071428 0%,#0D2040 100%); border:1px solid rgba(37,99,235,0.25);">
        <div class="absolute inset-0 grid-bg opacity-30 pointer-events-none"></div>
        <div class="relative">
            <div class="text-5xl mb-4">🎮</div>
            <h3 class="text-white font-black text-2xl mb-2">Ready to top up your Steam Wallet?</h3>
            <p class="text-gray-400 text-sm mb-7 max-w-md mx-auto">Browse Steam gift cards from $5 to $100 USD. Pay with bKash — code delivered instantly.</p>
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 btn-brand px-8 py-3.5 rounded-2xl font-bold text-base">
                Browse Gift Cards →
            </a>
        </div>
    </div>

</div>
</div>

@endsection
