<?php

/**
 * The dashboard tiles.
 *
 * These are the numbers an admin trusts to tell them whether there is work to
 * do, so each one has to count what its label claims.
 */

use App\Filament\Widgets\StatsOverviewWidget;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Queue;

/** The rendered stats, keyed by label, without going through Livewire. */
function statValues(): array
{
    $widget = new StatsOverviewWidget();
    $method = new ReflectionMethod($widget, 'getStats');

    $method->setAccessible(true);

    return collect($method->invoke($widget))
        ->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()])
        ->all();
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

/**
 * An order in a given state, placed the way the storefront places one.
 *
 * Each call builds its own brand, product and denomination, so a test can
 * place several orders without colliding on a slug.
 */
function placedOrder(string $status): Order
{
    Queue::fake();

    static $n = 0;
    $n++;

    $brand   = seoBrand(['name' => "Brand {$n}", 'slug' => "brand-{$n}"]);
    $product = seoProduct($brand, ['name' => "Product {$n}", 'slug' => "product-{$n}"]);
    $card    = seoCard($product, ['name' => "Card {$n}", 'slug' => "card-{$n}"], codes: 3);

    $order = app(OrderService::class)->createOrder(
        ['name' => 'Shopper', 'email' => 'shopper@example.com', 'phone' => '01700000000'],
        [['gift_card_id' => $card->id, 'gift_card' => $card, 'quantity' => 1, 'price' => $card->price_bdt]],
    );

    $order->update(['status' => $status]);

    return $order->fresh();
}

describe('the work-waiting tile', function () {
    it('counts a send-money order waiting to be approved', function () {
        placedOrder('pending_review');

        expect(statValues()['Needs Your Action'])->toBe(1);
    });

    it('counts an order whose items still need fulfilling by hand', function () {
        placedOrder('processing');

        expect(statValues()['Needs Your Action'])->toBe(1);
    });

    it('counts both together', function () {
        placedOrder('pending_review');
        placedOrder('processing');

        expect(statValues()['Needs Your Action'])->toBe(2);
    });

    it('ignores an abandoned checkout, which needs nothing from an admin', function () {
        placedOrder('pending');

        expect(statValues()['Needs Your Action'])->toBe(0);
    });

    it('ignores orders that are settled', function (string $status) {
        placedOrder($status);

        expect(statValues()['Needs Your Action'])->toBe(0);
    })->with(['paid', 'completed', 'failed', 'refunded', 'cancelled', 'payment_initiated']);
});

describe('the revenue tiles', function () {
    it('counts only money that has actually been confirmed', function () {
        // An order awaiting review is money the customer says they sent. It is
        // not revenue until an admin has checked the transaction.
        placedOrder('pending_review');

        expect(statValues()["Today's Revenue"])->toBe(format_bdt(0));
    });

    it('counts a paid order', function () {
        $order = placedOrder('paid');

        expect(statValues()["Today's Revenue"])->toBe(format_bdt($order->total_bdt));
    });
});

describe('the order count tile', function () {
    it('counts every order placed today, whatever its state', function () {
        placedOrder('pending_review');
        placedOrder('cancelled');

        expect(statValues()["Today's Orders"])->toBe(2);
    });
});
