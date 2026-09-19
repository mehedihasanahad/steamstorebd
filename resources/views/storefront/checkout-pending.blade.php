@extends('layouts.storefront')

@section('title', 'Order Under Review — Steam Store BD')
@section('robots', 'noindex, nofollow')
@section('meta_description', 'We are verifying your payment.')

@section('content')

<div class="mx-auto max-w-2xl px-4 py-section sm:px-6 lg:px-8 lg:py-section-lg">

    <div class="text-center">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-2 border-warning bg-warning/10" aria-hidden="true">
            <svg class="h-8 w-8 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>

        <h1 class="mt-5 text-title md:text-display font-extrabold text-ink-hi">Order under review</h1>
        <p class="mx-auto mt-2 max-w-md text-body text-ink-mid">
            We have your order and are verifying the payment. Your code is usually e-mailed within
            <strong class="text-ink-hi">2–5 minutes</strong>.
        </p>
    </div>

    <section class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="pending-details">
        <h2 id="pending-details" class="sr-only">Order details</h2>

        <dl class="grid grid-cols-2 gap-3">
            <div class="rounded-control border border-surface-3 bg-surface-2 p-3">
                <dt class="text-meta text-ink-low">Order ID</dt>
                <dd class="mt-0.5 font-mono text-caption font-bold text-ink-hi">{{ $order->order_number }}</dd>
            </div>
            <div class="rounded-control border border-surface-3 bg-surface-2 p-3">
                <dt class="text-meta text-ink-low">Amount</dt>
                <dd class="mt-0.5 text-caption font-bold tabular-nums text-success">{{ format_bdt($order->total_bdt) }}</dd>
            </div>
            <div class="rounded-control border border-surface-3 bg-surface-2 p-3">
                <dt class="text-meta text-ink-low">Payment method</dt>
                <dd class="mt-0.5 text-caption font-semibold text-ink-hi">{{ $order->paymentMethodLabel() }}</dd>
            </div>
            <div class="rounded-control border border-surface-3 bg-surface-2 p-3">
                <dt class="text-meta text-ink-low">Transaction ID</dt>
                <dd class="mt-0.5 truncate font-mono text-caption font-semibold text-ink-hi">{{ $order->send_money_trx_id }}</dd>
            </div>
        </dl>

        <ul class="mt-4 divide-y divide-surface-3 rounded-control border border-surface-3">
            @foreach($order->items as $item)
                <li class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-caption font-medium text-ink-hi">{{ $item->giftCard->name }}</p>
                        <p class="text-meta text-ink-low">&times; {{ $item->quantity }}</p>
                    </div>
                    <span class="flex-shrink-0 text-caption font-semibold tabular-nums text-success">{{ format_bdt($item->subtotal_bdt) }}</span>
                </li>
            @endforeach
        </ul>

        <p class="mt-4 rounded-control border border-success/30 bg-success/10 p-3 text-caption text-ink-mid">
            <strong class="text-ink-hi">Check your e-mail.</strong>
            A confirmation has gone to {{ $order->customer_email }}. Your code arrives in a separate e-mail once the payment is verified.
        </p>
    </section>

    <div class="mt-6 flex flex-col gap-2 sm:flex-row">
        <x-ui.button :href="route('home')" variant="secondary" size="lg" class="flex-1">Back to home</x-ui.button>
        <x-ui.button :href="route('orders.lookup')" size="lg" class="flex-1">Track this order</x-ui.button>
    </div>

    <p class="mt-5 text-center text-meta text-ink-low">
        Need help? <a href="{{ route('contact') }}" class="text-accent-hover hover:underline">Contact support</a>
    </p>
</div>

@endsection
