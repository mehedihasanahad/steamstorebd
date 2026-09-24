@php
    $codeItems      = $order->items->filter(fn ($item) => $item->orderItemCodes->isNotEmpty());
    $deliveredItems = $order->items->filter(fn ($item) => $item->isFulfilled() && filled($item->delivered_payload));
    $awaitingItems  = $order->items->filter(fn ($item) => $item->needsFulfilment());
@endphp
@include('emails.partials.plain-header', ['eyebrow' => 'Order confirmation', 'heading' => 'Order #' . $order->order_number])

Hi {{ $order->customer_name }},
@if($awaitingItems->isNotEmpty())

Your payment was confirmed. Everything we can deliver instantly is below; the
rest is with our team now.
@else

Your payment was confirmed and your order is below.
@endif
@if($codeItems->isNotEmpty())

YOUR CODES
----------
@foreach($codeItems as $item)
{{ $item->giftCard->name }} (x{{ $item->quantity }}) — {{ $item->deliveryLabel() }}:
@foreach($item->orderItemCodes as $itemCode)
  {{ $itemCode->giftCardCode->code }}
@endforeach
@endforeach

REDEEMING YOUR CODE
-------------------
1. Sign in to the platform the code is for
2. Open its wallet, billing or redeem page
3. Choose "Redeem a code" or "Add funds"
4. Paste the code exactly as it appears above

Per-brand guides: {{ route('how-to-redeem') }}
@endif
@if($deliveredItems->isNotEmpty())

YOUR ACCOUNT DETAILS
--------------------
@foreach($deliveredItems as $item)
{{ $item->giftCard->name }} (x{{ $item->quantity }}) — {{ $item->deliveryLabel() }}:
{{ $item->delivered_payload }}
@endforeach

Keep these private. Anyone with them can use the account.
@endif
@if($awaitingItems->isNotEmpty())

STILL BEING DELIVERED
---------------------
@foreach($awaitingItems as $item)
{{ $item->giftCard->name }} x{{ $item->quantity }} — {{ $item->giftCard->delivery_eta_label ?: 'In progress' }}
@endforeach

We will e-mail you again as soon as these are done.
@endif

ORDER SUMMARY
-------------
@foreach($order->items as $item)
{{ $item->giftCard->name }} x{{ $item->quantity }}: ৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}
@endforeach
Total paid: ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}

LEAVE A REVIEW
--------------
Happy with your purchase? A short review helps the next buyer decide.
{{ route('orders.show', $order->order_number) }}

@include('emails.partials.plain-footer', ['disclaimer' => site_setting('site_name', 'Steam Store BD') . ' is an independent reseller and is not affiliated with Valve Corporation. All brand names are property of their respective owners.'])
