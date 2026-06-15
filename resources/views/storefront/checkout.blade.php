@extends('layouts.storefront')

@section('title', 'Checkout — Steam Store BD')

@section('content')

{{-- Breadcrumb --}}
<div style="background:#F8FAFF; border-bottom:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-400">
            <a href="{{ route('home') }}" class="hover:text-brand-500 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('cart') }}" class="hover:text-brand-500 transition-colors">Cart</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium" style="color:#0E1F35;">Checkout</span>
        </nav>
    </div>
</div>

@php
    $bkashSendNumber  = config('services.payment.bkash_send_money_number', '');
    $nagadSendNumber  = config('services.payment.nagad_send_money_number', '');
    $rocketSendNumber = config('services.payment.rocket_send_money_number', '');
    $defaultMethod    = old('payment_method', $paymentMethods[0] ?? 'bkash_online');
    $subtotal         = collect($cartItems)->sum(fn($i) => $i['price'] * $i['quantity']);
@endphp

<div style="background:#F8FAFF; min-height:calc(100vh - 64px);">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
     x-data="{
         paymentMethod: '{{ $defaultMethod }}',
         loading: false,

         subtotal: {{ $subtotal }},
         referralDiscount: 0,
         referralCode: '',
         referralMsg: '',
         referralValid: false,
         referralLoading: false,

         useWallet: false,
         walletBalance: {{ $walletBalance }},

         get isSendMoney() { return this.paymentMethod !== 'bkash_online'; },
         get formAction() {
             return this.isSendMoney
                 ? '{{ route('checkout.manual') }}'
                 : '{{ route('checkout.initiate') }}';
         },
         get walletApplied() {
             if (!this.useWallet) return 0;
             const remaining = Math.max(0, this.subtotal - this.referralDiscount);
             return Math.min(this.walletBalance, remaining);
         },
         get orderTotal() {
             return Math.max(0, this.subtotal - this.referralDiscount - this.walletApplied);
         },

         async applyReferral() {
             if (!this.referralCode.trim()) return;
             this.referralLoading = true;
             this.referralMsg = '';
             try {
                 const res = await fetch('{{ route('referral.apply') }}', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                     body: JSON.stringify({ code: this.referralCode, order_total: this.subtotal }),
                 });
                 const data = await res.json();
                 if (data.success) {
                     this.referralDiscount = data.discount;
                     this.referralValid = true;
                     this.referralMsg = data.message;
                 } else {
                     this.referralDiscount = 0;
                     this.referralValid = false;
                     this.referralMsg = data.message;
                 }
             } catch(e) {
                 this.referralMsg = 'Something went wrong. Please try again.';
             }
             this.referralLoading = false;
         },

         removeReferral() {
             this.referralDiscount = 0;
             this.referralCode = '';
             this.referralMsg = '';
             this.referralValid = false;
         }
     }">

    <h1 class="text-2xl font-bold mb-8" style="color:#071428;">Checkout</h1>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6">
        <ul class="text-red-600 text-sm space-y-1">
            @foreach($errors->all() as $error)
            <li class="flex items-start gap-2"><span class="mt-0.5">•</span> {{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Customer Form --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Payment method tabs (only shown if more than one method enabled) --}}
            @if(count($paymentMethods) > 1)
            <div class="bg-white rounded-2xl p-5" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);">
                <h2 class="text-sm font-bold mb-4" style="color:#071428;">Select Payment Method</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($paymentMethods as $method)
                    @php
                        $label = match($method) {
                            'bkash_online'      => ['logo' => 'bkash-logo.png',  'name' => 'bKash Online',     'sub' => 'Tokenized Payment',  'color' => '#E2136E', 'bg' => '#FFF5FA', 'border' => '#F9C8DF'],
                            'bkash_send_money'  => ['logo' => 'bkash-logo.png',  'name' => 'bKash Send Money', 'sub' => 'Manual Transfer',    'color' => '#E2136E', 'bg' => '#FFF5FA', 'border' => '#F9C8DF'],
                            'nagad_send_money'  => ['logo' => 'nagad-logo.webp', 'name' => 'Nagad Send Money', 'sub' => 'Manual Transfer',    'color' => '#F37021', 'bg' => '#FFF8F3', 'border' => '#FDDBB8'],
                            'rocket_send_money' => ['logo' => 'rocket-logo.png', 'name' => 'Rocket Send Money','sub' => 'Manual Transfer',    'color' => '#6B21A8', 'bg' => '#FAF5FF', 'border' => '#E9D5FF'],
                            default             => ['logo' => null,              'name' => $method,             'sub' => '',                   'color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#e5e7eb'],
                        };
                    @endphp
                    <button type="button"
                            @click="paymentMethod = '{{ $method }}'"
                            :class="paymentMethod === '{{ $method }}' ? 'ring-2' : 'opacity-70 hover:opacity-100'"
                            class="flex items-center gap-3 p-4 rounded-xl text-left transition-all"
                            :style="paymentMethod === '{{ $method }}'
                                ? 'background:{{ $label['bg'] }};border:2px solid {{ $label['color'] }};'
                                : 'background:#FAFBFF;border:2px solid #E8EEF8;'">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden"
                             style="background:#ffffff; border:1px solid rgba(0,0,0,0.08);">
                            @if($label['logo'])
                            <img src="{{ asset('images/' . $label['logo']) }}"
                                 alt="{{ $label['name'] }}"
                                 class="w-8 h-8 object-contain">
                            @else
                            <span class="font-black text-sm" style="color:{{ $label['color'] }};">?</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-bold" style="color:#071428;">{{ $label['name'] }}</div>
                            <div class="text-xs text-gray-400">{{ $label['sub'] }}</div>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Main form (action changes dynamically) --}}
            <div class="bg-white rounded-3xl p-7" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.08);">
                <h2 class="text-lg font-bold mb-6" style="color:#071428;">Your Details</h2>

                <form method="POST" :action="formAction" id="checkoutForm" @submit="loading = true">
                    @csrf
                    <input type="hidden" name="payment_method" :value="paymentMethod">

                    <div class="space-y-5">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#071428;">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/10 transition-all"
                                   style="color:#071428; background:#FAFBFF;"
                                   placeholder="Your full name">
                            @error('name')
                            <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#071428;">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required
                                   class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/10 transition-all"
                                   style="color:#071428; background:#FAFBFF;"
                                   placeholder="your@email.com">
                            <p class="text-gray-400 text-xs mt-1.5">Your gift card codes will be delivered to this email.</p>
                            @error('email')
                            <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- ── Send Money fields ── --}}
                        <div x-show="isSendMoney" x-cloak class="space-y-4">

                            {{-- bKash send money instructions --}}
                            <div x-show="paymentMethod === 'bkash_send_money'" x-cloak
                                 class="rounded-2xl p-5 space-y-3" style="background:#FFF5FA; border:1px solid #F9C8DF;">
                                <div class="flex items-center gap-2 text-sm font-bold" style="color:#E2136E;">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center overflow-hidden" style="background:#ffffff;border:1px solid rgba(0,0,0,0.08);">
                                        <img src="{{ asset('images/bkash-logo.png') }}" alt="bKash" class="w-6 h-6 object-contain">
                                    </div>
                                    bKash Send Money Instructions
                                </div>
                                {{-- Discount summary --}}
                                <div x-show="referralDiscount > 0 || walletApplied > 0"
                                     class="rounded-xl px-4 py-2.5 text-xs space-y-1" style="background:#FFF0F6; border:1px solid #F9C8DF;">
                                    <div x-show="referralDiscount > 0" class="flex justify-between">
                                        <span class="text-gray-500">Referral discount</span>
                                        <span class="font-semibold text-green-600">− ৳ <span x-text="referralDiscount.toLocaleString('en-BD')"></span></span>
                                    </div>
                                    <div x-show="walletApplied > 0" class="flex justify-between">
                                        <span class="text-gray-500">Wallet credit</span>
                                        <span class="font-semibold text-green-600">− ৳ <span x-text="walletApplied.toLocaleString('en-BD')"></span></span>
                                    </div>
                                    <div class="flex justify-between pt-1" style="border-top:1px dashed #F9C8DF;">
                                        <span class="font-bold" style="color:#E2136E;">Amount to send</span>
                                        <span class="font-black" style="color:#E2136E;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></span>
                                    </div>
                                </div>
                                <ol class="text-sm text-gray-600 space-y-1.5 list-decimal list-inside">
                                    <li>Open your <strong>bKash app</strong> or dial <strong>*247#</strong></li>
                                    <li>Select <strong>"Send Money"</strong></li>
                                    @if($bkashSendNumber)
                                    <li>Send exactly <strong style="color:#E2136E;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></strong> to: <strong style="color:#E2136E; font-family:monospace; font-size:15px;">{{ $bkashSendNumber }}</strong></li>
                                    @else
                                    <li>Send exactly <strong style="color:#E2136E;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></strong> to our bKash number (check WhatsApp for the number)</li>
                                    @endif
                                    <li>Copy the <strong>Transaction ID (TRX ID)</strong> from the confirmation SMS</li>
                                    <li>Paste it below and click <strong>"Place Order"</strong></li>
                                    @if($referralSettings['enabled'])
                                    <li>Have a <strong>referral code</strong>? Enter it in the <strong>Referral Code</strong> field below to get an instant discount.</li>
                                    @endif
                                </ol>
                                <div class="text-xs text-gray-400 flex items-center gap-1.5 mt-2">
                                    <svg class="w-3.5 h-3.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    Send the exact amount shown above. Partial or extra amounts may delay your order.
                                </div>
                            </div>

                            {{-- Nagad send money instructions --}}
                            <div x-show="paymentMethod === 'nagad_send_money'" x-cloak
                                 class="rounded-2xl p-5 space-y-3" style="background:#FFF8F3; border:1px solid #FDDBB8;">
                                <div class="flex items-center gap-2 text-sm font-bold" style="color:#F37021;">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center overflow-hidden" style="background:#ffffff;border:1px solid rgba(0,0,0,0.08);">
                                        <img src="{{ asset('images/nagad-logo.webp') }}" alt="Nagad" class="w-6 h-6 object-contain">
                                    </div>
                                    Nagad Send Money Instructions
                                </div>
                                {{-- Discount summary --}}
                                <div x-show="referralDiscount > 0 || walletApplied > 0"
                                     class="rounded-xl px-4 py-2.5 text-xs space-y-1" style="background:#FFF3EB; border:1px solid #FDDBB8;">
                                    <div x-show="referralDiscount > 0" class="flex justify-between">
                                        <span class="text-gray-500">Referral discount</span>
                                        <span class="font-semibold text-green-600">− ৳ <span x-text="referralDiscount.toLocaleString('en-BD')"></span></span>
                                    </div>
                                    <div x-show="walletApplied > 0" class="flex justify-between">
                                        <span class="text-gray-500">Wallet credit</span>
                                        <span class="font-semibold text-green-600">− ৳ <span x-text="walletApplied.toLocaleString('en-BD')"></span></span>
                                    </div>
                                    <div class="flex justify-between pt-1" style="border-top:1px dashed #FDDBB8;">
                                        <span class="font-bold" style="color:#F37021;">Amount to send</span>
                                        <span class="font-black" style="color:#F37021;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></span>
                                    </div>
                                </div>
                                <ol class="text-sm text-gray-600 space-y-1.5 list-decimal list-inside">
                                    <li>Open your <strong>Nagad app</strong> or dial <strong>*167#</strong></li>
                                    <li>Select <strong>"Send Money"</strong></li>
                                    @if($nagadSendNumber)
                                    <li>Send exactly <strong style="color:#F37021;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></strong> to: <strong style="color:#F37021; font-family:monospace; font-size:15px;">{{ $nagadSendNumber }}</strong></li>
                                    @else
                                    <li>Send exactly <strong style="color:#F37021;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></strong> to our Nagad number (check WhatsApp for the number)</li>
                                    @endif
                                    <li>Copy the <strong>Transaction ID</strong> from the confirmation message</li>
                                    <li>Paste it below and click <strong>"Place Order"</strong></li>
                                    @if($referralSettings['enabled'])
                                    <li>Have a <strong>referral code</strong>? Enter it in the <strong>Referral Code</strong> field below to get an instant discount.</li>
                                    @endif
                                </ol>
                                <div class="text-xs text-gray-400 flex items-center gap-1.5 mt-2">
                                    <svg class="w-3.5 h-3.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    Send the exact amount shown above. Partial or extra amounts may delay your order.
                                </div>
                            </div>

                            {{-- Rocket send money instructions --}}
                            <div x-show="paymentMethod === 'rocket_send_money'" x-cloak
                                 class="rounded-2xl p-5 space-y-3" style="background:#FAF5FF; border:1px solid #E9D5FF;">
                                <div class="flex items-center gap-2 text-sm font-bold" style="color:#6B21A8;">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center overflow-hidden" style="background:#ffffff;border:1px solid rgba(0,0,0,0.08);">
                                        <img src="{{ asset('images/rocket-logo.png') }}" alt="Rocket" class="w-6 h-6 object-contain">
                                    </div>
                                    Rocket Send Money Instructions
                                </div>
                                {{-- Discount summary --}}
                                <div x-show="referralDiscount > 0 || walletApplied > 0"
                                     class="rounded-xl px-4 py-2.5 text-xs space-y-1" style="background:#F5F0FF; border:1px solid #E9D5FF;">
                                    <div x-show="referralDiscount > 0" class="flex justify-between">
                                        <span class="text-gray-500">Referral discount</span>
                                        <span class="font-semibold text-green-600">− ৳ <span x-text="referralDiscount.toLocaleString('en-BD')"></span></span>
                                    </div>
                                    <div x-show="walletApplied > 0" class="flex justify-between">
                                        <span class="text-gray-500">Wallet credit</span>
                                        <span class="font-semibold text-green-600">− ৳ <span x-text="walletApplied.toLocaleString('en-BD')"></span></span>
                                    </div>
                                    <div class="flex justify-between pt-1" style="border-top:1px dashed #E9D5FF;">
                                        <span class="font-bold" style="color:#6B21A8;">Amount to send</span>
                                        <span class="font-black" style="color:#6B21A8;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></span>
                                    </div>
                                </div>
                                <ol class="text-sm text-gray-600 space-y-1.5 list-decimal list-inside">
                                    <li>Open your <strong>Rocket app</strong> or dial <strong>*322#</strong></li>
                                    <li>Select <strong>"Send Money"</strong></li>
                                    @if($rocketSendNumber)
                                    <li>Send exactly <strong style="color:#6B21A8;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></strong> to: <strong style="color:#6B21A8; font-family:monospace; font-size:15px;">{{ $rocketSendNumber }}</strong></li>
                                    @else
                                    <li>Send exactly <strong style="color:#6B21A8;">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></strong> to our Rocket number (check WhatsApp for the number)</li>
                                    @endif
                                    <li>Copy the <strong>Transaction ID</strong> from the confirmation message</li>
                                    <li>Paste it below and click <strong>"Place Order"</strong></li>
                                    @if($referralSettings['enabled'])
                                    <li>Have a <strong>referral code</strong>? Enter it in the <strong>Referral Code</strong> field below to get an instant discount.</li>
                                    @endif
                                </ol>
                                <div class="text-xs text-gray-400 flex items-center gap-1.5 mt-2">
                                    <svg class="w-3.5 h-3.5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    Send the exact amount shown above. Partial or extra amounts may delay your order.
                                </div>
                            </div>

                            {{-- TRX ID field --}}
                            <div>
                                <label class="block text-sm font-semibold mb-1.5" style="color:#071428;">
                                    Transaction ID <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="send_money_trx_id"
                                       value="{{ old('send_money_trx_id') }}"
                                       :required="isSendMoney"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-mono focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/10 transition-all"
                                       style="color:#071428; background:#FAFBFF;"
                                       placeholder="e.g. 8G4D2X1F9Y">
                                <p class="text-gray-400 text-xs mt-1.5">Enter the Transaction ID from your payment confirmation SMS.</p>
                                @error('send_money_trx_id')
                                <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    {{ $message }}
                                </p>
                                @enderror
                            </div>

                            {{-- Delivery note --}}
                            <div class="flex items-center gap-2 text-xs text-gray-500 px-1">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                After placing your order, your gift card code will be emailed within <strong class="text-gray-700">2–5 minutes</strong> once payment is verified.
                            </div>
                        </div>

                    </div>

                    {{-- ── Referral Code ── --}}
                    @if($referralSettings['enabled'])
                    <div class="mt-5">
                        <label class="block text-sm font-semibold mb-1.5" style="color:#071428;">
                            Referral Code <span class="font-normal text-gray-400">(Optional)</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="text" x-model="referralCode"
                                   :readonly="referralValid"
                                   @keydown.enter.prevent="applyReferral()"
                                   class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono uppercase focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/10 transition-all"
                                   style="color:#071428; background:#FAFBFF;"
                                   placeholder="e.g. AHAD1234">
                            <button type="button"
                                    @click="referralValid ? removeReferral() : applyReferral()"
                                    :disabled="referralLoading"
                                    class="px-4 py-2.5 rounded-xl text-sm font-bold text-white transition-all"
                                    :class="referralValid ? 'bg-gray-400 hover:bg-gray-500' : 'bg-brand-500 hover:bg-brand-600'"
                                    :style="referralLoading ? 'opacity:0.6;cursor:not-allowed;' : ''">
                                <span x-show="!referralLoading" x-text="referralValid ? 'Remove' : 'Apply'"></span>
                                <span x-show="referralLoading" x-cloak>
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                </span>
                            </button>
                        </div>
                        <p x-show="referralMsg" x-cloak
                           :class="referralValid ? 'text-green-600' : 'text-red-500'"
                           class="text-xs mt-1.5 flex items-center gap-1">
                            <svg x-show="referralValid" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <svg x-show="!referralValid" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span x-text="referralMsg"></span>
                        </p>
                        {{-- Hidden input sent with form --}}
                        <input type="hidden" name="referral_code" :value="referralValid ? referralCode : ''">
                    </div>
                    @endif

                    {{-- ── Wallet Balance ── --}}
                    @if($walletBalance > 0)
                    <div class="mt-5 rounded-2xl p-4 flex items-start gap-3"
                         style="background:#F0FDF4; border:1px solid #BBF7D0;">
                        <div class="flex-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" x-model="useWallet" name="use_wallet" value="1"
                                       class="w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500">
                                <span class="text-sm font-semibold" style="color:#166534;">
                                    Use Wallet Balance
                                    <span class="font-normal text-green-700">
                                        (৳ {{ number_format($walletBalance, 0) }} available)
                                    </span>
                                </span>
                            </label>
                            <p x-show="useWallet" x-cloak class="text-xs text-green-700 mt-1.5 ml-6">
                                ৳ <span x-text="walletApplied.toLocaleString('en-BD')"></span> will be deducted from your wallet.
                            </p>
                        </div>
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                    </div>
                    @endif

                    {{-- bKash online notice --}}
                    <div x-show="!isSendMoney" x-cloak
                         class="mt-7 rounded-2xl p-4 flex items-start gap-3" style="background:#FFF5FA; border:1px solid #F9C8DF;">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden" style="background:#ffffff;border:1px solid rgba(0,0,0,0.08);">
                            <img src="{{ asset('images/bkash-logo.png') }}" alt="bKash" class="w-8 h-8 object-contain">
                        </div>
                        <div>
                            <div class="text-sm font-semibold" style="color:#071428;">Pay with bKash</div>
                            <div class="text-xs text-gray-500 mt-0.5">You'll be securely redirected to bKash Tokenized Checkout to complete payment.</div>
                        </div>
                    </div>

                    {{-- Submit button --}}
                    <button type="submit" :disabled="loading"
                            class="mt-6 w-full font-bold py-4 rounded-2xl text-base transition-all duration-200 flex items-center justify-center gap-3 text-white"
                            :class="loading ? 'opacity-60 cursor-not-allowed' : 'hover:opacity-90 hover:shadow-lg'"
                            :style="paymentMethod === 'nagad_send_money'
                                ? 'background:#F37021;'
                                : paymentMethod === 'rocket_send_money'
                                    ? 'background:#6B21A8;'
                                    : 'background:#E2136E;'">

                        <span x-show="!loading">
                            <span x-show="paymentMethod === 'bkash_online'">Pay with bKash →</span>
                            <span x-show="paymentMethod === 'bkash_send_money'" x-cloak>Place Order</span>
                            <span x-show="paymentMethod === 'nagad_send_money'" x-cloak>Place Order</span>
                            <span x-show="paymentMethod === 'rocket_send_money'" x-cloak>Place Order</span>
                        </span>
                        <span x-show="loading" x-cloak class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <span x-show="paymentMethod === 'bkash_online'" x-cloak>Redirecting to bKash...</span>
                            <span x-show="isSendMoney" x-cloak>Placing order...</span>
                        </span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Order Summary --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl p-6 sticky top-24" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.08);">
                <h2 class="font-bold text-lg mb-5" style="color:#071428;">Order Summary</h2>

                <div class="space-y-4 mb-5">
                    @php $total = 0; @endphp
                    @foreach($cartItems as $item)
                    @php $itemTotal = $item['price'] * $item['quantity']; $total += $itemTotal; @endphp
                    <div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-lg"
                                 style="background:linear-gradient(135deg,#071428,#0D2040);">🎮</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate" style="color:#071428;">{{ $item['gift_card']->name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">Qty: {{ $item['quantity'] }}</p>
                            </div>
                            <span class="text-brand-500 font-semibold text-sm flex-shrink-0">৳ {{ number_format($itemTotal, 0) }}</span>
                        </div>
                        <form method="POST" action="{{ route('cart.remove', $item['gift_card_id']) }}" class="mt-1.5">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition-colors ml-13" style="margin-left:52px;">Remove</button>
                        </form>
                    </div>
                    @endforeach
                </div>

                <div class="pt-4 mb-5" style="border-top:1px solid #E8EEF8;">
                    <div class="flex justify-between text-sm text-gray-400 mb-2">
                        <span>Subtotal</span>
                        <span>৳ {{ number_format($subtotal, 0) }}</span>
                    </div>
                    <div x-show="referralDiscount > 0" x-cloak class="flex justify-between text-sm mb-1.5">
                        <span class="text-green-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Referral Discount
                        </span>
                        <span class="text-green-600 font-medium">− ৳ <span x-text="referralDiscount.toLocaleString('en-BD')"></span></span>
                    </div>
                    <div x-show="walletApplied > 0" x-cloak class="flex justify-between text-sm mb-1.5">
                        <span class="text-green-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                            Wallet Credit
                        </span>
                        <span class="text-green-600 font-medium">− ৳ <span x-text="walletApplied.toLocaleString('en-BD')"></span></span>
                    </div>
                    <div class="flex justify-between font-bold text-lg mt-3">
                        <span style="color:#071428;">Total</span>
                        <span class="text-brand-500">৳ <span x-text="orderTotal.toLocaleString('en-BD')"></span></span>
                    </div>
                </div>

                <a href="{{ route('cart') }}" class="block text-center text-sm text-gray-400 hover:text-brand-500 transition-colors mb-1">
                    ← Back to Cart
                </a>

                <div class="mt-3 text-center text-xs text-gray-400 flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span x-text="isSendMoney ? 'Secure Order via Manual Transfer' : 'Secured Checkout'"></span>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

@push('styles')
<style>body { background: #F8FAFF !important; }</style>
@endpush

@endsection
