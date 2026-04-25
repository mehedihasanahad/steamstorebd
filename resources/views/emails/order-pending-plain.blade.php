Steam Store BD
==============

Order #{{ $order->order_number }} — Under Review

Hi {{ $order->customer_name }},

We've received your order and are verifying your {{ $order->paymentMethodLabel() }} payment.
Your gift card code will be delivered to this email within 2–5 minutes.

PAYMENT DETAILS
---------------
Payment Method : {{ $order->paymentMethodLabel() }}
Transaction ID : {{ $order->send_money_trx_id }}
Amount Paid    : ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}

ORDER SUMMARY
-------------
@foreach($order->items as $item)
{{ $item->giftCard->name }} x{{ $item->quantity }}   ৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}
@endforeach

Total: ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}

WHAT HAPPENS NEXT?
------------------
1. Our team verifies your {{ $order->paymentMethodLabel() }} transaction
2. Once confirmed, your Steam gift card code is sent to this email
3. This usually takes 2–5 minutes

Need help? Visit {{ route('contact') }}

Steam Store BD — {{ route('home') }}
Steam Store BD is not affiliated with Valve Corporation.
