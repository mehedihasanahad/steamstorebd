Steam Store BD — Admin Notification
=====================================

[ACTION REQUIRED] New Send Money Order #{{ $order->order_number }}

A customer has placed a {{ $order->paymentMethodLabel() }} order.
Please verify the transaction and send the gift card code.

CUSTOMER DETAILS
----------------
Name  : {{ $order->customer_name }}
Email : {{ $order->customer_email }}
@if($order->customer_phone)
Phone : {{ $order->customer_phone }}
@endif

PAYMENT DETAILS
---------------
Payment Method : {{ $order->paymentMethodLabel() }}
Amount         : ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}
Transaction ID : {{ $order->send_money_trx_id }}

*** VERIFY THIS TRANSACTION ID IN YOUR {{ strtoupper($order->payment_method === 'nagad_send_money' ? 'Nagad' : 'bKash') }} APP ***

ORDER ITEMS
-----------
@foreach($order->items as $item)
{{ $item->giftCard->name }} x{{ $item->quantity }}   ৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}
@endforeach

Total: ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}

ACTION: Go to Admin Panel → Orders → Approve & Send Codes
{{ url('/admin/orders') }}

This is an automated notification from Steam Store BD.
