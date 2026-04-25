@extends('layouts.storefront')

@section('title', 'Cart — Steam Store BD')
@section('meta_description', 'Review your Steam gift card order before checkout.')

@section('content')

{{-- Breadcrumb --}}
<div style="background:#F8FAFF; border-bottom:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-400">
            <a href="{{ route('home') }}" class="hover:text-brand-500 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium" style="color:#0E1F35;">Cart</span>
        </nav>
    </div>
</div>

<div style="background:#F8FAFF; min-height:calc(100vh - 64px);">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-bold mb-8" style="color:#071428;">
        Shopping Cart
        @if(!empty($cartItems))
        <span class="text-base font-normal text-gray-400 ml-2">({{ count($cartItems) }} {{ Str::plural('item', count($cartItems)) }})</span>
        @endif
    </h1>

    @if(empty($cartItems))
    {{-- Empty state --}}
    <div class="bg-white rounded-3xl p-14 text-center" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.08);">
        <div class="text-6xl mb-4">🛒</div>
        <h2 class="text-xl font-bold mb-2" style="color:#071428;">Your cart is empty</h2>
        <p class="text-gray-400 text-sm mb-8 max-w-xs mx-auto">Browse our Steam gift cards and add them to your cart to get started.</p>
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl font-bold text-white text-sm transition-all hover:opacity-90 hover:shadow-lg"
           style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
            🎮 Browse Gift Cards
        </a>
    </div>

    @else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Cart Items --}}
        <div class="lg:col-span-2">
            <div class="space-y-4">
                @foreach($cartItems as $item)
                <div class="bg-white rounded-2xl p-5" style="border:1px solid #E8EEF8; box-shadow:0 2px 12px rgba(7,20,40,0.05);">
                    <div class="flex items-center gap-4">
                        {{-- Icon --}}
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 text-2xl"
                             style="background:linear-gradient(135deg,#071428,#0D2040);">
                            🎮
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-sm" style="color:#071428;">{{ $item['gift_card']->name }}</h3>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:#EEF4FF;color:#2563EB;">
                                    ${{ number_format($item['gift_card']->denomination_usd, 0) }} USD
                                </span>
                                <span class="text-xs text-gray-400">Qty: {{ $item['quantity'] }}</span>
                            </div>
                            <div class="text-brand-500 font-semibold text-xs mt-1">৳ {{ number_format($item['price'], 0) }} each</div>
                        </div>

                        {{-- Item total + remove --}}
                        <div class="text-right flex-shrink-0">
                            <div class="font-black text-lg" style="color:#071428;">
                                ৳ {{ number_format($item['price'] * $item['quantity'], 0) }}
                            </div>
                            <form method="POST" action="{{ route('cart.remove', $item['gift_card_id']) }}" class="mt-1.5">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-xs text-red-400 hover:text-red-600 transition-colors font-medium flex items-center gap-1 ml-auto">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-1.5 text-sm text-brand-500 hover:text-brand-600 font-medium mt-6 transition-colors">
                ← Continue Shopping
            </a>
        </div>

        {{-- Order Summary --}}
        <div class="lg:col-span-1">
            @php $total = collect($cartItems)->sum(fn($item) => $item['price'] * $item['quantity']); @endphp
            <div class="bg-white rounded-2xl p-6 sticky top-24" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.08);">
                <h2 class="font-bold text-lg mb-5" style="color:#071428;">Order Summary</h2>

                <div class="space-y-3 mb-5">
                    @foreach($cartItems as $item)
                    <div class="flex justify-between text-sm gap-2">
                        <span class="text-gray-500 truncate">
                            {{ $item['gift_card']->name }}
                            @if($item['quantity'] > 1)
                            <span class="text-gray-400">×{{ $item['quantity'] }}</span>
                            @endif
                        </span>
                        <span class="font-semibold flex-shrink-0" style="color:#071428;">
                            ৳ {{ number_format($item['price'] * $item['quantity'], 0) }}
                        </span>
                    </div>
                    @endforeach
                </div>

                <div class="pt-4 mb-6" style="border-top:1px solid #E8EEF8;">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-base" style="color:#071428;">Total</span>
                        <span class="font-black text-xl text-brand-500">৳ {{ number_format($total, 0) }}</span>
                    </div>
                </div>

                <a href="{{ route('checkout') }}"
                   class="block w-full py-4 rounded-2xl font-bold text-white text-center text-base transition-all duration-200 hover:opacity-90 hover:shadow-lg"
                   style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                    Proceed to Checkout →
                </a>

                <div class="mt-5 flex items-center justify-center gap-2 text-xs text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Secured by bKash Tokenized Checkout
                </div>
            </div>
        </div>

    </div>
    @endif

</div>
</div>

@push('styles')
<style>body { background: #F8FAFF !important; }</style>
@endpush

@endsection
