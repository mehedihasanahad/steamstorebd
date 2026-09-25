<?php

namespace App\Services;

use App\Jobs\SendAdminNewOrderEmail;
use App\Jobs\SendOrderCodesEmail;
use App\Jobs\SendOrderPendingEmail;
use App\Models\BkashPayment;
use App\Models\GiftCard;
use App\Models\GiftCardCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Turning a cart into an order, and moving that order through payment.
 *
 * Two delivery mechanisms live here. A card_pool line pulls one pre-stocked
 * GiftCardCode per unit and is delivered the instant payment clears — that is
 * what this service has always done and the code below is the same code,
 * moved into named methods rather than rewritten. A manual line (a game
 * top-up, a subscription) has no code to pull, so it reserves stock from a
 * counter instead and waits for an admin.
 *
 * The two mechanisms only ever meet at the order: an order holding both is
 * partly delivered and partly queued, which is exactly what the `processing`
 * status means.
 */
class OrderService
{
    public function __construct(private ReferralService $referralService) {}

    /**
     * @param array $discountData ['referral_code' => string|null, 'referral_discount' => float, 'wallet_discount' => float]
     */
    public function createOrder(array $customerData, array $cartItems, array $discountData = []): Order
    {
        return DB::transaction(function () use ($customerData, $cartItems, $discountData) {
            $order = Order::create($this->orderAttributes($customerData, $cartItems, $discountData, [
                'status' => 'pending',
            ]));

            $this->createItems($order, $cartItems);

            return $order->fresh(['items']);
        });
    }

    public function completeOrder(Order $order, array $bkashData): void
    {
        DB::transaction(function () use ($order, $bkashData) {
            BkashPayment::where('order_id', $order->id)->update([
                'status'         => 'completed',
                'trx_id'         => $bkashData['trxID'] ?? null,
                'bkash_response' => $bkashData,
            ]);

            $this->deliver($order);

            // Credit referrer wallet and debit buyer wallet
            $this->referralService->creditReferrerWallet($order);
            $this->processWalletDebit($order);

            $this->sendDeliveryEmail($order);
        });
    }

    /**
     * @param array $discountData ['referral_code' => string|null, 'referral_discount' => float, 'wallet_discount' => float]
     */
    public function createSendMoneyOrder(array $customerData, array $cartItems, string $paymentMethod, string $trxId, ?int $userId = null, array $discountData = []): Order
    {
        return DB::transaction(function () use ($customerData, $cartItems, $paymentMethod, $trxId, $userId, $discountData) {
            $order = Order::create($this->orderAttributes($customerData, $cartItems, $discountData, [
                'user_id'           => $userId,
                'status'            => 'pending_review',
                'payment_method'    => $paymentMethod,
                'send_money_trx_id' => $trxId,
            ]));

            $this->createItems($order, $cartItems);

            // Record referral usage (pending until order is approved)
            $this->recordReferralUsage($order, $discountData);

            // Debit wallet immediately for send-money orders (balance is committed on order creation)
            $this->processWalletDebit($order);

            $freshOrder = $order->fresh(['items.giftCard']);

            dispatch(new SendOrderPendingEmail($freshOrder));
            dispatch(new SendAdminNewOrderEmail($freshOrder));

            return $freshOrder;
        });
    }

    public function approveSendMoneyOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->deliver($order);

            // Credit referrer wallet now that the order is confirmed
            $this->referralService->creditReferrerWallet($order);

            $this->sendDeliveryEmail($order);
        });
    }

    /**
     * Tell the buyer their payment cleared — but only when that mail has
     * something in it.
     *
     * An order an admin has to fulfil by hand holds no codes at approval
     * time, so this would send a delivery e-mail that delivers nothing.
     * FulfilmentService sends the real one once the last line is handed over.
     */
    private function sendDeliveryEmail(Order $order): void
    {
        if (! $order->hasInstantDelivery()) {
            return;
        }

        dispatch(new SendOrderCodesEmail($order));
    }

    public function failOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'failed']);

            $this->releaseReservations($order);

            BkashPayment::where('order_id', $order->id)->update(['status' => 'failed']);

            $this->referralService->cancelUsage($order);
        });
    }

    public function cancelOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            $this->releaseReservations($order);

            $this->referralService->cancelUsage($order);

            // Refund wallet if it was debited
            if ($order->wallet_discount_bdt > 0 && $order->user_id) {
                $user = User::find($order->user_id);
                if ($user) {
                    $this->referralService->refundWallet($user, (float) $order->wallet_discount_bdt, $order);
                }
            }
        });
    }

    public function refundOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'refunded']);

            foreach ($order->items as $orderItem) {
                $itemCodes = OrderItemCode::where('order_item_id', $orderItem->id)->get();

                if ($itemCodes->isNotEmpty()) {
                    $itemCodes->each(function ($itemCode) {
                        $itemCode->giftCardCode->update(['status' => 'available', 'order_item_id' => null]);
                        $itemCode->delete();
                    });

                    $orderItem->giftCard->increment('stock_count', $itemCodes->count());
                }
            }

            $this->releaseReservations($order);

            // Refund wallet if it was used
            if ($order->wallet_discount_bdt > 0 && $order->user_id) {
                $user = User::find($order->user_id);
                if ($user) {
                    $this->referralService->refundWallet($user, (float) $order->wallet_discount_bdt, $order);
                }
            }
        });
    }

    /**
     * The order row itself. Shared so the online and send-money paths can
     * never disagree about how a total is arrived at.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderAttributes(array $customerData, array $cartItems, array $discountData, array $overrides): array
    {
        $subtotal         = collect($cartItems)->sum(fn ($item) => $item['price'] * $item['quantity']);
        $referralDiscount = (float) ($discountData['referral_discount'] ?? 0);
        $walletDiscount   = (float) ($discountData['wallet_discount'] ?? 0);

        return array_merge([
            'order_number'          => generate_order_number(),
            'customer_name'         => $customerData['name'],
            'customer_email'        => $customerData['email'],
            'customer_phone'        => $customerData['phone'],
            'subtotal_bdt'          => $subtotal,
            'total_bdt'             => max(0, $subtotal - $referralDiscount - $walletDiscount),
            'ip_address'            => request()->ip(),
            'referral_code_used'    => $discountData['referral_code'] ?? null,
            'referral_discount_bdt' => $referralDiscount,
            'wallet_discount_bdt'   => $walletDiscount,
        ], $overrides);
    }

    /**
     * Write one order item per cart line and reserve what it needs.
     *
     * Runs inside the caller's transaction, so a line that cannot be reserved
     * takes the whole order down with it rather than leaving a half-reserved
     * order behind.
     */
    private function createItems(Order $order, array $cartItems): void
    {
        $buyPrices = GiftCard::whereIn('id', collect($cartItems)->pluck('gift_card_id'))
            ->pluck('buy_price_bdt', 'id');

        foreach ($cartItems as $item) {
            $giftCard = $item['gift_card'] ?? GiftCard::findOrFail($item['gift_card_id']);
            $manual   = $giftCard->needsManualFulfilment();

            $orderItem = OrderItem::create([
                'order_id'          => $order->id,
                'gift_card_id'      => $item['gift_card_id'],
                'quantity'          => $item['quantity'],
                'unit_price_bdt'    => $item['price'],
                'buy_price_bdt'     => $buyPrices[$item['gift_card_id']] ?? null,
                'subtotal_bdt'      => $item['price'] * $item['quantity'],
                'fulfilment_status' => $manual ? OrderItem::FULFILMENT_AWAITING : null,
                'buyer_inputs'      => empty($item['buyer_inputs']) ? null : $item['buyer_inputs'],
            ]);

            $manual
                ? $this->reserveManualStock($orderItem, $item['gift_card_id'], $item['quantity'])
                : $this->reserveCodes($orderItem, $item['gift_card_id'], $item['quantity']);
        }
    }

    /**
     * Take `$quantity` codes off the shelf and pin them to this line.
     *
     * Unchanged from the single-mode version of this service: the row lock on
     * gift_card_codes is what stops two concurrent checkouts claiming the same
     * code.
     */
    private function reserveCodes(OrderItem $orderItem, int $giftCardId, int $quantity): void
    {
        $codes = GiftCardCode::where('gift_card_id', $giftCardId)
            ->available()
            ->lockForUpdate()
            ->limit($quantity)
            ->get();

        if ($codes->count() < $quantity) {
            throw new \RuntimeException("Insufficient stock for gift card ID {$giftCardId}");
        }

        $codes->each(fn (GiftCardCode $code) => $code->update([
            'status'        => 'reserved',
            'order_item_id' => $orderItem->id,
        ]));
    }

    /**
     * Hold `$quantity` units of a manually-fulfilled card.
     *
     * The decrement happens here, inside the reservation transaction and
     * behind a row lock, rather than at completion. Deferring it would let two
     * concurrent checkouts both read the same remaining stock and oversell a
     * product that cannot be auto-delivered.
     */
    private function reserveManualStock(OrderItem $orderItem, int $giftCardId, int $quantity): void
    {
        $card = GiftCard::whereKey($giftCardId)->lockForUpdate()->first();

        if ($card === null || $card->manual_stock < $quantity) {
            throw new \RuntimeException("Insufficient stock for gift card ID {$giftCardId}");
        }

        $card->decrement('manual_stock', $quantity);
    }

    /**
     * Payment has cleared: hand over everything that can be handed over now.
     *
     * An order whose lines are all code-pool becomes `paid` exactly as it
     * always did. One still holding a line an admin must act on becomes
     * `processing` — a status that has been in the enum and labelled in the
     * admin since the first migration, and until now was never set.
     */
    private function deliver(Order $order): void
    {
        foreach ($order->items as $orderItem) {
            if ($orderItem->isManual()) {
                continue;
            }

            $codes = GiftCardCode::where('order_item_id', $orderItem->id)
                ->where('status', 'reserved')
                ->get();

            foreach ($codes as $code) {
                $code->update(['status' => 'sold']);
                OrderItemCode::create([
                    'order_item_id'     => $orderItem->id,
                    'gift_card_code_id' => $code->id,
                ]);
            }

            $orderItem->giftCard->decrement('stock_count', $orderItem->quantity);
        }

        $order->update([
            'status' => $order->items->contains(fn (OrderItem $item) => $item->needsFulfilment())
                ? 'processing'
                : 'paid',
        ]);
    }

    /**
     * Put back whatever this order was holding: reserved codes to the shelf,
     * manual units to their counter. Fulfilled lines are left alone — that
     * stock is gone and putting it back would invent inventory.
     */
    private function releaseReservations(Order $order): void
    {
        GiftCardCode::whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))
            ->where('status', 'reserved')
            ->update(['status' => 'available', 'order_item_id' => null]);

        foreach ($order->items as $orderItem) {
            if ($orderItem->needsFulfilment()) {
                GiftCard::whereKey($orderItem->gift_card_id)->increment('manual_stock', $orderItem->quantity);
            }
        }
    }

    private function recordReferralUsage(Order $order, array $discountData): void
    {
        $code = $discountData['referral_code'] ?? null;
        if (! $code || ($discountData['referral_discount'] ?? 0) <= 0) {
            return;
        }

        $referrer = \App\Models\User::where('referral_code', strtoupper($code))->first();
        if ($referrer) {
            $this->referralService->recordUsage($order, $referrer, (float) $discountData['referral_discount']);
        }
    }

    private function processWalletDebit(Order $order): void
    {
        if ($order->wallet_discount_bdt > 0 && $order->user_id) {
            $user = User::find($order->user_id);
            if ($user) {
                $this->referralService->debitWallet($user, (float) $order->wallet_discount_bdt, $order);
            }
        }
    }
}
