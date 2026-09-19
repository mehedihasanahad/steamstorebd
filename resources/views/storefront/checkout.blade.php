@extends('layouts.storefront')

@section('title', 'Checkout — Steam Store BD')
@section('robots', 'noindex, follow')
@section('meta_description', 'Complete your Steam Store BD order.')

@php
    use App\Services\PaymentMethods;

    $defaultMethod = old('payment_method', $paymentMethods[0] ?? 'bkash_online');
    $subtotal      = collect($cartItems)->sum(fn ($item) => $item['price'] * $item['quantity']);
    $sendMethods   = array_values(array_filter($paymentMethods, fn ($method) => PaymentMethods::isSendMoney($method)));
@endphp

@section('content')

<div class="mx-auto max-w-shell px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg"
     x-data="{
         paymentMethod: @js($defaultMethod),
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
         get orderTotalLabel() { return this.orderTotal.toLocaleString('en-BD'); },

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

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Cart', 'url' => route('cart')],
        ['label' => 'Checkout', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">Checkout</h1>

    @if($errors->any())
        <div class="mt-4 rounded-card border border-danger/40 bg-danger/10 p-4" role="alert">
            <ul class="space-y-1 text-caption text-danger">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-5 grid grid-cols-12 gap-5">

        <div class="col-span-12 space-y-4 lg:col-span-8">

            {{-- Payment method --}}
            @if(count($paymentMethods) > 1)
                <fieldset class="rounded-card border border-surface-3 bg-surface-1 p-5">
                    <legend class="px-1 text-body font-bold text-ink-hi">Payment method</legend>

                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach($paymentMethods as $method)
                            @php $detail = PaymentMethods::describe($method); @endphp
                            <button type="button"
                                    @click="paymentMethod = @js($method)"
                                    :aria-pressed="paymentMethod === @js($method) ? 'true' : 'false'"
                                    class="flex items-center gap-3 rounded-control border p-3 text-left transition-colors"
                                    :class="paymentMethod === @js($method) ? 'border-accent bg-accent/10' : 'border-surface-3 bg-surface-2 hover:border-accent/50'">
                                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center overflow-hidden rounded-control bg-white">
                                    @if($detail['logo'])
                                        <img src="{{ asset('images/' . $detail['logo']) }}" alt="" width="28" height="28" class="h-7 w-7 object-contain" aria-hidden="true">
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-caption font-semibold text-ink-hi">{{ $detail['name'] }}</span>
                                    <span class="block text-meta text-ink-low">{{ $detail['sub'] }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            {{-- Details form --}}
            <div class="rounded-card border border-surface-3 bg-surface-1 p-5 md:p-6">
                <h2 class="text-lede font-bold text-ink-hi">Your details</h2>

                <form method="POST" :action="formAction" id="checkoutForm" @submit="loading = true" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="payment_method" :value="paymentMethod">

                    <x-ui.input label="Full name" name="name" required
                                :value="old('name', auth()->user()->name)"
                                placeholder="Your full name"
                                :error="$errors->first('name')" />

                    <x-ui.input label="Email address" name="email" type="email" required
                                :value="old('email', auth()->user()->email)"
                                placeholder="you@example.com"
                                hint="Your codes are delivered to this address."
                                :error="$errors->first('email')" />

                    {{-- Send-money instructions. One block, driven by the
                         method's own details, rather than one hand-written
                         copy per wallet. --}}
                    <div x-show="isSendMoney" x-cloak class="space-y-4">
                        @foreach($sendMethods as $method)
                            @php $detail = PaymentMethods::describe($method); @endphp
                            <div x-show="paymentMethod === @js($method)" x-cloak
                                 class="rounded-card border border-surface-3 bg-surface-2 p-4">

                                <p class="flex items-center gap-2 text-caption font-bold text-ink-hi">
                                    <span class="flex h-7 w-7 items-center justify-center overflow-hidden rounded-control bg-white">
                                        <img src="{{ asset('images/' . $detail['logo']) }}" alt="" width="24" height="24" class="h-6 w-6 object-contain" aria-hidden="true">
                                    </span>
                                    {{ $detail['name'] }} instructions
                                </p>

                                <div x-show="referralDiscount > 0 || walletApplied > 0" x-cloak
                                     class="mt-3 space-y-1 rounded-control border border-surface-3 bg-surface-1 px-3 py-2 text-meta">
                                    <div x-show="referralDiscount > 0" class="flex justify-between">
                                        <span class="text-ink-low">Referral discount</span>
                                        <span class="font-semibold text-success">&minus; ৳ <span x-text="referralDiscount.toLocaleString('en-BD')"></span></span>
                                    </div>
                                    <div x-show="walletApplied > 0" class="flex justify-between">
                                        <span class="text-ink-low">Wallet credit</span>
                                        <span class="font-semibold text-success">&minus; ৳ <span x-text="walletApplied.toLocaleString('en-BD')"></span></span>
                                    </div>
                                    <div class="flex justify-between border-t border-surface-3 pt-1">
                                        <span class="font-bold text-ink-hi">Amount to send</span>
                                        <span class="font-bold text-success">৳ <span x-text="orderTotalLabel"></span></span>
                                    </div>
                                </div>

                                <ol class="mt-3 list-inside list-decimal space-y-1.5 text-caption text-ink-mid">
                                    <li>Open your <strong class="text-ink-hi">{{ $detail['app'] }}</strong> or dial <strong class="text-ink-hi">{{ $detail['ussd'] }}</strong></li>
                                    <li>Choose <strong class="text-ink-hi">Send Money</strong></li>
                                    <li>
                                        Send exactly <strong class="text-success">৳ <span x-text="orderTotalLabel"></span></strong>
                                        @if($detail['number'])
                                            to <strong class="font-mono text-ink-hi">{{ $detail['number'] }}</strong>
                                        @else
                                            to our number — message us on WhatsApp for it
                                        @endif
                                    </li>
                                    <li>Copy the <strong class="text-ink-hi">Transaction ID</strong> from the confirmation message</li>
                                    <li>Paste it below and place the order</li>
                                    @if($referralSettings['enabled'])
                                        <li>Have a referral code? Enter it below before you send, so the amount is right.</li>
                                    @endif
                                </ol>

                                <p class="mt-3 text-meta text-warning">
                                    Send the exact amount shown. A partial or extra amount delays the order.
                                </p>
                            </div>
                        @endforeach

                        <x-ui.input label="Transaction ID" name="send_money_trx_id"
                                    :value="old('send_money_trx_id')"
                                    x-bind:required="isSendMoney"
                                    placeholder="e.g. 8G4D2X1F9Y"
                                    class="font-mono"
                                    hint="The Transaction ID from your payment confirmation SMS."
                                    :error="$errors->first('send_money_trx_id')" />

                        <p class="text-meta text-ink-low">
                            Once your order is placed, your code is e-mailed within <strong class="text-ink-mid">2–5 minutes</strong> of the payment being verified.
                        </p>
                    </div>

                    {{-- Referral code --}}
                    @if($referralSettings['enabled'])
                        <div>
                            <label for="referral-code" class="mb-1.5 block text-caption font-medium text-ink-mid">
                                Referral code <span class="text-ink-low">(optional)</span>
                            </label>
                            <div class="flex gap-2">
                                <input id="referral-code" type="text" x-model="referralCode"
                                       :readonly="referralValid"
                                       @keydown.enter.prevent="applyReferral()"
                                       placeholder="e.g. AHAD1234"
                                       class="min-h-[44px] flex-1 rounded-control border border-surface-3 bg-surface-2 px-3 font-mono text-body uppercase text-ink-hi
                                              placeholder:text-ink-low focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">

                                <x-ui.button type="button"
                                             x-bind:class="referralValid ? 'bg-surface-2 text-ink-mid border border-surface-3 hover:bg-surface-3' : ''"
                                             @click="referralValid ? removeReferral() : applyReferral()"
                                             x-bind:disabled="referralLoading">
                                    <span x-show="!referralLoading" x-text="referralValid ? 'Remove' : 'Apply'"></span>
                                    <svg x-show="referralLoading" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                </x-ui.button>
                            </div>

                            <p x-show="referralMsg" x-cloak class="mt-1.5 text-meta"
                               :class="referralValid ? 'text-success' : 'text-danger'"
                               x-text="referralMsg"></p>

                            <input type="hidden" name="referral_code" :value="referralValid ? referralCode : ''">
                        </div>
                    @endif

                    {{-- Wallet --}}
                    @if($walletBalance > 0)
                        <div class="rounded-card border border-success/30 bg-success/10 p-4">
                            <label class="flex cursor-pointer select-none items-center gap-2">
                                <input type="checkbox" x-model="useWallet" name="use_wallet" value="1"
                                       class="h-4 w-4 rounded-chip border-surface-3 bg-surface-2 text-success focus:ring-2 focus:ring-success/40">
                                <span class="text-caption font-semibold text-ink-hi">
                                    Use wallet balance
                                    <span class="font-normal text-ink-mid">({{ format_bdt($walletBalance) }} available)</span>
                                </span>
                            </label>
                            <p x-show="useWallet" x-cloak class="ml-6 mt-1.5 text-meta text-ink-mid">
                                ৳ <span x-text="walletApplied.toLocaleString('en-BD')"></span> will come off this order.
                            </p>
                        </div>
                    @endif

                    {{-- bKash online notice --}}
                    <div x-show="!isSendMoney" x-cloak class="flex items-start gap-3 rounded-card border border-surface-3 bg-surface-2 p-4">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center overflow-hidden rounded-control bg-white">
                            <img src="{{ asset('images/bkash-logo.png') }}" alt="" width="28" height="28" class="h-7 w-7 object-contain" aria-hidden="true">
                        </span>
                        <span>
                            <span class="block text-caption font-semibold text-ink-hi">Pay with bKash</span>
                            <span class="block text-meta text-ink-low">You will be redirected to bKash Tokenized Checkout to complete the payment.</span>
                        </span>
                    </div>

                    <x-ui.button type="submit" size="lg" class="w-full" x-bind:disabled="loading">
                        <span x-show="!loading" x-text="isSendMoney ? 'Place order' : 'Pay with bKash'"></span>
                        <span x-show="loading" x-cloak class="flex items-center gap-2">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            <span x-text="isSendMoney ? 'Placing order' : 'Redirecting to bKash'"></span>
                        </span>
                    </x-ui.button>
                </form>
            </div>
        </div>

        {{-- Order summary --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="rounded-card border border-surface-3 bg-surface-1 p-5 lg:sticky lg:top-[120px]">
                <h2 class="text-lede font-bold text-ink-hi">Order summary</h2>

                <ul class="mt-4 space-y-3">
                    @foreach($cartItems as $item)
                        <li class="flex items-start gap-3">
                            <x-catalog.artwork :image="$item['gift_card']->image ?: $item['gift_card']->category?->image"
                                               :name="$item['gift_card']->name" ratio="aspect-square"
                                               class="w-10 flex-shrink-0 rounded-control" :width="40" :height="40" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-caption font-medium text-ink-hi">{{ $item['gift_card']->name }}</p>
                                <p class="text-meta text-ink-low">Qty: {{ $item['quantity'] }}</p>
                                @if(! empty($item['buyer_inputs']))
                                    <p class="mt-0.5 text-meta text-ink-low">
                                        @foreach($item['buyer_inputs'] as $field => $value){{ Str::headline($field) }}: {{ $value }}@if(! $loop->last) · @endif @endforeach
                                    </p>
                                @endif
                            </div>
                            <span class="flex-shrink-0 text-caption font-semibold tabular-nums text-success">{{ format_bdt($item['price'] * $item['quantity']) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-4 space-y-1.5 border-t border-surface-3 pt-4 text-caption">
                    <div class="flex justify-between">
                        <dt class="text-ink-low">Subtotal</dt>
                        <dd class="tabular-nums text-ink-mid">{{ format_bdt($subtotal) }}</dd>
                    </div>
                    <div x-show="referralDiscount > 0" x-cloak class="flex justify-between">
                        <dt class="text-success">Referral discount</dt>
                        <dd class="font-medium text-success">&minus; ৳ <span x-text="referralDiscount.toLocaleString('en-BD')"></span></dd>
                    </div>
                    <div x-show="walletApplied > 0" x-cloak class="flex justify-between">
                        <dt class="text-success">Wallet credit</dt>
                        <dd class="font-medium text-success">&minus; ৳ <span x-text="walletApplied.toLocaleString('en-BD')"></span></dd>
                    </div>
                    <div class="flex justify-between border-t border-surface-3 pt-3">
                        <dt class="text-body font-bold text-ink-hi">Total</dt>
                        <dd class="text-lede font-bold tabular-nums text-success">৳ <span x-text="orderTotalLabel"></span></dd>
                    </div>
                </dl>

                <a href="{{ route('cart') }}" class="mt-4 block text-center text-caption text-ink-low transition-colors hover:text-accent-hover">
                    &larr; Back to cart
                </a>

                <p class="mt-3 flex items-center justify-center gap-1.5 text-meta text-ink-low">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span x-text="isSendMoney ? 'Secure order via manual transfer' : 'Secured checkout'"></span>
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
