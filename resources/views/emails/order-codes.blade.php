@php
    // Three delivery shapes on one order: codes pulled from the pool, details
    // an admin sent by hand, and lines still in the queue. Each is named for
    // what it actually is, rather than labelled "Steam Wallet Code" whatever
    // the buyer bought.
    $codeItems      = $order->items->filter(fn ($item) => $item->orderItemCodes->isNotEmpty());
    $deliveredItems = $order->items->filter(fn ($item) => $item->isFulfilled() && filled($item->delivered_payload));
    $awaitingItems  = $order->items->filter(fn ($item) => $item->needsFulfilment());
    $partial        = $awaitingItems->isNotEmpty();
@endphp
<x-email.layout
    :title="'Your order #' . $order->order_number"
    :preheader="$partial ? 'Payment confirmed — part of your order is ready below.' : 'Payment confirmed — your order is ready below.'"
    eyebrow="Gift cards, top-ups, keys and subscriptions"
    :badge="'Order #' . $order->order_number"
>

    <x-email.banner
        :tone="$partial ? 'warning' : 'success'"
        :heading="$partial ? 'Payment confirmed — part of your order is on the way' : 'Payment confirmed'"
    >
        @if($partial)
            Hi {{ $order->customer_name }}, everything we can deliver instantly is below. The rest is with our team now.
        @else
            Hi {{ $order->customer_name }}, your order is below.
        @endif
    </x-email.banner>

    @if($codeItems->isNotEmpty())
    <x-email.panel title="Your codes">
        @foreach($codeItems as $item)
            <x-email.text size="caption" tone="low" :top="$loop->first ? '0' : '6px'" bottom="8px">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</x-email.text>
            @foreach($item->orderItemCodes as $itemCode)
                <x-email.code :label="$item->deliveryLabel()">{{ $itemCode->giftCardCode->code }}</x-email.code>
            @endforeach
        @endforeach
    </x-email.panel>
    @endif

    @if($deliveredItems->isNotEmpty())
    <x-email.panel title="Your account details">
        @foreach($deliveredItems as $item)
            <x-email.text size="caption" tone="low" bottom="8px">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</x-email.text>
            <x-email.code :label="$item->deliveryLabel()">{{ $item->delivered_payload }}</x-email.code>
        @endforeach
        <x-email.text size="caption" tone="low" top="4px">Keep these private. Anyone with them can use the account.</x-email.text>
    </x-email.panel>
    @endif

    @if($awaitingItems->isNotEmpty())
    <x-email.panel title="Still being delivered">
        <x-email.rows>
            @foreach($awaitingItems as $item)
            <x-email.row :label="$item->giftCard->name . ' × ' . $item->quantity" tone="warning" :divider="! $loop->last">
                {{ $item->giftCard->delivery_eta_label ?: 'In progress' }}
            </x-email.row>
            @endforeach
        </x-email.rows>
        <x-email.text size="caption" tone="low" top="12px">We will e-mail you again as soon as these are done. You can also follow them on your order page.</x-email.text>
    </x-email.panel>
    @endif

    <x-email.panel title="Order summary">
        <x-email.rows>
            @foreach($order->items as $item)
            <x-email.row :label="$item->giftCard->name . ' × ' . $item->quantity" tone="success">
                ৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}
            </x-email.row>
            @endforeach
            <x-email.row label="Total paid" tone="success" :divider="false" emphasis>
                ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}
            </x-email.row>
        </x-email.rows>
    </x-email.panel>

    @if($codeItems->isNotEmpty())
    <x-email.list title="Redeeming your code">
        <li>Sign in to the platform the code is for</li>
        <li>Open its wallet, billing or redeem page</li>
        <li>Choose &ldquo;Redeem a code&rdquo; or &ldquo;Add funds&rdquo;</li>
        <li>Paste the code exactly as it appears above</li>

        <x-slot:note>
            Step-by-step guides per brand: <x-email.link :url="route('how-to-redeem')">how to redeem</x-email.link>
        </x-slot:note>
    </x-email.list>
    @endif

    <x-email.panel align="center">
        <x-email.text size="body" tone="hi" align="center" bottom="6px"><strong>Happy with your purchase?</strong></x-email.text>
        <x-email.text size="caption" tone="low" align="center" bottom="16px">A short review helps the next buyer decide. It takes about a minute.</x-email.text>
        <x-email.button :url="route('orders.show', $order->order_number)">Leave a review</x-email.button>
    </x-email.panel>

    <x-slot:footer>
        <p style="margin:0;">Need help? <x-email.link :url="route('contact')">Contact our support</x-email.link></p>
    </x-slot:footer>

    <x-slot:disclaimer>
        {{ site_setting('site_name', 'Steam Store BD') }} is an independent reseller and is not affiliated with Valve Corporation. All brand names are property of their respective owners.
    </x-slot:disclaimer>

</x-email.layout>
