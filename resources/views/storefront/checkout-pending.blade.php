@extends('layouts.storefront')

@section('title', 'Order Under Review — Steam Store BD')

@section('content')

<div style="background:#F8FAFF; min-height:calc(100vh - 64px); display:flex; align-items:center;">
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16 w-full">

    {{-- Status card --}}
    <div class="bg-white rounded-3xl overflow-hidden text-center" style="border:1px solid #E8EEF8; box-shadow:0 8px 40px rgba(7,20,40,0.10);">

        {{-- Top banner --}}
        <div class="px-8 pt-10 pb-8" style="background:linear-gradient(135deg,#FFF8E6 0%,#FFFBF0 100%); border-bottom:1px solid #FDE68A;">
            <div class="w-20 h-20 rounded-full flex items-center justify-center text-4xl mx-auto mb-4"
                 style="background:rgba(234,179,8,0.15); border:2px solid rgba(234,179,8,0.3);">
                ⏳
            </div>
            <h1 class="text-2xl font-black mb-2" style="color:#071428;">Order Under Review</h1>
            <p class="text-gray-500 text-sm leading-relaxed max-w-sm mx-auto">
                We've received your order and are verifying your payment. Your gift card code will be sent to your email within <strong class="text-gray-700">2–5 minutes</strong>.
            </p>
        </div>

        {{-- Order details --}}
        <div class="px-8 py-6 space-y-4">

            {{-- Order number --}}
            <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full" style="background:#EEF4FF; border:1px solid rgba(37,99,235,0.2);">
                <span class="text-sm text-gray-400 font-medium">Order ID:</span>
                <span class="font-bold text-sm" style="color:#2563EB;">{{ $order->order_number }}</span>
            </div>

            {{-- Info grid --}}
            <div class="grid grid-cols-2 gap-3 text-sm mt-4">
                <div class="rounded-xl p-4 text-left" style="background:#F8FAFF; border:1px solid #E8EEF8;">
                    <div class="text-gray-400 text-xs mb-1">Payment Method</div>
                    <div class="font-semibold" style="color:#071428;">{{ $order->paymentMethodLabel() }}</div>
                </div>
                <div class="rounded-xl p-4 text-left" style="background:#F8FAFF; border:1px solid #E8EEF8;">
                    <div class="text-gray-400 text-xs mb-1">Amount</div>
                    <div class="font-bold" style="color:#2563EB;">৳ {{ number_format($order->total_bdt, 0) }}</div>
                </div>
                <div class="rounded-xl p-4 text-left col-span-2" style="background:#F8FAFF; border:1px solid #E8EEF8;">
                    <div class="text-gray-400 text-xs mb-1">Transaction ID</div>
                    <div class="font-mono font-semibold" style="color:#071428;">{{ $order->send_money_trx_id }}</div>
                </div>
            </div>

            {{-- Items --}}
            <div class="text-left rounded-xl overflow-hidden" style="border:1px solid #E8EEF8;">
                @foreach($order->items as $item)
                <div class="flex items-center justify-between px-4 py-3 {{ !$loop->last ? 'border-b' : '' }}" style="border-color:#E8EEF8;">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-base flex-shrink-0"
                             style="background:linear-gradient(135deg,#071428,#0D2040);">🎮</div>
                        <div>
                            <div class="text-sm font-medium" style="color:#071428;">{{ $item->giftCard->name }}</div>
                            <div class="text-xs text-gray-400">× {{ $item->quantity }}</div>
                        </div>
                    </div>
                    <span class="font-semibold text-sm" style="color:#2563EB;">৳ {{ number_format($item->subtotal_bdt, 0) }}</span>
                </div>
                @endforeach
            </div>

            {{-- Email notice --}}
            <div class="flex items-start gap-3 rounded-xl p-4 text-left" style="background:#F0FDF4; border:1px solid #BBF7D0;">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
                <div>
                    <div class="text-sm font-semibold text-green-800">Check your email</div>
                    <div class="text-xs text-green-700 mt-0.5">
                        A confirmation has been sent to <strong>{{ $order->customer_email }}</strong>.<br>
                        Your gift card code will arrive in a separate email once payment is verified.
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer actions --}}
        <div class="px-8 pb-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('home') }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm transition-all hover:opacity-90"
               style="background:#EEF4FF; color:#2563EB;">
                ← Back to Home
            </a>
            <a href="{{ route('shop') }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold text-sm text-white transition-all hover:opacity-90"
               style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                🛒 Buy More Cards
            </a>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        Need help? <a href="{{ route('contact') }}" class="text-brand-500 hover:underline">Contact Support</a>
    </p>

</div>
</div>

@push('styles')
<style>body { background: #F8FAFF !important; }</style>
@endpush

@endsection
