@php
    // Three delivery shapes on one order: codes pulled from the pool, details
    // an admin sent by hand, and lines still in the queue. Each is named for
    // what it actually is, rather than labelled "Steam Wallet Code" whatever
    // the buyer bought.
    $codeItems      = $order->items->filter(fn ($item) => $item->orderItemCodes->isNotEmpty());
    $deliveredItems = $order->items->filter(fn ($item) => $item->isFulfilled() && filled($item->delivered_payload));
    $awaitingItems  = $order->items->filter(fn ($item) => $item->needsFulfilment());
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your order #{{ $order->order_number }} — Steam Store BD</title>
    <style>
        /* Light background on purpose: dark-background HTML mail renders
           unpredictably across clients, so e-mail is the one deliberate
           exception to the storefront's dark theme. */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #F4F4F5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #3F3F46; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .header { background: #FFFFFF; border: 1px solid #E4E4E7; border-radius: 10px; padding: 28px 24px; text-align: center; margin-bottom: 16px; }
        .logo { font-size: 22px; font-weight: 800; color: #18181B; }
        .logo span { color: #2563EB; }
        .order-badge { display: inline-block; background: #EFF6FF; border: 1px solid #BFDBFE; color: #2563EB; font-family: monospace; font-weight: 700; padding: 5px 14px; border-radius: 999px; font-size: 13px; margin-top: 10px; }
        .banner { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 18px; text-align: center; margin-bottom: 16px; }
        .banner h2 { color: #15803D; font-size: 17px; margin-bottom: 4px; }
        .banner p { color: #52525B; font-size: 13px; }
        .banner-wait { background: #FFFBEB; border-color: #FDE68A; }
        .banner-wait h2 { color: #B45309; }
        .section { background: #FFFFFF; border: 1px solid #E4E4E7; border-radius: 10px; padding: 20px; margin-bottom: 16px; }
        .section h3 { color: #18181B; font-size: 15px; font-weight: 700; margin-bottom: 14px; }
        .item-label { color: #71717A; font-size: 13px; margin-bottom: 8px; }
        .code-box { background: #FAFAFA; border: 1px solid #E4E4E7; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; }
        .code-label { color: #71717A; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.04em; }
        .code-value { font-family: 'Courier New', Courier, monospace; color: #18181B; font-size: 17px; font-weight: 700; letter-spacing: 0.08em; word-break: break-all; white-space: pre-wrap; }
        .order-row { padding: 9px 0; border-bottom: 1px solid #F4F4F5; font-size: 14px; }
        .order-row:last-child { border-bottom: none; }
        .order-row .label { color: #71717A; }
        .order-row .price { color: #16A34A; font-weight: 700; float: right; }
        .instructions { background: #FAFAFA; border: 1px solid #E4E4E7; border-radius: 10px; padding: 18px; margin-bottom: 16px; }
        .instructions h4 { color: #18181B; font-size: 14px; font-weight: 600; margin-bottom: 10px; }
        .instructions ol { color: #52525B; font-size: 13px; line-height: 1.9; padding-left: 18px; }
        .btn { display: inline-block; background: #2563EB; color: #FFFFFF; font-size: 14px; font-weight: 700; text-decoration: none; padding: 12px 28px; border-radius: 8px; }
        .footer { text-align: center; padding: 20px 0; }
        .footer p { color: #A1A1AA; font-size: 12px; line-height: 1.8; }
        .footer a { color: #2563EB; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">Steam Store <span>BD</span></div>
        <p style="color: #71717A; font-size: 12px; margin-top: 4px;">Gift cards, top-ups, keys and subscriptions</p>
        <div class="order-badge">Order #{{ $order->order_number }}</div>
    </div>

    <div class="banner {{ $awaitingItems->isNotEmpty() ? 'banner-wait' : '' }}">
        @if($awaitingItems->isNotEmpty())
            <h2>Payment confirmed — part of your order is on the way</h2>
            <p>Hi {{ $order->customer_name }}, everything we can deliver instantly is below. The rest is with our team now.</p>
        @else
            <h2>Payment confirmed</h2>
            <p>Hi {{ $order->customer_name }}, your order is below.</p>
        @endif
    </div>

    @if($codeItems->isNotEmpty())
    <div class="section">
        <h3>Your codes</h3>
        @foreach($codeItems as $item)
        <div style="margin-bottom: 16px;">
            <p class="item-label">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</p>
            @foreach($item->orderItemCodes as $itemCode)
            <div class="code-box">
                <div class="code-label">{{ $item->deliveryLabel() }}</div>
                <div class="code-value">{{ $itemCode->giftCardCode->code }}</div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    @endif

    @if($deliveredItems->isNotEmpty())
    <div class="section">
        <h3>Your account details</h3>
        @foreach($deliveredItems as $item)
        <div style="margin-bottom: 16px;">
            <p class="item-label">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</p>
            <div class="code-box">
                <div class="code-label">{{ $item->deliveryLabel() }}</div>
                <div class="code-value">{{ $item->delivered_payload }}</div>
            </div>
        </div>
        @endforeach
        <p style="color: #71717A; font-size: 12px;">Keep these private. Anyone with them can use the account.</p>
    </div>
    @endif

    @if($awaitingItems->isNotEmpty())
    <div class="section">
        <h3>Still being delivered</h3>
        @foreach($awaitingItems as $item)
        <div class="order-row">
            <span class="label">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</span>
            <span class="price" style="color: #B45309;">{{ $item->giftCard->delivery_eta_label ?: 'In progress' }}</span>
            <div style="clear: both;"></div>
        </div>
        @endforeach
        <p style="color: #71717A; font-size: 12px; margin-top: 12px;">We will e-mail you again as soon as these are done. You can also follow them on your order page.</p>
    </div>
    @endif

    <div class="section">
        <h3>Order summary</h3>
        @foreach($order->items as $item)
        <div class="order-row">
            <span class="label">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</span>
            <span class="price">৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}</span>
            <div style="clear: both;"></div>
        </div>
        @endforeach
        <div class="order-row" style="margin-top: 6px; padding-top: 10px; border-top: 1px solid #E4E4E7;">
            <span class="label" style="font-weight: 700; color: #18181B;">Total paid</span>
            <span class="price" style="font-size: 17px;">৳ {{ number_format($order->total_bdt, 0, '.', ',') }}</span>
            <div style="clear: both;"></div>
        </div>
    </div>

    @if($codeItems->isNotEmpty())
    <div class="instructions">
        <h4>Redeeming your code</h4>
        <ol>
            <li>Sign in to the platform the code is for</li>
            <li>Open its wallet, billing or redeem page</li>
            <li>Choose "Redeem a code" or "Add funds"</li>
            <li>Paste the code exactly as it appears above</li>
        </ol>
        <p style="color: #71717A; font-size: 12px; margin-top: 10px;">
            Step-by-step guides per brand: <a href="{{ route('how-to-redeem') }}" style="color: #2563EB;">how to redeem</a>
        </p>
    </div>
    @endif

    <div class="section" style="text-align: center;">
        <h3 style="margin-bottom: 6px;">Happy with your purchase?</h3>
        <p style="color: #71717A; font-size: 13px; margin-bottom: 16px;">A short review helps the next buyer decide. It takes about a minute.</p>
        <a href="{{ route('orders.show', $order->order_number) }}" class="btn">Leave a review</a>
    </div>

    <div class="footer">
        <p>Need help? <a href="{{ route('contact') }}">Contact our support</a></p>
        <p style="margin-top: 6px;">Steam Store BD — <a href="{{ route('home') }}">steamstorebd.com</a></p>
        <p style="margin-top: 10px;">Steam Store BD is an independent reseller and is not affiliated with Valve Corporation.<br>All brand names are property of their respective owners.</p>
    </div>
</div>
</body>
</html>
