<?php

/**
 * The second fulfilment mode: paid lines an admin has to top up or send
 * credentials for.
 *
 * The first mode — a code pulled from the pool — is pinned by
 * CheckoutCharacterisationTest and must not change. These tests are about what
 * happens around it.
 */

use App\Jobs\SendOrderCodesEmail;
use App\Models\BkashPayment;
use App\Models\GiftCard;
use App\Models\GiftCardCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\FulfilmentService;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

function manualLine(GiftCard $card, int $quantity = 1, array $buyerInputs = []): array
{
    return [
        'gift_card_id' => $card->id,
        'gift_card'    => $card,
        'quantity'     => $quantity,
        'price'        => $card->price_bdt,
        'buyer_inputs' => $buyerInputs,
    ];
}

function buyer(): array
{
    return ['name' => 'Test Buyer', 'email' => 'buyer@example.com', 'phone' => '01700000000'];
}

describe('reserving a manual card', function () {
    it('holds stock from the counter instead of the code pool', function () {
        $card  = manualCard(seoProduct(seoBrand()), 5);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card, 2)]);

        expect($order->status)->toBe('pending')
            ->and(GiftCard::whereKey($card->id)->value('manual_stock'))->toBe(3)
            ->and(GiftCardCode::count())->toBe(0);
    });

    it('marks the line as awaiting an admin', function () {
        $card  = manualCard(seoProduct(seoBrand()), 5);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card)]);

        expect($order->items->first()->fulfilment_status)->toBe(OrderItem::FULFILMENT_AWAITING)
            ->and($order->items->first()->needsFulfilment())->toBeTrue();
    });

    it('stores the buyer inputs on the line', function () {
        $card  = manualCard(seoProduct(seoBrand()), 5);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card, 1, ['player_id' => '5123456789'])]);

        expect($order->items->first()->buyer_inputs)->toBe(['player_id' => '5123456789']);
    });

    it('decrements stock at reservation, not at completion', function () {
        // Deferring it would let two concurrent checkouts read the same
        // remaining stock and oversell something that cannot be auto-delivered.
        $card = manualCard(seoProduct(seoBrand()), 2);

        app(OrderService::class)->createOrder(buyer(), [manualLine($card, 2)]);

        expect(GiftCard::whereKey($card->id)->value('manual_stock'))->toBe(0);
    });

    it('throws and writes nothing when the counter is short', function () {
        $card = manualCard(seoProduct(seoBrand()), 1);

        expect(fn () => app(OrderService::class)->createOrder(buyer(), [manualLine($card, 3)]))
            ->toThrow(RuntimeException::class);

        expect(Order::count())->toBe(0)
            ->and(OrderItem::count())->toBe(0)
            ->and(GiftCard::whereKey($card->id)->value('manual_stock'))->toBe(1);
    });

    it('leaves a code-pool line on the same order completely alone', function () {
        $product = seoProduct(seoBrand());
        $pool    = seoCard($product, ['slug' => 'pool-card'], 3);
        $manual  = manualCard($product, 4);

        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($pool), manualLine($manual)]);

        $poolItem   = $order->items->firstWhere('gift_card_id', $pool->id);
        $manualItem = $order->items->firstWhere('gift_card_id', $manual->id);

        expect($poolItem->fulfilment_status)->toBeNull()
            ->and($poolItem->isManual())->toBeFalse()
            ->and($manualItem->needsFulfilment())->toBeTrue()
            ->and(GiftCardCode::where('status', 'reserved')->count())->toBe(1);
    });
});

describe('payment clearing on a manual order', function () {
    it('moves a wholly-manual order to processing rather than paid', function () {
        Queue::fake();

        $card  = manualCard(seoProduct(seoBrand()), 5);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);

        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

        expect($order->fresh()->status)->toBe('processing');
    });

    it('delivers the code-pool half of a mixed order immediately', function () {
        Queue::fake();

        $product = seoProduct(seoBrand());
        $pool    = seoCard($product, ['slug' => 'pool-card'], 3);
        $manual  = manualCard($product, 4);

        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($pool), manualLine($manual)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);

        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

        expect($order->fresh()->status)->toBe('processing')
            ->and(GiftCardCode::where('status', 'sold')->count())->toBe(1);

        Queue::assertPushed(SendOrderCodesEmail::class);
    });

    it('still marks a wholly code-pool order as paid', function () {
        Queue::fake();

        $card  = seoCard(seoProduct(seoBrand()), [], 3);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);

        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

        expect($order->fresh()->status)->toBe('paid');
    });
});

describe('reversal', function () {
    it('returns manual stock to the counter when an order fails', function () {
        $card  = manualCard(seoProduct(seoBrand()), 5);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card, 2)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);

        app(OrderService::class)->failOrder($order);

        expect(GiftCard::whereKey($card->id)->value('manual_stock'))->toBe(5);
    });

    it('returns manual stock when an order is cancelled', function () {
        $card  = manualCard(seoProduct(seoBrand()), 5);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card, 3)]);

        app(OrderService::class)->cancelOrder($order);

        expect(GiftCard::whereKey($card->id)->value('manual_stock'))->toBe(5);
    });

    it('never invents stock by returning an already-fulfilled line', function () {
        Queue::fake();

        $card  = manualCard(seoProduct(seoBrand()), 5);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card, 2)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

        app(FulfilmentService::class)->fulfil($order->fresh()->items->first(), 'Topped up.');
        app(OrderService::class)->refundOrder($order->fresh());

        // The units are spent; the refund is a money decision, not a stock one.
        expect(GiftCard::whereKey($card->id)->value('manual_stock'))->toBe(3);
    });
});

describe('the admin queue', function () {
    beforeEach(function () {
        Queue::fake();

        $this->card  = manualCard(seoProduct(seoBrand()), 5);
        $this->order = app(OrderService::class)->createOrder(buyer(), [manualLine($this->card, 1, ['player_id' => '5123456789'])]);
        BkashPayment::create(['order_id' => $this->order->id, 'amount' => $this->order->total_bdt, 'status' => 'initiated']);
        app(OrderService::class)->completeOrder($this->order, ['trxID' => 'TRX1']);

        $this->item = $this->order->fresh()->items->first();
    });

    it('lists a paid line that needs an admin', function () {
        $queue = app(FulfilmentService::class)->queue();

        expect($queue)->toHaveCount(1)
            ->and($queue->first()->is($this->item))->toBeTrue()
            ->and(app(FulfilmentService::class)->pendingCount())->toBe(1);
    });

    it('never lists a line on an unpaid order', function () {
        $other = manualCard(
            seoProduct(seoBrand(['slug' => 'other', 'name' => 'Other']), ['slug' => 'other-product', 'name' => 'Other Product']),
            5,
        );
        app(OrderService::class)->createOrder(buyer(), [manualLine($other)]);

        // Two awaiting lines exist; only the paid one is work.
        expect(app(FulfilmentService::class)->pendingCount())->toBe(1);
    });

    it('records what was delivered and settles the order', function () {
        app(FulfilmentService::class)->fulfil($this->item, 'Topped up to Player ID 5123456789.');

        $item = $this->item->fresh();

        expect($item->isFulfilled())->toBeTrue()
            ->and($item->delivered_payload)->toBe('Topped up to Player ID 5123456789.')
            ->and($item->fulfilled_at)->not->toBeNull()
            ->and($this->order->fresh()->status)->toBe('paid');
    });

    it('stores the delivered payload encrypted at rest', function () {
        app(FulfilmentService::class)->fulfil($this->item, 'user@example.com / hunter2');

        $raw = DB::table('order_items')->where('id', $this->item->id)->value('delivered_payload');

        expect($raw)->not->toContain('hunter2')
            ->and($this->item->fresh()->delivered_payload)->toBe('user@example.com / hunter2');
    });

    it('leaves an order in processing while any line is still outstanding', function () {
        $second = manualCard($this->card->category, 5, ['slug' => 'second-manual']);
        $order  = app(OrderService::class)->createOrder(buyer(), [manualLine($this->card), manualLine($second)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX2']);

        app(FulfilmentService::class)->fulfil($order->fresh()->items->first(), 'done');

        expect($order->fresh()->status)->toBe('processing');
    });

    it('e-mails the buyer once the last line is done', function () {
        Queue::fake();

        app(FulfilmentService::class)->fulfil($this->item, 'done');

        Queue::assertPushed(SendOrderCodesEmail::class);
    });

    it('puts a line back in the queue when it is reopened', function () {
        app(FulfilmentService::class)->fulfil($this->item, 'wrong account');
        app(FulfilmentService::class)->reopen($this->item->fresh());

        expect($this->item->fresh()->needsFulfilment())->toBeTrue()
            ->and($this->item->fresh()->delivered_payload)->toBeNull()
            ->and($this->order->fresh()->status)->toBe('processing');
    });

    it('returns stock when a line is released before fulfilment', function () {
        app(FulfilmentService::class)->release($this->item);

        expect(GiftCard::whereKey($this->card->id)->value('manual_stock'))->toBe(5)
            ->and($this->item->fresh()->fulfilment_status)->toBeNull()
            ->and($this->order->fresh()->status)->toBe('paid');
    });

    it('releases nothing twice', function () {
        app(FulfilmentService::class)->release($this->item);
        app(FulfilmentService::class)->release($this->item->fresh());

        expect(GiftCard::whereKey($this->card->id)->value('manual_stock'))->toBe(5);
    });
});

describe('what the buyer sees', function () {
    beforeEach(function () {
        Queue::fake();

        $this->user  = User::factory()->create(['email' => 'buyer@example.com']);
        $this->card  = manualCard(seoProduct(seoBrand()), 5, ['name' => 'PUBG 660 UC']);
        $this->order = app(OrderService::class)->createOrder(buyer(), [manualLine($this->card, 1, ['player_id' => '5123456789'])]);
        $this->order->update(['user_id' => $this->user->id]);
        BkashPayment::create(['order_id' => $this->order->id, 'amount' => $this->order->total_bdt, 'status' => 'initiated']);
        app(OrderService::class)->completeOrder($this->order, ['trxID' => 'TRX1']);
    });

    it('shows an outstanding line with its promised time and their own input', function () {
        $this->actingAs($this->user)
            ->get(route('orders.show', $this->order->order_number))
            ->assertSuccessful()
            ->assertSee('Being delivered')
            ->assertSee('PUBG 660 UC')
            ->assertSee('5-30 minutes')
            ->assertSee('5123456789');
    });

    it('masks the delivered credentials behind a reveal', function () {
        app(FulfilmentService::class)->fulfil($this->order->fresh()->items->first(), 'user@example.com / hunter2');

        $this->actingAs($this->user)
            ->get(route('orders.show', $this->order->order_number))
            ->assertSuccessful()
            ->assertSee('Your account details')
            ->assertSee('Reveal')
            ->assertSee('user@example.com / hunter2');
    });

    it('treats a processing order as paid for the purposes of the order page', function () {
        expect($this->order->fresh()->status)->toBe('processing')
            ->and($this->order->fresh()->isPaid())->toBeTrue()
            ->and($this->order->fresh()->isAwaitingFulfilment())->toBeTrue();
    });

    it('reaches the success page while the order is still processing', function () {
        $this->actingAs($this->user)
            ->get(route('checkout.success', $this->order->order_number))
            ->assertSuccessful()
            ->assertSee('Being delivered by our team');
    });
});

describe('the delivery e-mail', function () {
    it('labels each item by its own product, never by a hardcoded name', function () {
        Queue::fake();

        $product = seoProduct(seoBrand(), ['name' => 'Google Play Card', 'slug' => 'google-play-card']);
        $card    = seoCard($product, ['name' => 'Google Play 500', 'slug' => 'gp-500'], 2);

        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

        $html = view('emails.order-codes', ['order' => $order->fresh(['items.orderItemCodes.giftCardCode', 'items.giftCard.category'])])->render();

        expect($html)->toContain('Google Play Card')
            ->and($html)->not->toContain('Steam Wallet Code');
    });

    it('tells the buyer which lines are still coming', function () {
        Queue::fake();

        $card  = manualCard(seoProduct(seoBrand()), 5);
        $order = app(OrderService::class)->createOrder(buyer(), [manualLine($card)]);
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

        $html = view('emails.order-codes', ['order' => $order->fresh(['items.orderItemCodes.giftCardCode', 'items.giftCard.category'])])->render();

        expect($html)->toContain('Still being delivered')
            ->and($html)->toContain('5-30 minutes');
    });
});
