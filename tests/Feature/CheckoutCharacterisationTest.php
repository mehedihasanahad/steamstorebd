<?php

/**
 * Characterisation tests: these pin the behaviour of the checkout and order
 * pipeline EXACTLY AS IT IS TODAY, before the fulfilment engine grows a second
 * mode in Phase 5.
 *
 * When OrderService is refactored, every test in this file must keep passing
 * WITHOUT BEING EDITED. A test that needs changing means the refactor changed
 * behaviour rather than just moving it, and should be reverted and redone.
 *
 * See docs/superpowers/plans/2026-09-19-multi-vertical-catalog.md, Task 0.1.
 */

use App\Jobs\SendOrderCodesEmail;
use App\Models\BkashPayment;
use App\Models\GiftCard;
use App\Models\GiftCardCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCode;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/** A card with `$codes` available codes, wired under a brand and product. */
function stockedCard(int $codes = 5, array $cardOverrides = []): GiftCard
{
    return seoCard(seoProduct(seoBrand()), $cardOverrides, $codes);
}

/** The cart-item shape OrderService expects from CheckoutController. */
function cartLine(GiftCard $card, int $quantity = 1): array
{
    return [
        'gift_card_id' => $card->id,
        'gift_card'    => $card,
        'quantity'     => $quantity,
        'price'        => $card->price_bdt,
    ];
}

function customer(): array
{
    return ['name' => 'Test Buyer', 'email' => 'buyer@example.com', 'phone' => '01700000000'];
}

/**
 * Read the stored `gift_cards.stock_count` column, bypassing the model.
 *
 * `GiftCard::whereKey($id)->value('stock_count')` does NOT work here: Eloquent's
 * value() selects a single column, so the hydrated model has no `id`, and the
 * stock_count accessor then counts codes on a relation keyed by null and returns
 * zero. Always go through the query builder when asserting on the raw column.
 */
function rawStock(int $giftCardId): int
{
    return (int) DB::table('gift_cards')->where('id', $giftCardId)->value('stock_count');
}

describe('add to cart', function () {
    it('accepts a quantity the stock covers', function () {
        $card = stockedCard(5);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 3])
            ->assertRedirect();

        expect(session('cart'))->toHaveKey($card->id)
            ->and(session('cart')[$card->id]['quantity'])->toBe(3);
    });

    it('refuses a quantity beyond stock with the count in the message', function () {
        $card = stockedCard(2);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 3])
            ->assertSessionHas('error', 'Only 2 available in stock.');

        expect(session('cart'))->toBeNull();
    });

    it('caps quantity at ten', function () {
        $card = stockedCard(20);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 11])
            ->assertSessionHasErrors('quantity');
    });

    it('derives stock from available codes, not the stored column', function () {
        $card = stockedCard(3);
        GiftCard::whereKey($card->id)->update(['stock_count' => 99]);

        expect($card->fresh()->stock_count)->toBe(3);
    });
});

describe('order creation', function () {
    it('reserves exactly the ordered quantity of codes', function () {
        $card  = stockedCard(5);
        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 2)]);

        expect($order->status)->toBe('pending')
            ->and(GiftCardCode::where('gift_card_id', $card->id)->where('status', 'reserved')->count())->toBe(2)
            ->and(GiftCardCode::where('gift_card_id', $card->id)->where('status', 'available')->count())->toBe(3);
    });

    it('links every reserved code to the order item', function () {
        $card      = stockedCard(5);
        $order     = app(OrderService::class)->createOrder(customer(), [cartLine($card, 2)]);
        $orderItem = $order->items->first();

        expect(GiftCardCode::where('order_item_id', $orderItem->id)->count())->toBe(2);
    });

    it('totals the order from quantity and unit price', function () {
        $card  = stockedCard(5, ['price_bdt' => 500]);
        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 3)]);

        expect((float) $order->subtotal_bdt)->toBe(1500.0)
            ->and((float) $order->total_bdt)->toBe(1500.0);
    });

    it('throws and writes nothing when stock is short', function () {
        $card = stockedCard(1);

        expect(fn () => app(OrderService::class)->createOrder(customer(), [cartLine($card, 3)]))
            ->toThrow(RuntimeException::class);

        expect(Order::count())->toBe(0)
            ->and(OrderItem::count())->toBe(0)
            ->and(GiftCardCode::where('status', 'reserved')->count())->toBe(0);
    });

    it('subtracts referral and wallet discounts from the total but not the subtotal', function () {
        $card  = stockedCard(5, ['price_bdt' => 1000]);
        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 1)], [
            'referral_code'     => 'FRIEND10',
            'referral_discount' => 100.0,
            'wallet_discount'   => 50.0,
        ]);

        expect((float) $order->subtotal_bdt)->toBe(1000.0)
            ->and((float) $order->total_bdt)->toBe(850.0)
            ->and($order->referral_code_used)->toBe('FRIEND10');
    });

    it('never lets discounts push the total below zero', function () {
        $card  = stockedCard(5, ['price_bdt' => 100]);
        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 1)], [
            'referral_discount' => 500.0,
            'wallet_discount'   => 0.0,
        ]);

        expect((float) $order->total_bdt)->toBe(0.0);
    });
});

describe('order completion', function () {
    it('sells the reserved codes and hands them to the buyer', function () {
        Queue::fake();

        $card  = stockedCard(5);
        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 2)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);

        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX123']);

        expect($order->fresh()->status)->toBe('paid')
            ->and(GiftCardCode::where('gift_card_id', $card->id)->where('status', 'sold')->count())->toBe(2)
            ->and(OrderItemCode::count())->toBe(2);

        Queue::assertPushed(SendOrderCodesEmail::class);
    });

    it('decrements the stored stock column on completion', function () {
        Queue::fake();

        $card = stockedCard(5);
        GiftCard::whereKey($card->id)->update(['stock_count' => 5]);

        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 2)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);

        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX123']);

        expect(rawStock($card->id))->toBe(3);
    });

    it('marks the bkash payment completed with its transaction id', function () {
        Queue::fake();

        $card  = stockedCard(5);
        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 1)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);

        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX999']);

        $payment = BkashPayment::where('order_id', $order->id)->first();
        expect($payment->status)->toBe('completed')->and($payment->trx_id)->toBe('TRX999');
    });
});

describe('send money orders', function () {
    it('opens for review and reserves stock immediately', function () {
        Queue::fake();

        $card  = stockedCard(5);
        $order = app(OrderService::class)->createSendMoneyOrder(
            customer(), [cartLine($card, 2)], 'bkash_send_money', 'TRX-SM-1'
        );

        expect($order->status)->toBe('pending_review')
            ->and($order->send_money_trx_id)->toBe('TRX-SM-1')
            ->and(GiftCardCode::where('status', 'reserved')->count())->toBe(2);
    });

    it('releases codes to the buyer on approval', function () {
        Queue::fake();

        $card  = stockedCard(5);
        $order = app(OrderService::class)->createSendMoneyOrder(
            customer(), [cartLine($card, 2)], 'bkash_send_money', 'TRX-SM-2'
        );

        app(OrderService::class)->approveSendMoneyOrder($order);

        expect($order->fresh()->status)->toBe('paid')
            ->and(GiftCardCode::where('status', 'sold')->count())->toBe(2)
            ->and(OrderItemCode::count())->toBe(2);
    });
});

describe('order reversal', function () {
    it('returns reserved codes to the shelf when an order fails', function () {
        $card  = stockedCard(5);
        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 2)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);

        app(OrderService::class)->failOrder($order);

        expect($order->fresh()->status)->toBe('failed')
            ->and(GiftCardCode::where('status', 'available')->count())->toBe(5)
            ->and(GiftCardCode::whereNotNull('order_item_id')->count())->toBe(0);
    });

    it('returns reserved codes to the shelf when an order is cancelled', function () {
        $card  = stockedCard(5);
        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 2)]);

        app(OrderService::class)->cancelOrder($order);

        expect($order->fresh()->status)->toBe('cancelled')
            ->and(GiftCardCode::where('status', 'available')->count())->toBe(5);
    });

    it('returns sold codes and restores stock on refund', function () {
        Queue::fake();

        $card = stockedCard(5);
        GiftCard::whereKey($card->id)->update(['stock_count' => 5]);

        $order = app(OrderService::class)->createOrder(customer(), [cartLine($card, 2)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX123']);

        app(OrderService::class)->refundOrder($order);

        expect($order->fresh()->status)->toBe('refunded')
            ->and(GiftCardCode::where('status', 'available')->count())->toBe(5)
            ->and(OrderItemCode::count())->toBe(0)
            ->and(rawStock($card->id))->toBe(5);
    });

    it('refunds a wallet debit when the order is cancelled', function () {
        SiteSetting::set('referral_enabled', true);

        $user = User::factory()->create(['wallet_balance' => 500]);
        $card = stockedCard(5, ['price_bdt' => 1000]);

        $order = app(OrderService::class)->createSendMoneyOrder(
            customer(), [cartLine($card, 1)], 'bkash_send_money', 'TRX-W', $user->id,
            ['wallet_discount' => 200.0],
        );

        expect((float) $user->fresh()->wallet_balance)->toBe(300.0);

        app(OrderService::class)->cancelOrder($order->fresh());

        expect((float) $user->fresh()->wallet_balance)->toBe(500.0);
    });
});
