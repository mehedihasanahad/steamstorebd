<?php

namespace Database\Seeders\Demo;

use App\Models\GiftCard;
use App\Models\GiftCardCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCode;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Orders for the demo shopper, one per state the order pages can be in.
 *
 * Written directly rather than through OrderService on purpose: a fixture must
 * be able to produce a refunded order and a half-fulfilled one without walking
 * a payment through bKash, and without dispatching the e-mails that go with a
 * real purchase.
 */
class OrderDemoSeeder extends Seeder
{
    public const SHOPPER_EMAIL = 'shopper@steamstorebd.test';

    public function run(): void
    {
        $shopper = User::firstOrCreate(
            ['email' => self::SHOPPER_EMAIL],
            [
                'name'              => 'Ahad Hasan',
                // The 'hashed' cast on User does the hashing; the plain value
                // is what the browser suite signs in with.
                'password'          => 'password',
                'email_verified_at' => now(),
                'phone'             => '01700000000',
                'wallet_balance'    => 260,
                'referral_code'     => 'AHAD1234',
            ],
        );

        $this->deliveredOrder($shopper);
        $this->awaitingTopUpOrder($shopper);
        $this->deliveredCredentialsOrder($shopper);
        $this->pendingReviewOrder($shopper);
        $this->refundedOrder($shopper);
    }

    /** Paid, codes handed over: the ordinary happy path. */
    private function deliveredOrder(User $shopper): void
    {
        $card  = GiftCard::where('slug', 'steam-hkd-40')->firstOrFail();
        $order = $this->order($shopper, 'BD2026-100001', 'paid', [
            'payment_method' => 'bkash_online',
            'created_at'     => now()->subDays(2),
        ]);

        $item = $this->item($order, $card, 2);

        $this->handOverCodes($item, $card, 2);

        $order->update([
            'subtotal_bdt' => $item->subtotal_bdt,
            'total_bdt'    => $item->subtotal_bdt,
        ]);
    }

    /** Paid, but the top-up line is still with the team: status `processing`. */
    private function awaitingTopUpOrder(User $shopper): void
    {
        $topUp = GiftCard::where('slug', 'pubg-660-uc')->firstOrFail();
        $card  = GiftCard::where('slug', 'steam-usa-5')->firstOrFail();

        $order = $this->order($shopper, 'BD2026-100002', 'processing', [
            'payment_method' => 'bkash_online',
            'created_at'     => now()->subHours(3),
        ]);

        // A mixed order: the gift card has already reached the customer while
        // the top-up has not. That is exactly what `processing` means.
        $codeItem = $this->item($order, $card, 1);
        $this->handOverCodes($codeItem, $card, 1);

        $this->item($order, $topUp, 1, [
            'fulfilment_status' => OrderItem::FULFILMENT_AWAITING,
            'buyer_inputs'      => ['player_id' => '5123456789', 'zone_id' => '1234'],
        ]);

        $total = $order->items()->sum('subtotal_bdt');
        $order->update(['subtotal_bdt' => $total, 'total_bdt' => $total]);
    }

    /** A subscription whose credentials an admin has already sent. */
    private function deliveredCredentialsOrder(User $shopper): void
    {
        $card  = GiftCard::where('slug', 'hoichoi-6m')->firstOrFail();
        $order = $this->order($shopper, 'BD2026-100003', 'paid', [
            'payment_method' => 'bkash_send_money',
            'send_money_trx_id' => 'BKS7H2K9XQ',
            'created_at'     => now()->subDay(),
        ]);

        $this->item($order, $card, 1, [
            'fulfilment_status' => OrderItem::FULFILMENT_FULFILLED,
            'delivered_payload' => "E-mail: demo.hoichoi@example.test\nPassword: Hc!2026demo\nScreens: 1",
            'fulfilled_at'      => now()->subHours(20),
        ]);

        $total = $order->items()->sum('subtotal_bdt');
        $order->update(['subtotal_bdt' => $total, 'total_bdt' => $total]);
    }

    /** Send-money order waiting on an admin to verify the transaction. */
    private function pendingReviewOrder(User $shopper): void
    {
        $card  = GiftCard::where('slug', 'google-play-25')->firstOrFail();
        $order = $this->order($shopper, 'BD2026-100004', 'pending_review', [
            'payment_method'    => 'nagad_send_money',
            'send_money_trx_id' => 'NGD8G4D2X1F9Y',
            'created_at'        => now()->subMinutes(12),
        ]);

        $item = $this->item($order, $card, 1);
        $this->reserveCodes($item, $card, 1);

        $order->update(['subtotal_bdt' => $item->subtotal_bdt, 'total_bdt' => $item->subtotal_bdt]);
    }

    /** A refunded order, so the account pages have a red state to render. */
    private function refundedOrder(User $shopper): void
    {
        $card  = GiftCard::where('slug', 'itunes-usa-10')->firstOrFail();
        $order = $this->order($shopper, 'BD2026-100005', 'refunded', [
            'payment_method' => 'bkash_online',
            'created_at'     => now()->subDays(9),
        ]);

        $item = $this->item($order, $card, 1);

        $order->update(['subtotal_bdt' => $item->subtotal_bdt, 'total_bdt' => $item->subtotal_bdt]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function order(User $shopper, string $number, string $status, array $attributes = []): Order
    {
        $order = Order::updateOrCreate(['order_number' => $number], array_merge([
            'user_id'        => $shopper->id,
            'customer_name'  => $shopper->name,
            'customer_email' => $shopper->email,
            'customer_phone' => $shopper->phone ?? '01700000000',
            'subtotal_bdt'   => 0,
            'total_bdt'      => 0,
            'status'         => $status,
            'ip_address'     => '203.0.113.7',
        ], $attributes));

        // Wipe the lines so re-running the seeder cannot double them up.
        OrderItemCode::whereIn('order_item_id', $order->items()->pluck('id'))->delete();
        $order->items()->delete();

        // created_at is not fillable, so a back-dated order needs it set after.
        if (isset($attributes['created_at'])) {
            $order->forceFill(['created_at' => $attributes['created_at']])->save();
        }

        return $order;
    }

    /** @param  array<string, mixed>  $attributes */
    private function item(Order $order, GiftCard $card, int $quantity, array $attributes = []): OrderItem
    {
        return OrderItem::create(array_merge([
            'order_id'       => $order->id,
            'gift_card_id'   => $card->id,
            'quantity'       => $quantity,
            'unit_price_bdt' => $card->price_bdt,
            'buy_price_bdt'  => $card->buy_price_bdt,
            'subtotal_bdt'   => $card->price_bdt * $quantity,
        ], $attributes));
    }

    /** Pin `$quantity` available codes to this line and mark them sold. */
    private function handOverCodes(OrderItem $item, GiftCard $card, int $quantity): void
    {
        foreach ($this->takeCodes($card, $quantity) as $code) {
            $code->update(['status' => 'sold', 'order_item_id' => $item->id]);

            OrderItemCode::firstOrCreate([
                'order_item_id'     => $item->id,
                'gift_card_code_id' => $code->id,
            ]);
        }
    }

    /** Hold codes against a line that has not been paid for yet. */
    private function reserveCodes(OrderItem $item, GiftCard $card, int $quantity): void
    {
        foreach ($this->takeCodes($card, $quantity) as $code) {
            $code->update(['status' => 'reserved', 'order_item_id' => $item->id]);
        }
    }

    /** @return \Illuminate\Support\Collection<int, GiftCardCode> */
    private function takeCodes(GiftCard $card, int $quantity)
    {
        return GiftCardCode::where('gift_card_id', $card->id)
            ->available()
            ->limit($quantity)
            ->get();
    }
}
