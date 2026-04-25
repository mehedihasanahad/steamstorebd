<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #{{ $order->order_number }} Received</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #030711; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #e5e7eb; }
        .container { max-width: 600px; margin: 0 auto; padding: 32px 16px; }
        .header { background: linear-gradient(135deg, #071428 0%, #040D1A 100%); border-radius: 16px; padding: 40px 32px; text-align: center; margin-bottom: 24px; border: 1px solid rgba(37,99,235,0.3); }
        .logo { font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .logo span { color: #4B8FEF; }
        .order-badge { display: inline-block; background: rgba(102,192,244,0.15); border: 1px solid rgba(102,192,244,0.3); color: #4B8FEF; font-family: monospace; font-weight: 700; padding: 6px 16px; border-radius: 999px; font-size: 14px; margin-top: 12px; }
        .info-box { background: rgba(234,179,8,0.1); border: 1px solid rgba(234,179,8,0.3); border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px; }
        .info-box h2 { color: #fbbf24; font-size: 20px; margin-bottom: 6px; }
        .info-box p { color: #9ca3af; font-size: 14px; }
        .section { background: #111827; border: 1px solid #374151; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
        .section h3 { color: #fff; font-size: 16px; font-weight: 700; margin-bottom: 16px; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #374151; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .label { color: #9ca3af; }
        .detail-row .value { color: #fff; font-weight: 600; }
        .detail-row .price { color: #4B8FEF; font-weight: 700; }
        .trx-box { background: #1f2937; border: 1px solid #4b5563; border-radius: 12px; padding: 16px 20px; margin-top: 16px; }
        .trx-label { color: #9ca3af; font-size: 12px; margin-bottom: 6px; }
        .trx-value { font-family: 'Courier New', Courier, monospace; color: #4ade80; font-size: 16px; font-weight: 700; letter-spacing: 0.05em; }
        .steps { background: #1f2937; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .steps h4 { color: #fff; font-size: 14px; font-weight: 600; margin-bottom: 12px; }
        .steps ol { color: #9ca3af; font-size: 13px; line-height: 2.2; padding-left: 20px; }
        .footer { text-align: center; padding: 24px 0; }
        .footer p { color: #6b7280; font-size: 12px; line-height: 1.8; }
        .footer a { color: #4B8FEF; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">Steam Store <span>BD</span></div>
        <p style="color: #9ca3af; font-size: 13px; margin-top: 4px;">Your Digital Gift Card Store</p>
        <div class="order-badge">Order #{{ $order->order_number }}</div>
    </div>

    <div class="info-box">
        <h2>⏳ Order Received — Under Review</h2>
        <p>Hi {{ $order->customer_name }}, we've received your order and are verifying your payment.<br>
        Your gift card code will be delivered within <strong style="color:#fbbf24;">2–5 minutes</strong>.</p>
    </div>

    {{-- Payment Details --}}
    <div class="section">
        <h3>💳 Payment Details</h3>
        <div class="detail-row">
            <span class="label">Payment Method: </span>
            <span class="value">{{ $order->paymentMethodLabel() }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Your Transaction ID: </span>
            <span class="value" style="font-family:monospace;">{{ $order->send_money_trx_id }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Amount Paid: </span>
            <span class="price">৳ {{ number_format($order->total_bdt, 0, '.', ',') }}</span>
        </div>
    </div>

    {{-- Order Summary --}}
    <div class="section">
        <h3>📋 Order Summary</h3>
        @foreach($order->items as $item)
        <div class="detail-row">
            <span class="label">{{ $item->giftCard->name }} × {{ $item->quantity }}</span>
            <span class="price">৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}</span>
        </div>
        @endforeach
        <div class="detail-row" style="margin-top: 8px; padding-top: 12px; border-top: 1px solid #4b5563;">
            <span class="label" style="font-weight: 700; color: #fff;">Total</span>
            <span class="price" style="font-size: 18px;">৳ {{ number_format($order->total_bdt, 0, '.', ',') }}</span>
        </div>
    </div>

    <div class="steps">
        <h4>What happens next?</h4>
        <ol>
            <li>Our team verifies your {{ $order->paymentMethodLabel() }} transaction</li>
            <li>Once confirmed, your Steam gift card code is sent to this email</li>
            <li>This usually takes <strong style="color:#fff;">2–5 minutes</strong></li>
        </ol>
    </div>

    <div class="footer">
        <p>Questions? <a href="{{ route('contact') }}">Contact our support</a></p>
        <p style="margin-top: 8px;">Steam Store BD — <a href="{{ route('home') }}">steamstorebd.com</a></p>
        <p style="margin-top: 12px;">Steam Store BD is not affiliated with Valve Corporation.</p>
    </div>
</div>
</body>
</html>
