@include('emails.partials.plain-header', ['eyebrow' => 'Order received', 'heading' => 'Order #' . $order->order_number . ' — under review'])

Hi {{ $order->customer_name }},

We have your order and are verifying your {{ $order->paymentMethodLabel() }} payment.
Your code will be delivered to this email within 2–5 minutes.

PAYMENT DETAILS
---------------
Payment method : {{ $order->paymentMethodLabel() }}
Transaction ID : {{ $order->send_money_trx_id }}
Amount paid    : ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}

ORDER SUMMARY
-------------
@foreach($order->items as $item)
{{ $item->giftCard->name }} x{{ $item->quantity }}: ৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}
@endforeach
Total: ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}

WHAT HAPPENS NEXT?
------------------
1. Our team verifies your {{ $order->paymentMethodLabel() }} transaction
2. Once confirmed, your code is sent to this email
3. This usually takes 2–5 minutes

@include('emails.partials.plain-footer', ['disclaimer' => site_setting('site_name', 'Steam Store BD') . ' is an independent reseller and is not affiliated with Valve Corporation.'])
