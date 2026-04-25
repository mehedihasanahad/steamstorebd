<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[ACTION REQUIRED] New Order #{{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #030711; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #e5e7eb; }
        .container { max-width: 600px; margin: 0 auto; padding: 32px 16px; }
        .header { background: linear-gradient(135deg, #071428 0%, #040D1A 100%); border-radius: 16px; padding: 40px 32px; text-align: center; margin-bottom: 24px; border: 1px solid rgba(37,99,235,0.3); }
        .logo { font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .logo span { color: #4B8FEF; }
        .order-badge { display: inline-block; background: rgba(102,192,244,0.15); border: 1px solid rgba(102,192,244,0.3); color: #4B8FEF; font-family: monospace; font-weight: 700; padding: 6px 16px; border-radius: 999px; font-size: 14px; margin-top: 12px; }
        .alert-box { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px; }
        .alert-box h2 { color: #f87171; font-size: 20px; margin-bottom: 6px; }
        .alert-box p { color: #9ca3af; font-size: 14px; }
        .section { background: #111827; border: 1px solid #374151; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
        .section h3 { color: #fff; font-size: 16px; font-weight: 700; margin-bottom: 16px; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #374151; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .label { color: #9ca3af; }
        .detail-row .value { color: #fff; font-weight: 600; }
        .detail-row .price { color: #4B8FEF; font-weight: 700; }
        .trx-highlight { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.3); border-radius: 10px; padding: 14px 18px; margin: 16px 0; }
        .trx-highlight .trx-label { color: #9ca3af; font-size: 12px; margin-bottom: 4px; }
        .trx-highlight .trx-value { font-family: 'Courier New', Courier, monospace; color: #4ade80; font-size: 20px; font-weight: 700; letter-spacing: 0.05em; }
        .cta-btn { display: block; background: linear-gradient(135deg, #2563EB, #1D4ED8); color: #fff; text-decoration: none; text-align: center; font-weight: 700; font-size: 16px; padding: 16px 32px; border-radius: 12px; margin: 24px 0; }
        .footer { text-align: center; padding: 24px 0; }
        .footer p { color: #6b7280; font-size: 12px; line-height: 1.8; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">Steam Store <span>BD</span></div>
        <p style="color: #9ca3af; font-size: 13px; margin-top: 4px;">Admin Notification</p>
        <div class="order-badge">Order #{{ $order->order_number }}</div>
    </div>

    <div class="alert-box">
        <h2>🔔 New Send Money Order — Action Required</h2>
        <p>A customer has placed a <strong style="color:#fff;">{{ $order->paymentMethodLabel() }}</strong> order.<br>
        Please verify the transaction and send the gift card code.</p>
    </div>

    {{-- Customer Info --}}
    <div class="section">
        <h3>👤 Customer Details</h3>
        <div class="detail-row">
            <span class="label">Name</span>
            <span class="value">{{ $order->customer_name }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Email</span>
            <span class="value">{{ $order->customer_email }}</span>
        </div>
        @if($order->customer_phone)
        <div class="detail-row">
            <span class="label">Phone</span>
            <span class="value">{{ $order->customer_phone }}</span>
        </div>
        @endif
    </div>

    {{-- Payment Info --}}
    <div class="section">
        <h3>💳 Payment Details</h3>
        <div class="detail-row">
            <span class="label">Payment Method</span>
            <span class="value">{{ $order->paymentMethodLabel() }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Amount</span>
            <span class="price">৳ {{ number_format($order->total_bdt, 0, '.', ',') }}</span>
        </div>
        <div class="trx-highlight">
            <div class="trx-label">TRANSACTION ID (verify this)</div>
            <div class="trx-value">{{ $order->send_money_trx_id }}</div>
        </div>
    </div>

    {{-- Order Items --}}
    <div class="section">
        <h3>📋 Order Items</h3>
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

    <a href="{{ url('/admin/orders') }}" class="cta-btn">
        → Open Admin Panel to Approve & Send Codes
    </a>

    <div class="footer">
        <p>This is an automated notification from Steam Store BD.</p>
    </div>
</div>
</body>
</html>
