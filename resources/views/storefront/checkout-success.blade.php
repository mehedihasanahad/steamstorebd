@extends('layouts.storefront')

@section('title', 'Order Confirmed — Steam Store BD')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Success animation --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-green-500/10 border-2 border-green-500 mb-4">
            <svg class="w-12 h-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">Payment Successful!</h1>
        <p class="text-gray-400">Order <span class="text-brand-400 font-mono font-bold">#{{ $order->order_number }}</span></p>
        <p class="text-gray-400 text-sm mt-1">Your codes have been sent to <span class="text-black">{{ $order->customer_email }}</span></p>
    </div>

    {{-- Codes --}}
    <div class="bg-gray-900 rounded-2xl border border-gray-700/50 p-6 mb-6">
        <h2 class="text-xl font-bold text-white mb-5">Your Gift Card Codes</h2>

        @foreach($order->items as $item)
        <div class="mb-6">
            <p class="text-gray-400 text-sm mb-3">{{ $item->giftCard->name }} × {{ $item->quantity }}</p>
            @foreach($item->orderItemCodes as $itemCode)
            <div x-data="{ copied: false }" class="flex items-center gap-3 mb-3">
                <div class="flex-1 bg-gray-800 border border-gray-600 rounded-xl px-4 py-3 font-mono text-brand-400 font-bold tracking-widest text-sm">
                    {{ $itemCode->giftCardCode->code }}
                </div>
                <button @click="navigator.clipboard.writeText('{{ $itemCode->giftCardCode->code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex-shrink-0 btn-steam px-4 py-3 rounded-xl text-sm font-semibold transition-all">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak class="text-green-800">✓ Copied!</span>
                </button>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    <div class="bg-brand-500/10 border border-brand-500/20 rounded-xl p-4 text-center mb-6">
        <p class="text-brand-400 text-sm">📧 A copy of your codes has been emailed to <strong>{{ $order->customer_email }}</strong></p>
    </div>

    {{-- Referral share nudge --}}
    @if($referralSettings['enabled'])
    <div class="rounded-2xl p-5 mb-6" style="background:linear-gradient(135deg,#0A1A35,#0D2040); border:1px solid rgba(37,99,235,0.3);">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,99,235,0.18);">
                <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-white font-bold text-sm mb-1">Share your code — earn together!</div>
                <p class="text-gray-400 text-xs leading-relaxed mb-3">Your friend uses your referral code at checkout → they get an instant discount → you earn wallet credit once their order is confirmed.</p>
                @if($referralCode)
                <div x-data="{ codeCopied: false }" class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-800 border border-gray-600 rounded-xl px-3 py-2 font-mono text-brand-400 font-bold tracking-widest text-sm text-center">
                        {{ $referralCode }}
                    </div>
                    <button @click="navigator.clipboard.writeText('{{ $referralCode }}'); codeCopied = true; setTimeout(() => codeCopied = false, 2000)"
                            class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all"
                            :class="codeCopied ? 'bg-green-600 text-white' : 'bg-brand-500 hover:bg-brand-600 text-white'">
                        <span x-show="!codeCopied">Copy Code</span>
                        <span x-show="codeCopied" x-cloak>✓ Copied!</span>
                    </button>
                    <a href="{{ route('referral.dashboard') }}"
                       class="flex-shrink-0 px-3 py-2 rounded-xl text-xs font-semibold transition-colors"
                       style="background:rgba(37,99,235,0.15); color:#93C5FD; border:1px solid rgba(37,99,235,0.3);">
                        Dashboard
                    </a>
                </div>
                @else
                <a href="{{ route('register') }}"
                   class="inline-block px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-500 hover:bg-brand-600 transition-colors">
                    Create account to get your referral code
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-4">
        <a href="{{ route('home') }}" class="flex-1 text-center bg-gray-800 hover:bg-gray-700 text-white font-semibold py-3 rounded-xl transition-colors">
            ← Back to Home
        </a>
        <a href="{{ route('shop') }}" class="flex-1 text-center btn-steam font-semibold py-3 rounded-xl transition-all hover:shadow-steam-glow">
            Buy More Cards
        </a>
    </div>
</div>
@endsection
