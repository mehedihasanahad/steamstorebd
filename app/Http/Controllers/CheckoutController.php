<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\BkashService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private BkashService $bkashService,
    ) {}

    public function index(Request $request)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        $cartItems = $this->resolveCartItems($cart);

        if ($cartItems === null) {
            return redirect()->route('cart')->with('error', 'Some items in your cart are no longer available.');
        }

        $paymentMethods = $this->enabledPaymentMethods();

        return view('storefront.checkout', compact('cartItems', 'paymentMethods'));
    }

    public function cart(): \Illuminate\View\View
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return view('storefront.cart', ['cartItems' => []]);
        }

        $cartItems = $this->resolveCartItems($cart);

        if ($cartItems === null) {
            Session::forget('cart');
            session()->flash('error', 'Some items are no longer available and have been removed from your cart.');
            return view('storefront.cart', ['cartItems' => []]);
        }

        return view('storefront.cart', compact('cartItems'));
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'gift_card_id' => ['required', 'exists:gift_cards,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $giftCard = GiftCard::findOrFail($request->gift_card_id);

        if ($giftCard->stock_count < $request->quantity) {
            return back()->with('error', 'Only ' . $giftCard->stock_count . ' available in stock.');
        }

        $cart = Session::get('cart', []);
        $cart[$giftCard->id] = [
            'gift_card_id' => $giftCard->id,
            'quantity' => (int) $request->quantity,
            'price' => $giftCard->price_bdt,
        ];
        Session::put('cart', $cart);

        if ($request->input('redirect_to') === 'checkout') {
            return redirect()->route('checkout');
        }

        return back()->with('success', 'Added to cart!');
    }

    public function removeFromCart(Request $request, int $giftCardId)
    {
        $cart = Session::get('cart', []);
        unset($cart[$giftCardId]);
        Session::put('cart', $cart);

        return back()->with('success', 'Item removed.');
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
        ]);

        $cart = Session::get('cart', []);
        if (empty($cart)) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $cartItems = $this->resolveCartItems($cart);
        if ($cartItems === null) {
            return redirect()->route('shop')->with('error', 'Some items are out of stock.');
        }

        try {
            $customerData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => auth()->user()->phone ?? '',
            ];

            $order = $this->orderService->createOrder($customerData, $cartItems);

            $order->user_id = auth()->id();
            $order->save();

            Session::put('current_order_id', $order->id);

            $bkashResponse = $this->bkashService->initiatePayment($order);

            if (isset($bkashResponse['bkashURL'])) {
                return redirect()->away($bkashResponse['bkashURL']);
            }

            Log::error('bKash initiate failed', $bkashResponse);
            $this->orderService->failOrder($order);
            return back()->with('error', 'Payment initiation failed. Please try again.');

        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function placeManualOrder(Request $request)
    {
        $enabledMethods = $this->enabledPaymentMethods();
        $allowedMethods = array_filter(['bkash_send_money', 'nagad_send_money'], fn($m) => in_array($m, $enabledMethods));

        $request->validate([
            'name'               => ['required', 'string', 'max:100'],
            'email'              => ['required', 'email', 'max:100'],
            'payment_method'     => ['required', 'in:' . implode(',', array_values($allowedMethods))],
            'send_money_trx_id'  => ['required', 'string', 'max:100'],
        ]);

        $cart = Session::get('cart', []);
        if (empty($cart)) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $cartItems = $this->resolveCartItems($cart);
        if ($cartItems === null) {
            return redirect()->route('cart')->with('error', 'Some items are out of stock.');
        }

        try {
            $customerData = [
                'name'  => $request->name,
                'email' => $request->email,
                'phone' => auth()->user()->phone ?? '',
            ];

            $order = $this->orderService->createSendMoneyOrder(
                $customerData,
                $cartItems,
                $request->payment_method,
                trim($request->send_money_trx_id),
                auth()->id()
            );

            Session::forget('cart');

            return redirect()->route('checkout.pending', $order->order_number);

        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function success(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.giftCard', 'items.orderItemCodes.giftCardCode'])
            ->firstOrFail();

        if (! in_array($order->status, ['paid', 'completed'])) {
            return redirect()->route('home')->with('error', 'Order not found or not yet confirmed.');
        }

        Session::forget('cart');

        return view('storefront.checkout-success', compact('order'));
    }

    public function pending(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.giftCard'])
            ->firstOrFail();

        if ($order->status !== 'pending_review') {
            if (in_array($order->status, ['paid', 'completed'])) {
                return redirect()->route('checkout.success', $orderNumber);
            }
            return redirect()->route('home');
        }

        return view('storefront.checkout-pending', compact('order'));
    }

    public function failed()
    {
        return view('storefront.checkout-failed');
    }

    private function enabledPaymentMethods(): array
    {
        $methods = [];

        if (SiteSetting::get('payment_bkash_online_enabled', true)) {
            $methods[] = 'bkash_online';
        }
        if (SiteSetting::get('payment_bkash_send_money_enabled', false)) {
            $methods[] = 'bkash_send_money';
        }
        if (SiteSetting::get('payment_nagad_send_money_enabled', false)) {
            $methods[] = 'nagad_send_money';
        }

        return $methods ?: ['bkash_online'];
    }

    private function resolveCartItems(array $cart): ?array
    {
        $items = [];
        foreach ($cart as $id => $item) {
            $giftCard = GiftCard::find($id);
            if (! $giftCard || $giftCard->stock_count < $item['quantity']) {
                return null;
            }
            $items[] = [
                'gift_card_id' => $giftCard->id,
                'gift_card' => $giftCard,
                'quantity' => $item['quantity'],
                'price' => $giftCard->price_bdt,
            ];
        }
        return $items;
    }
}
