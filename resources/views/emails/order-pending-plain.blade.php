@php
    // Mirrors the HTML version: the delivery time comes from each card's
    // "Delivery time shown to buyers", never from a figure hardcoded here.
    $codeItems   = $order->items->reject(fn ($item) => $item->isManual());
    $manualItems = $order->items->filter(fn ($item) => $item->isManual());
    $sharedEta   = $order->sharedDeliveryEta();
    $units       = $order->items->sum('quantity');
@endphp
@include('emails.partials.plain-header', ['eyebrow' => 'Order received', 'heading' => 'Order #' . $order->order_number . ' — under review'])

Hi {{ $order->customer_name }},

We have your order and are verifying your {{ $order->paymentMethodLabel() }} payment.
@if($sharedEta && $manualItems->isEmpty())
Your {{ Str::plural('code', $units) }} will be delivered to this email within {{ $sharedEta }}.
@elseif($sharedEta && $codeItems->isEmpty())
Our team starts on your order as soon as it clears — delivery usually takes {{ $sharedEta }}.
@elseif($sharedEta)
Your order will be delivered within {{ $sharedEta }}.
@elseif($codeItems->isEmpty())
Our team starts on your order as soon as it clears. Delivery times are below.
@else
Delivery times differ by item, so each one is listed below.
@endif

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
@unless($sharedEta)

DELIVERY TIMES
--------------
@foreach($order->items as $item)
{{ $item->giftCard->name }} x{{ $item->quantity }} — {{ $item->deliveryEtaLabel() }}
@endforeach
@endunless

WHAT HAPPENS NEXT?
------------------
1. Our team verifies your {{ $order->paymentMethodLabel() }} transaction
@if($manualItems->isEmpty())
2. Once confirmed, your {{ Str::plural('code', $units) }} {{ $units > 1 ? 'are' : 'is' }} sent to this email
3. {{ $sharedEta ? 'This usually takes ' . $sharedEta : 'Delivery times are listed above' }}
@elseif($codeItems->isEmpty())
2. Once confirmed, our team fulfils your order by hand
3. {{ $sharedEta ? 'This usually takes ' . $sharedEta : 'We email you as soon as it is delivered' }}
@else
2. Once confirmed, your codes are sent to this email
3. The rest of your order is delivered by our team, and we email you when it is ready
@endif

@include('emails.partials.plain-footer', ['disclaimer' => site_setting('site_name', 'Steam Store BD') . ' is an independent reseller and is not affiliated with Valve Corporation.'])
