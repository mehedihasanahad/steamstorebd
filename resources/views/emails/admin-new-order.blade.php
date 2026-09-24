<x-email.layout
    :title="'[ACTION REQUIRED] New order #' . $order->order_number"
    :preheader="'Verify the ' . $order->paymentMethodLabel() . ' transaction and send the codes.'"
    eyebrow="Admin notification"
    :badge="'Order #' . $order->order_number"
>

    <x-email.banner tone="danger" heading="New send money order — action required">
        A customer has placed a <x-email.em>{{ $order->paymentMethodLabel() }}</x-email.em> order.<br>
        Verify the transaction and send the codes.
    </x-email.banner>

    <x-email.panel title="Customer details">
        <x-email.rows>
            <x-email.row label="Name" :divider="(bool) $order->customer_phone">{{ $order->customer_name }}</x-email.row>
            <x-email.row label="Email" :divider="(bool) $order->customer_phone">{{ $order->customer_email }}</x-email.row>
            @if($order->customer_phone)
            <x-email.row label="Phone" :divider="false">{{ $order->customer_phone }}</x-email.row>
            @endif
        </x-email.rows>
    </x-email.panel>

    <x-email.panel title="Payment details">
        <x-email.rows>
            <x-email.row label="Payment method">{{ $order->paymentMethodLabel() }}</x-email.row>
            <x-email.row label="Amount" tone="success" :divider="false">৳ {{ number_format($order->total_bdt, 0, '.', ',') }}</x-email.row>
        </x-email.rows>
        <x-email.text size="caption" tone="low" top="16px" bottom="8px">Check this against your {{ $order->walletLabel() }} app:</x-email.text>
        <x-email.code label="Transaction ID — verify this">{{ $order->send_money_trx_id }}</x-email.code>
    </x-email.panel>

    <x-email.panel title="Order items">
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

    <x-email.panel align="center">
        <x-email.text size="caption" tone="low" align="center" bottom="16px">Approve the order and release the codes from the admin panel.</x-email.text>
        <x-email.button :url="url('/admin/orders')">Open admin panel</x-email.button>
    </x-email.panel>

    <x-slot:footer>
        <p style="margin:0;">This is an automated notification.</p>
    </x-slot:footer>

</x-email.layout>
