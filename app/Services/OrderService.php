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

class OrderService
{
    public function __construct(private ReferralService $referralService) {}

    /**
     * @param array $discountData ['referral_code' => string|null, 'referral_discount' => float, 'wallet_discount' => float]
     */
    public function createOrder(array $customerData, array $cartItems, array $discountData = []): Order
    {
        return DB::transaction(function () use ($customerData, $cartItems, $discountData) {
            $subtotal         = collect($cartItems)->sum(fn($item) => $item['price'] * $item['quantity']);
            $referralDiscount = (float) ($discountData['referral_discount'] ?? 0);
            $walletDiscount   = (float) ($discountData['wallet_discount'] ?? 0);
            $total            = max(0, $subtotal - $referralDiscount - $walletDiscount);

            $order = Order::create([
                'order_number'          => generate_order_number(),
                'customer_name'         => $customerData['name'],
                'customer_email'        => $customerData['email'],
                'customer_phone'        => $customerData['phone'],
                'subtotal_bdt'          => $subtotal,
                'total_bdt'             => $total,
                'status'                => 'pending',
                'ip_address'            => request()->ip(),
                'referral_code_used'    => $discountData['referral_code'] ?? null,
                'referral_discount_bdt' => $referralDiscount,
                'wallet_discount_bdt'   => $walletDiscount,
            ]);

            $buyPrices = GiftCard::whereIn('id', collect($cartItems)->pluck('gift_card_id'))
                ->pluck('buy_price_bdt', 'id');

            foreach ($cartItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id'       => $order->id,
                    'gift_card_id'   => $item['gift_card_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price_bdt' => $item['price'],
                    'buy_price_bdt'  => $buyPrices[$item['gift_card_id']] ?? null,
                    'subtotal_bdt'   => $item['price'] * $item['quantity'],
                ]);

                // Reserve codes
                $codes = GiftCardCode::where('gift_card_id', $item['gift_card_id'])
                    ->available()
                    ->lockForUpdate()
                    ->limit($item['quantity'])
                    ->get();

                if ($codes->count() < $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for gift card ID {$item['gift_card_id']}");
                }

                $codes->each(function (GiftCardCode $code) use ($orderItem) {
                    $code->update([
                        'status'        => 'reserved',
                        'order_item_id' => $orderItem->id,
                    ]);
                });
            }

            return $order->fresh(['items']);
        });
    }

    public function completeOrder(Order $order, array $bkashData): void
    {
        DB::transaction(function () use ($order, $bkashData) {
            $order->update(['status' => 'paid']);

            BkashPayment::where('order_id', $order->id)->update([
                'status'         => 'completed',
                'trx_id'         => $bkashData['trxID'] ?? null,
                'bkash_response' => $bkashData,
            ]);

            foreach ($order->items as $orderItem) {
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

            // Credit referrer wallet and debit buyer wallet
            $this->referralService->creditReferrerWallet($order);
            $this->processWalletDebit($order);

            dispatch(new SendOrderCodesEmail($order));
        });
    }

    /**
     * @param array $discountData ['referral_code' => string|null, 'referral_discount' => float, 'wallet_discount' => float]
     */
    public function createSendMoneyOrder(array $customerData, array $cartItems, string $paymentMethod, string $trxId, ?int $userId = null, array $discountData = []): Order
    {
        return DB::transaction(function () use ($customerData, $cartItems, $paymentMethod, $trxId, $userId, $discountData) {
            $subtotal         = collect($cartItems)->sum(fn($item) => $item['price'] * $item['quantity']);
            $referralDiscount = (float) ($discountData['referral_discount'] ?? 0);
            $walletDiscount   = (float) ($discountData['wallet_discount'] ?? 0);
            $total            = max(0, $subtotal - $referralDiscount - $walletDiscount);

            $order = Order::create([
                'user_id'               => $userId,
                'order_number'          => generate_order_number(),
                'customer_name'         => $customerData['name'],
                'customer_email'        => $customerData['email'],
                'customer_phone'        => $customerData['phone'],
                'subtotal_bdt'          => $subtotal,
                'total_bdt'             => $total,
                'status'                => 'pending_review',
                'payment_method'        => $paymentMethod,
                'send_money_trx_id'     => $trxId,
                'ip_address'            => request()->ip(),
                'referral_code_used'    => $discountData['referral_code'] ?? null,
                'referral_discount_bdt' => $referralDiscount,
                'wallet_discount_bdt'   => $walletDiscount,
            ]);

            $buyPrices = GiftCard::whereIn('id', collect($cartItems)->pluck('gift_card_id'))
                ->pluck('buy_price_bdt', 'id');

            foreach ($cartItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id'       => $order->id,
                    'gift_card_id'   => $item['gift_card_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price_bdt' => $item['price'],
                    'buy_price_bdt'  => $buyPrices[$item['gift_card_id']] ?? null,
                    'subtotal_bdt'   => $item['price'] * $item['quantity'],
                ]);

                $codes = GiftCardCode::where('gift_card_id', $item['gift_card_id'])
                    ->available()
                    ->lockForUpdate()
                    ->limit($item['quantity'])
                    ->get();

                if ($codes->count() < $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for gift card ID {$item['gift_card_id']}");
                }

                $codes->each(fn(GiftCardCode $code) => $code->update([
                    'status'        => 'reserved',
                    'order_item_id' => $orderItem->id,
                ]));
            }

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
            $order->update(['status' => 'paid']);

            foreach ($order->items as $orderItem) {
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

            // Credit referrer wallet now that the order is confirmed
            $this->referralService->creditReferrerWallet($order);

            dispatch(new SendOrderCodesEmail($order));
        });
    }

    public function failOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'failed']);

            GiftCardCode::whereHas('orderItem', fn($q) => $q->where('order_id', $order->id))
                ->where('status', 'reserved')
                ->update(['status' => 'available', 'order_item_id' => null]);

            BkashPayment::where('order_id', $order->id)->update(['status' => 'failed']);

            $this->referralService->cancelUsage($order);
        });
    }

    public function cancelOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            GiftCardCode::whereHas('orderItem', fn($q) => $q->where('order_id', $order->id))
                ->where('status', 'reserved')
                ->update(['status' => 'available', 'order_item_id' => null]);

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

            GiftCardCode::whereHas('orderItem', fn($q) => $q->where('order_id', $order->id))
                ->where('status', 'reserved')
                ->update(['status' => 'available', 'order_item_id' => null]);

            // Refund wallet if it was used
            if ($order->wallet_discount_bdt > 0 && $order->user_id) {
                $user = User::find($order->user_id);
                if ($user) {
                    $this->referralService->refundWallet($user, (float) $order->wallet_discount_bdt, $order);
                }
            }
        });
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
