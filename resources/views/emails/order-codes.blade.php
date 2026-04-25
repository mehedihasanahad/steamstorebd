<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Steam Gift Card Codes — Order #{{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #030711; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #e5e7eb; }
        .container { max-width: 600px; margin: 0 auto; padding: 32px 16px; }
        .header { background: linear-gradient(135deg, #071428 0%, #040D1A 100%); border-radius: 16px; padding: 40px 32px; text-align: center; margin-bottom: 24px; border: 1px solid rgba(37,99,235,0.3); }
        .logo { font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .logo span { color: #4B8FEF; }
        .order-badge { display: inline-block; background: rgba(102,192,244,0.15); border: 1px solid rgba(102,192,244,0.3); color: #4B8FEF; font-family: monospace; font-weight: 700; padding: 6px 16px; border-radius: 999px; font-size: 14px; margin-top: 12px; }
        .success-box { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px; }
        .success-box h2 { color: #4ade80; font-size: 20px; margin-bottom: 6px; }
        .success-box p { color: #9ca3af; font-size: 14px; }
        .section { background: #111827; border: 1px solid #374151; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
        .section h3 { color: #fff; font-size: 16px; font-weight: 700; margin-bottom: 16px; }
        .code-box { background: #1f2937; border: 1px solid #4b5563; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; }
        .code-label { color: #9ca3af; font-size: 12px; margin-bottom: 8px; }
        .code-value { font-family: 'Courier New', Courier, monospace; color: #4B8FEF; font-size: 18px; font-weight: 700; letter-spacing: 0.1em; word-break: break-all; }
        .order-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #374151; font-size: 14px; }
        .order-row:last-child { border-bottom: none; }
        .order-row .label { color: #9ca3af; }
        .order-row .value { color: #fff; font-weight: 600; }
        .order-row .price { color: #4B8FEF; font-weight: 700; }
        .instructions { background: #1f2937; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .instructions h4 { color: #fff; font-size: 14px; font-weight: 600; margin-bottom: 12px; }
        .instructions ol { color: #9ca3af; font-size: 13px; line-height: 2; padding-left: 20px; }
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

    <div class="success-box">
        <h2>✅ Payment Confirmed!</h2>
        <p>Hi {{ $order->customer_name }}, your Steam gift card codes are ready below.</p>
    </div>

    {{-- Gift Card Codes --}}
    <div class="section">
        <h3>🎮 Your Gift Card Codes</h3>
        @foreach($order->items as $item)
        <div style="margin-bottom: 20px;">
            <p style="color: #9ca3af; font-size: 13px; margin-bottom: 10px;">{{ $item->giftCard->name }} × {{ $item->quantity }}</p>
            @foreach($item->orderItemCodes as $itemCode)
            <div class="code-box">
                <div class="code-label">Steam Wallet Code</div>
                <div class="code-value">{{ $itemCode->giftCardCode->code }}</div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    {{-- Order Summary --}}
    <div class="section">
        <h3>📋 Order Summary</h3>
        @foreach($order->items as $item)
        <div class="order-row">
            <span class="label">{{ $item->giftCard->name }} × {{ $item->quantity }}</span>
            <span class="price">৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}</span>
        </div>
        @endforeach
        <div class="order-row" style="margin-top: 8px; padding-top: 12px; border-top: 1px solid #4b5563;">
            <span class="label" style="font-weight: 700; color: #fff;">Total Paid</span>
            <span class="price" style="font-size: 18px;">৳ {{ number_format($order->total_bdt, 0, '.', ',') }}</span>
        </div>
    </div>

    {{-- How to Redeem --}}
    <div class="instructions">
        <h4>How to Redeem on Steam</h4>
        <ol>
            <li>Open Steam and sign into your account</li>
            <li>Click your username in the top right corner</li>
            <li>Select "Account Details" → "Add Funds to Your Steam Wallet"</li>
            <li>Click "Redeem a Steam Gift Card or Wallet Code"</li>
            <li>Enter your code and click "Continue"</li>
        </ol>
    </div>

    <div class="footer">
        <p>Need help? <a href="{{ route('contact') }}">Contact our support</a></p>
        <p style="margin-top: 8px;">Steam Store BD — <a href="{{ route('home') }}">steamstorebd.com</a></p>
        <p style="margin-top: 12px;">Steam Store BD is not affiliated with Valve Corporation.<br>Steam and the Steam logo are trademarks of Valve Corporation.</p>
    </div>
</div>
</body>
</html>
