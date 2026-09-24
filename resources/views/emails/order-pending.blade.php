<x-email.layout
    :title="'Order #' . $order->order_number . ' received'"
    :preheader="'We are verifying your ' . $order->paymentMethodLabel() . ' payment — usually 2–5 minutes.'"
    eyebrow="Gift cards, top-ups, keys and subscriptions"
    :badge="'Order #' . $order->order_number"
>

    <x-email.banner tone="warning" heading="Order received — under review">
        Hi {{ $order->customer_name }}, we have your order and are verifying your payment.<br>
        Your code will be delivered within <x-email.em tone="warning">2–5 minutes</x-email.em>.
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

    <x-email.list title="What happens next?">
        <li>Our team verifies your {{ $order->paymentMethodLabel() }} transaction</li>
        <li>Once confirmed, your code is sent to this email</li>
        <li>This usually takes 2–5 minutes</li>
    </x-email.list>

    <x-slot:footer>
        <p style="margin:0;">Questions? <x-email.link :url="route('contact')">Contact our support</x-email.link></p>
    </x-slot:footer>

    <x-slot:disclaimer>
        {{ site_setting('site_name', 'Steam Store BD') }} is an independent reseller and is not affiliated with Valve Corporation.
    </x-slot:disclaimer>

</x-email.layout>
