Steam Store BD — Order Confirmation
Order #{{ $order->order_number }}

Hello {{ $order->customer_name }},

Your payment was confirmed and your Steam gift card codes are ready!

=== YOUR GIFT CARD CODES ===
@foreach($order->items as $item)
{{ $item->giftCard->name }} (x{{ $item->quantity }}):
@foreach($item->orderItemCodes as $itemCode)
  {{ $itemCode->giftCardCode->code }}
@endforeach
@endforeach

=== HOW TO REDEEM ===
1. Open Steam and sign in
2. Click username → Account Details → Add Funds
3. Click "Redeem a Steam Gift Card or Wallet Code"
4. Enter your code and confirm

=== ORDER SUMMARY ===
@foreach($order->items as $item)
{{ $item->giftCard->name }} x{{ $item->quantity }}: ৳ {{ number_format($item->subtotal_bdt, 0, '.', ',') }}
@endforeach
Total: ৳ {{ number_format($order->total_bdt, 0, '.', ',') }}

Need help? Visit {{ route('contact') }}

Steam Store BD is not affiliated with Valve Corporation.
