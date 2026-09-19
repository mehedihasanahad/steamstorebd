@extends('layouts.storefront')

@section('title', 'My Orders — Steam Store BD')
@section('robots', 'noindex, nofollow')
@section('meta_description', 'Your Steam Store BD orders.')

@section('content')

<div class="mx-auto max-w-3xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'My orders', 'url' => null],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-title md:text-display font-extrabold text-ink-hi">My orders</h1>
            <p class="mt-1 text-caption text-ink-low">{{ $orders->count() }} {{ Str::plural('order', $orders->count()) }}</p>
        </div>
        <x-ui.button :href="route('home')" variant="secondary" size="sm">Keep shopping</x-ui.button>
    </div>

    @if($orders->isEmpty())
        <div class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-12 text-center">
            <p class="text-body font-semibold text-ink-hi">No orders yet</p>
            <p class="mx-auto mt-1.5 max-w-sm text-caption text-ink-low">Once you buy something it will show up here with its codes.</p>
            <x-ui.button :href="route('home')" class="mt-6">Browse the catalog</x-ui.button>
        </div>
    @else
        <ul class="mt-6 space-y-3">
            @foreach($orders as $order)
                @php
                    $tone = match ($order->status) {
                        'completed', 'paid'               => 'success',
                        'processing', 'pending_review',
                        'pending', 'payment_initiated'    => 'warning',
                        'failed', 'refunded', 'cancelled' => 'danger',
                        default                           => 'neutral',
                    };
                    $itemCount = $order->items->sum('quantity');
                    $itemNames = $order->items->map(fn ($item) => $item->giftCard->name)->implode(', ');
                @endphp

                <li>
                    <a href="{{ route('orders.show', $order->order_number) }}"
                       class="flex flex-wrap items-center gap-3 rounded-card border border-surface-3 bg-surface-1 p-4 transition-colors hover:border-accent/50">

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-caption font-bold text-ink-hi">{{ $order->order_number }}</span>
                                <x-ui.badge :tone="$tone">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</x-ui.badge>
                            </div>
                            <p class="mt-1 text-meta text-ink-low">
                                {{ $order->created_at->format('d M Y, h:i A') }}
                                &middot; {{ $itemCount }} {{ Str::plural('item', $itemCount) }}
                                @if($itemNames) &mdash; {{ $itemNames }} @endif
                            </p>
                        </div>

                        <span class="flex-shrink-0 text-body font-bold tabular-nums text-success">{{ format_bdt($order->total_bdt) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

@endsection
