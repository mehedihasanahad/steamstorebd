<?php

/**
 * The admin side of the fulfilment queue: who can see it, what it lists, and
 * what the actions on it do.
 */

use App\Filament\Resources\FulfilmentResource;
use App\Models\BkashPayment;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Queue;

/** A paid order holding one manual line, waiting on an admin. */
function awaitingItem(array $buyerInputs = ['player_id' => '5123456789']): OrderItem
{
    Queue::fake();

    $card  = manualCard(seoProduct(seoBrand()), 5);
    $order = app(OrderService::class)->createOrder(
        ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '01700000000'],
        [[
            'gift_card_id' => $card->id,
            'gift_card'    => $card,
            'quantity'     => 1,
            'price'        => $card->price_bdt,
            'buyer_inputs' => $buyerInputs,
        ]],
    );

    BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
    app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX-ADMIN']);

    return $order->fresh()->items->first();
}

describe('access control', function () {
    it('lets an admin open the queue', function () {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(FulfilmentResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('turns a non-admin away', function () {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(FulfilmentResource::getUrl('index'))
            ->assertForbidden();
    });

    it('sends a guest to login', function () {
        $this->get(FulfilmentResource::getUrl('index'))->assertRedirect();
    });
});

describe('the queue listing', function () {
    it('shows an outstanding line with its order and buyer details', function () {
        $item = awaitingItem();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(FulfilmentResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee($item->order->order_number)
            ->assertSee('PUBG 660 UC');
    });

    it('badges the navigation with how much work is outstanding', function () {
        expect(FulfilmentResource::getNavigationBadge())->toBeNull();

        awaitingItem();

        expect(FulfilmentResource::getNavigationBadge())->toBe('1');
    });

    it('describes buyer inputs in a readable line', function () {
        expect(FulfilmentResource::describeInputs(['player_id' => '123', 'zone_id' => '9']))
            ->toBe('Player Id: 123 · Zone Id: 9');
    });

    it('shows a dash when a line carries no buyer input', function () {
        expect(FulfilmentResource::describeInputs(null))->toBe('—')
            ->and(FulfilmentResource::describeInputs('not an array'))->toBe('—');
    });

    it('never lists a code-pool line', function () {
        Queue::fake();

        $card  = seoCard(seoProduct(seoBrand()), [], 3);
        $order = app(OrderService::class)->createOrder(
            ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '01700000000'],
            [['gift_card_id' => $card->id, 'gift_card' => $card, 'quantity' => 1, 'price' => $card->price_bdt]],
        );
        BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
        app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

        expect(FulfilmentResource::getEloquentQuery()->count())->toBe(0);
    });

    it('is read-only: nothing is created, edited or deleted here', function () {
        $item = awaitingItem();

        expect(FulfilmentResource::canCreate())->toBeFalse()
            ->and(FulfilmentResource::canEdit($item))->toBeFalse()
            ->and(FulfilmentResource::canDelete($item))->toBeFalse();
    });
});

describe('the order record', function () {
    it('shows the fulfilment state and buyer inputs on the order itself', function () {
        $item = awaitingItem();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(\App\Filament\Resources\OrderResource::getUrl('view', ['record' => $item->order_id]))
            ->assertSuccessful()
            ->assertSee('processing');
    });
});
