@php
    // Not every line on an order is delivered the same way, and each card
    // carries its own "Delivery time shown to buyers". Promising a code in
    // 2–5 minutes to someone who bought a top-up is a promise this e-mail
    // cannot keep, so the wording follows the card rather than the other way
    // round.
    $codeItems   = $order->items->reject(fn ($item) => $item->isManual());
    $manualItems = $order->items->filter(fn ($item) => $item->isManual());

    // One time every line agrees on can be stated in a sentence. A mix of
    // them cannot, so those orders get a line-by-line table instead.
    $sharedEta = $order->sharedDeliveryEta();
@endphp
<x-email.layout
    :title="'Order #' . $order->order_number . ' received'"
    :preheader="'We are verifying your ' . $order->paymentMethodLabel() . ' payment'
        . ($sharedEta ? ' — delivery in ' . $sharedEta . '.' : '.')"
    eyebrow="Gift cards, top-ups, keys and subscriptions"
    :badge="'Order #' . $order->order_number"
>

    <x-email.banner tone="warning" heading="Order received — under review">
        Hi {{ $order->customer_name }}, we have your order and are verifying your payment.<br>
        @if($sharedEta && $manualItems->isEmpty())
            Your {{ Str::plural('code', $order->items->sum('quantity')) }} will be delivered within
            <x-email.em tone="warning">{{ $sharedEta }}</x-email.em>.
        @elseif($sharedEta && $codeItems->isEmpty())
            Our team starts on your order as soon as it clears — delivery usually takes
            <x-email.em tone="warning">{{ $sharedEta }}</x-email.em>.
        @elseif($sharedEta)
            Your order will be delivered within <x-email.em tone="warning">{{ $sharedEta }}</x-email.em>.
        @elseif($codeItems->isEmpty())
            Our team starts on your order as soon as it clears. Delivery times are below.
        @else
            Delivery times differ by item, so each one is listed below.
        @endif
    </x-email.banner>

    <x-email.panel title="Payment details">
        <x-email.rows>
            <x-email.row label="Payment method">{{ $order->paymentMethodLabel() }}</x-email.row>
            <x-email.row label="Your transaction ID" mono>{{ $order->send_money_trx_id }}</x-email.row>
            <x-email.row label="Amount paid" tone="success" :divider="false">৳ {{ number_format($order->total_bdt, 0, '.', ',') }}</x-email.row>
        </x-email.rows>
    </x-email.panel>

    <x-email.panel title="Order summary">
        <x-email.rows>
            @foreach($order->items as $item)
            <x-email.row :label="$item->giftCard->name . ' × ' . $item->quantity" tone="success">
                ৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}
            </x-email.row>
            @endforeach
            <x-email.row label="Total" tone="success" :divider="false" emphasis>
                ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}
            </x-email.row>
        </x-email.rows>
    </x-email.panel>

    @unless($sharedEta)
    {{-- No single time covers this order, so each line carries its own. --}}
    <x-email.panel title="Delivery times">
        <x-email.rows>
            @foreach($order->items as $item)
            <x-email.row :label="$item->giftCard->name . ' × ' . $item->quantity" tone="warning" :divider="! $loop->last">
                {{ $item->deliveryEtaLabel() }}
            </x-email.row>
            @endforeach
        </x-email.rows>
    </x-email.panel>
    @endunless

    <x-email.list title="What happens next?">
        <li>Our team verifies your {{ $order->paymentMethodLabel() }} transaction</li>
        @if($manualItems->isEmpty())
            <li>Once confirmed, your {{ Str::plural('code', $order->items->sum('quantity')) }} {{ $order->items->sum('quantity') > 1 ? 'are' : 'is' }} sent to this email</li>
            <li>{{ $sharedEta ? 'This usually takes ' . $sharedEta : 'Delivery times are listed above' }}</li>
        @elseif($codeItems->isEmpty())
            <li>Once confirmed, our team fulfils your order by hand</li>
            <li>{{ $sharedEta ? 'This usually takes ' . $sharedEta : 'We email you as soon as it is delivered' }}</li>
        @else
            <li>Once confirmed, your codes are sent to this email</li>
            <li>The rest of your order is delivered by our team, and we email you when it is ready</li>
        @endif
    </x-email.list>

    <x-slot:footer>
        <p style="margin:0;">Questions? <x-email.link :url="route('contact')">Contact our support</x-email.link></p>
    </x-slot:footer>

    <x-slot:disclaimer>
        {{ site_setting('site_name', 'Steam Store BD') }} is an independent reseller and is not affiliated with Valve Corporation.
    </x-slot:disclaimer>

</x-email.layout>
