<?php

namespace App\Services;

use App\Jobs\SendAdminNewOrderEmail;
use App\Jobs\SendOrderCodesEmail;
use App\Jobs\SendOrderPendingEmail;
use App\Models\BkashPayment;
use App\Models\GiftCardCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function createOrder(array $customerData, array $cartItems): Order
    {
        return DB::transaction(function () use ($customerData, $cartItems) {
            $subtotal = collect($cartItems)->sum(fn($item) => $item['price'] * $item['quantity']);

            $order = Order::create([
                'order_number' => generate_order_number(),
                'customer_name' => $customerData['name'],
                'customer_email' => $customerData['email'],
                'customer_phone' => $customerData['phone'],
                'subtotal_bdt' => $subtotal,
                'total_bdt' => $subtotal,
                'status' => 'pending',
                'ip_address' => request()->ip(),
            ]);

            foreach ($cartItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'gift_card_id' => $item['gift_card_id'],
                    'quantity' => $item['quantity'],
                    'unit_price_bdt' => $item['price'],
                    'subtotal_bdt' => $item['price'] * $item['quantity'],
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
                        'status' => 'reserved',
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
                'status' => 'completed',
                'trx_id' => $bkashData['trxID'] ?? null,
                'bkash_response' => $bkashData,
            ]);

            foreach ($order->items as $orderItem) {
                $codes = GiftCardCode::where('order_item_id', $orderItem->id)
                    ->where('status', 'reserved')
                    ->get();

                foreach ($codes as $code) {
                    $code->update(['status' => 'sold']);
                    OrderItemCode::create([
                        'order_item_id' => $orderItem->id,
                        'gift_card_code_id' => $code->id,
                    ]);
                }

                // Update stock count
                $orderItem->giftCard->decrement('stock_count', $orderItem->quantity);
            }

            dispatch(new SendOrderCodesEmail($order));
        });
    }

    public function createSendMoneyOrder(array $customerData, array $cartItems, string $paymentMethod, string $trxId, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($customerData, $cartItems, $paymentMethod, $trxId, $userId) {
            $subtotal = collect($cartItems)->sum(fn($item) => $item['price'] * $item['quantity']);

            $order = Order::create([
                'user_id'           => $userId,
                'order_number'      => generate_order_number(),
                'customer_name'     => $customerData['name'],
                'customer_email'    => $customerData['email'],
                'customer_phone'    => $customerData['phone'],
                'subtotal_bdt'      => $subtotal,
                'total_bdt'         => $subtotal,
                'status'            => 'pending_review',
                'payment_method'    => $paymentMethod,
                'send_money_trx_id' => $trxId,
                'ip_address'        => request()->ip(),
            ]);

            foreach ($cartItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id'       => $order->id,
                    'gift_card_id'   => $item['gift_card_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price_bdt' => $item['price'],
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
                        'order_item_id'    => $orderItem->id,
                        'gift_card_code_id' => $code->id,
                    ]);
                }

                $orderItem->giftCard->decrement('stock_count', $orderItem->quantity);
            }

            dispatch(new SendOrderCodesEmail($order));
        });
    }

    public function failOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'failed']);

            // Release reserved codes
            GiftCardCode::whereHas('orderItem', fn($q) => $q->where('order_id', $order->id))
                ->where('status', 'reserved')
                ->update(['status' => 'available', 'order_item_id' => null]);

            BkashPayment::where('order_id', $order->id)->update(['status' => 'failed']);
        });
    }
}
