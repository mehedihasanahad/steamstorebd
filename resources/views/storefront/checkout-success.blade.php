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
