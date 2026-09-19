@extends('layouts.storefront')

@section('title', 'Order Confirmed — Steam Store BD')
@section('robots', 'noindex, nofollow')
@section('meta_description', 'Your order is confirmed.')

@section('content')

@php
    $codeItems     = $order->items->filter(fn ($item) => $item->orderItemCodes->isNotEmpty());
    $awaitingItems = $order->items->filter(fn ($item) => $item->needsFulfilment());
@endphp

<div class="mx-auto max-w-2xl px-4 py-section sm:px-6 lg:px-8 lg:py-section-lg">

    <div class="text-center">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-2 border-success bg-success/10" aria-hidden="true">
            <svg class="h-8 w-8 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </span>

        <h1 class="mt-5 text-title md:text-display font-extrabold text-ink-hi">Payment successful</h1>
        <p class="mt-2 text-body text-ink-mid">
            Order <span class="font-mono font-bold text-ink-hi">#{{ $order->order_number }}</span>
        </p>
        <p class="mt-1 text-caption text-ink-low">A copy has been e-mailed to {{ $order->customer_email }}</p>
    </div>

    @if($codeItems->isNotEmpty())
        <section class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="codes-heading">
            <h2 id="codes-heading" class="text-lede font-bold text-ink-hi">Your codes</h2>

            @foreach($codeItems as $item)
                <div class="mt-4">
                    <p class="text-caption text-ink-low">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</p>

                    <ul class="mt-2 space-y-2">
                        @foreach($item->orderItemCodes as $itemCode)
                            <li x-data="{ copied: false }" class="flex items-center gap-2">
                                <code class="flex-1 truncate rounded-control border border-surface-3 bg-surface-2 px-3 py-3 font-mono text-caption font-bold tracking-wider text-ink-hi">{{ $itemCode->giftCardCode->code }}</code>
                                <x-ui.button variant="secondary"
                                             x-on:click="navigator.clipboard.writeText(@js($itemCode->giftCardCode->code)); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-show="!copied">Copy</span>
                                    <span x-show="copied" x-cloak>Copied</span>
                                </x-ui.button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </section>
    @endif

    {{-- A partly-delivered order says so here rather than leaving the buyer to
         wonder why one of the things they paid for has no code. --}}
    @if($awaitingItems->isNotEmpty())
        <section class="mt-4 rounded-card border border-warning/40 bg-surface-1 p-5" aria-labelledby="awaiting-heading">
            <h2 id="awaiting-heading" class="text-lede font-bold text-ink-hi">Being delivered by our team</h2>
            <p class="mt-1 text-caption text-ink-mid">These are paid for and in our queue. We will e-mail you the moment each one is done.</p>

            <ul class="mt-4 space-y-2">
                @foreach($awaitingItems as $item)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-control border border-surface-3 bg-surface-2 p-3">
                        <span class="text-caption font-semibold text-ink-hi">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</span>
                        <x-ui.badge tone="warning">{{ $item->giftCard->delivery_eta_label ?: 'In progress' }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if($referralSettings['enabled'])
        <section class="mt-4 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="share-heading">
            <h2 id="share-heading" class="text-lede font-bold text-ink-hi">Share your code, earn together</h2>
            <p class="mt-1 text-caption text-ink-mid">Your friend uses it at checkout and saves; your wallet is credited once their order is confirmed.</p>

            @if($referralCode)
                <div x-data="{ copied: false }" class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-control border border-surface-3 bg-surface-2 px-4 py-2.5 font-mono text-body font-bold tracking-widest text-ink-hi">{{ $referralCode }}</span>
                    <x-ui.button variant="secondary" size="sm"
                                 x-on:click="navigator.clipboard.writeText(@js($referralCode)); copied = true; setTimeout(() => copied = false, 2000)">
                        <span x-show="!copied">Copy code</span>
                        <span x-show="copied" x-cloak>Copied</span>
                    </x-ui.button>
                    <x-ui.button :href="route('referral.dashboard')" size="sm">Dashboard</x-ui.button>
                </div>
            @else
                <x-ui.button :href="route('register')" size="sm" class="mt-4">Create an account to get your code</x-ui.button>
            @endif
        </section>
    @endif

    <div class="mt-6 flex flex-col gap-2 sm:flex-row">
        <x-ui.button :href="route('orders.show', $order->order_number)" variant="secondary" size="lg" class="flex-1">View this order</x-ui.button>
        <x-ui.button :href="route('home')" size="lg" class="flex-1">Keep shopping</x-ui.button>
    </div>
</div>

@endsection
