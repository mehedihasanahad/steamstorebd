<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Models\GiftCard;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\BkashService;
use App\Services\Cart;
use App\Services\OrderService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService    $orderService,
        private BkashService    $bkashService,
        private ReferralService $referralService,
        private Cart            $cart,
    ) {}

    public function index()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }

        $cartItems = $this->cart->checkoutItems();

        if ($cartItems === null) {
            return redirect()->route('cart')->with('error', 'Some items in your cart are no longer available.');
        }

        $paymentMethods   = $this->enabledPaymentMethods();
        $referralSettings = $this->referralService->getSettings();
        $walletBalance    = (float) (auth()->user()->wallet_balance ?? 0);

        return view('storefront.checkout', compact('cartItems', 'paymentMethods', 'referralSettings', 'walletBalance'));
    }

    public function cart(): \Illuminate\View\View
    {
        return view('storefront.cart', [
            'groups'        => $this->cart->grouped(),
            'selectedCount' => $this->cart->selectedCount(),
            'subtotal'      => $this->cart->subtotal(),
            'savings'       => $this->cart->savings(),
        ]);
    }

    public function addToCart(AddToCartRequest $request)
    {
        $giftCard = $request->card();

        if ($giftCard->stock_count < $request->integer('quantity')) {
            return back()->with('error', 'Only ' . $giftCard->stock_count . ' available in stock.');
        }

        $this->cart->add($giftCard, $request->integer('quantity'), $request->buyerInputs());

        if ($request->input('redirect_to') === 'checkout') {
            return redirect()->route('checkout');
        }

        return back()->with('success', 'Added to cart!');
    }

    public function updateQuantity(Request $request)
    {
        $request->validate([
            'gift_card_id' => ['required', 'exists:gift_cards,id'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'key'          => ['nullable', 'string', 'max:64'],
        ]);

        $giftCard = GiftCard::findOrFail($request->gift_card_id);

        $request->validate(['quantity' => $this->quantityRules($giftCard)]);

        if ($giftCard->stock_count < $request->quantity) {
            return response()->json(['error' => 'Only ' . $giftCard->stock_count . ' available in stock.'], 422);
        }

        // Pre-deploy carts and single-line products key by the card id alone;
        // lines carrying buyer inputs send their own composite key.
        $key = $request->filled('key') ? $request->input('key') : $giftCard->id;

        if (! $this->cart->setQuantity($key, (int) $request->quantity)) {
            return response()->json(['error' => 'Item not in cart.'], 404);
        }

        return response()->json(['ok' => true]);
    }

    /** Tick or untick one line. Unticked lines stay in the cart but do not check out. */
    public function updateSelection(Request $request)
    {
        $request->validate([
            'key'      => ['required', 'string', 'max:64'],
            'selected' => ['required', 'boolean'],
        ]);

        $key = ctype_digit($request->input('key')) ? (int) $request->input('key') : $request->input('key');

        if (! $this->cart->setSelected($key, $request->boolean('selected'))) {
            return response()->json(['error' => 'Item not in cart.'], 404);
        }

        return response()->json([
            'ok'       => true,
            'selected' => $this->cart->selectedCount(),
            'subtotal' => $this->cart->subtotal(),
            'savings'  => $this->cart->savings(),
        ]);
    }

    public function removeFromCart(string $cartKey)
    {
        $this->cart->remove(ctype_digit($cartKey) ? (int) $cartKey : $cartKey);

        return back()->with('success', 'Item removed.');
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $cartItems = $this->cart->checkoutItems();
        if ($cartItems === null) {
            return redirect()->route('home')->with('error', 'Some items are out of stock.');
        }

        try {
            $customerData = [
                'name'  => $request->name,
                'email' => $request->email,
                'phone' => auth()->user()->phone ?? '',
            ];

            $discountData = $this->resolveDiscountData($request, $cartItems);

            $order = $this->orderService->createOrder($customerData, $cartItems, $discountData);

            $order->user_id = auth()->id();
            $order->save();

            // Record referral usage for bKash-online orders (wallet debit happens in completeOrder)
            if (! empty($discountData['referral_code'])) {
                $referrer = \App\Models\User::where('referral_code', strtoupper($discountData['referral_code']))->first();
                if ($referrer) {
                    $this->referralService->recordUsage($order, $referrer, (float) $discountData['referral_discount']);
                }
            }

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
        $enabledMethods  = $this->enabledPaymentMethods();
        $allowedMethods  = array_filter(
            ['bkash_send_money', 'nagad_send_money', 'rocket_send_money'],
            fn($m) => in_array($m, $enabledMethods)
        );

        $request->validate([
            'name'              => ['required', 'string', 'max:100'],
            'email'             => ['required', 'email', 'max:100'],
            'payment_method'    => ['required', 'in:' . implode(',', array_values($allowedMethods))],
            'send_money_trx_id' => ['required', 'string', 'max:100'],
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $cartItems = $this->cart->checkoutItems();
        if ($cartItems === null) {
            return redirect()->route('cart')->with('error', 'Some items are out of stock.');
        }

        try {
            $customerData = [
                'name'  => $request->name,
                'email' => $request->email,
                'phone' => auth()->user()->phone ?? '',
            ];

            $discountData = $this->resolveDiscountData($request, $cartItems);

            $order = $this->orderService->createSendMoneyOrder(
                $customerData,
                $cartItems,
                $request->payment_method,
                trim($request->send_money_trx_id),
                auth()->id(),
                $discountData,
            );

            $this->clearOrderedLines();

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

        if (! in_array($order->status, ['paid', 'completed', 'processing'])) {
            return redirect()->route('home')->with('error', 'Order not found or not yet confirmed.');
        }

        $this->cart->forget();

        $referralSettings = $this->referralService->getSettings();
        $referralCode     = Auth::check() ? Auth::user()->referral_code : null;

        return view('storefront.checkout-success', compact('order', 'referralSettings', 'referralCode'));
    }

    public function pending(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.giftCard'])
            ->firstOrFail();

        if ($order->status !== 'pending_review') {
            if (in_array($order->status, ['paid', 'completed', 'processing'])) {
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

    /**
     * Drop the lines that just became an order, leaving any the shopper had
     * unticked. Emptying the whole cart would throw away a deliberate choice.
     */
    private function clearOrderedLines(): void
    {
        foreach ($this->cart->selected() as $line) {
            $this->cart->remove($line['key']);
        }

        if ($this->cart->isEmpty()) {
            $this->cart->forget();
        }
    }

    /**
     * How many of this card a buyer may take in one line. Cards default to
     * min 1 / max 10, which is the cap this controller used to hardcode, so
     * every existing card behaves exactly as it did.
     *
     * @return list<string>
     */
    private function quantityRules(GiftCard $giftCard): array
    {
        return [
            'required',
            'integer',
            'min:' . max(1, $giftCard->min_quantity),
            'max:' . max(1, $giftCard->max_quantity),
        ];
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
        if (SiteSetting::get('payment_rocket_send_money_enabled', false)) {
            $methods[] = 'rocket_send_money';
        }

        return $methods ?: ['bkash_online'];
    }

    /**
     * Validate and resolve referral + wallet discounts from the request.
     * Re-validates server-side to prevent tampering.
     */
    private function resolveDiscountData(Request $request, array $cartItems): array
    {
        $subtotal         = collect($cartItems)->sum(fn($i) => $i['price'] * $i['quantity']);
        $referralDiscount = 0.0;
        $walletDiscount   = 0.0;
        $referralCode     = null;

        // Validate referral code
        if ($this->referralService->isEnabled() && $request->filled('referral_code')) {
            $result = $this->referralService->validateCode(
                $request->referral_code,
                auth()->user(),
                $subtotal
            );
            if ($result['valid']) {
                $referralCode     = strtoupper(trim($request->referral_code));
                $referralDiscount = $result['discount'];
            }
        }

        // Validate wallet usage
        if ($request->boolean('use_wallet') && auth()->check()) {
            $user          = auth()->user();
            $available     = (float) $user->wallet_balance;
            $remaining     = max(0, $subtotal - $referralDiscount);
            $walletDiscount = min($available, $remaining);
        }

        return [
            'referral_code'     => $referralCode,
            'referral_discount' => $referralDiscount,
            'wallet_discount'   => $walletDiscount,
        ];
    }
}
