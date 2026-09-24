@include('emails.partials.plain-header', ['eyebrow' => 'Admin notification', 'heading' => '[ACTION REQUIRED] New send money order #' . $order->order_number])

A customer has placed a {{ $order->paymentMethodLabel() }} order.
Verify the transaction and send the codes.

CUSTOMER DETAILS
----------------
Name  : {{ $order->customer_name }}
Email : {{ $order->customer_email }}
@if($order->customer_phone)
Phone : {{ $order->customer_phone }}
@endif

PAYMENT DETAILS
---------------
Payment method : {{ $order->paymentMethodLabel() }}
Amount         : ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}
Transaction ID : {{ $order->send_money_trx_id }}

*** VERIFY THIS TRANSACTION ID IN YOUR {{ strtoupper($order->walletLabel()) }} APP ***

ORDER ITEMS
-----------
@foreach($order->items as $item)
{{ $item->giftCard->name }} x{{ $item->quantity }}: ৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}
@endforeach
Total: ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}

NEXT STEP
---------
Approve the order and release the codes from the admin panel:
{{ url('/admin/orders') }}

@include('emails.partials.plain-footer', ['support' => false, 'disclaimer' => 'This is an automated notification.'])
