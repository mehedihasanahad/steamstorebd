<?php

use App\Exceptions\OrderEditException;
use App\Filament\Resources\OrderResource\Pages\EditOrderItems;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Jobs\SendOrderCodesEmail;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\GiftCardCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCode;
use App\Models\User;
use App\Services\OrderEditService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function editAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function makeGiftCard(string $name, float $price, int $codes = 10): GiftCard
{
    $category = GiftCardCategory::firstOrCreate(
        ['slug' => 'steam-wallet'],
        ['name' => 'Steam Wallet', 'is_active' => true],
    );

    $card = GiftCard::create([
        'category_id'           => $category->id,
        'name'                  => $name,
        'slug'                  => str($name)->slug()->value(),
        'denomination'          => $price / 120,
        'denomination_currency' => 'USD',
        'denomination_bdt'      => $price,
        'buy_price_bdt'         => $price * 0.9,
        'price_bdt'             => $price,
        'is_active'             => true,
    ]);

    $admin = User::where('is_admin', true)->first() ?? editAdmin();

    for ($i = 0; $i < $codes; $i++) {
        GiftCardCode::create([
            'gift_card_id'      => $card->id,
            'code'              => strtoupper(str()->random(16)),
            'status'            => 'available',
            'added_by_admin_id' => $admin->id,
        ]);
    }

    return $card;
}

/**
 * Build an order in whichever lifecycle state the test needs, with its codes
 * wired up exactly the way OrderService would have left them.
 *
 * @param  array<int, array{card: GiftCard, quantity: int}>  $lines
 */
function makeOrder(array $lines, string $status = 'pending_review', array $attributes = []): Order
{
    $subtotal = collect($lines)->sum(fn ($line) => (float) $line['card']->price_bdt * $line['quantity']);

    $order = Order::create(array_merge([
        'order_number'   => 'BD2026-' . str()->padLeft((string) random_int(1, 999999), 6, '0'),
        'customer_name'  => 'Karim Rahman',
        'customer_email' => 'karim@example.com',
        'customer_phone' => '+8801712345678',
        'subtotal_bdt'   => $subtotal,
        'total_bdt'      => $subtotal,
        'status'         => $status,
        'payment_method' => 'bkash_send_money',
    ], $attributes));

    $delivered = in_array($status, OrderEditService::DELIVERED_STATUSES, true);

    foreach ($lines as $line) {
        $item = OrderItem::create([
            'order_id'       => $order->id,
            'gift_card_id'   => $line['card']->id,
            'quantity'       => $line['quantity'],
            'unit_price_bdt' => $line['card']->price_bdt,
            'buy_price_bdt'  => $line['card']->buy_price_bdt,
            'subtotal_bdt'   => (float) $line['card']->price_bdt * $line['quantity'],
        ]);

        GiftCardCode::where('gift_card_id', $line['card']->id)
            ->where('status', 'available')
            ->limit($line['quantity'])
            ->get()
            ->each(function (GiftCardCode $code) use ($item, $delivered) {
                $code->update([
                    'status'        => $delivered ? 'sold' : 'reserved',
                    'order_item_id' => $item->id,
                ]);

                if ($delivered) {
                    OrderItemCode::create([
                        'order_item_id'     => $item->id,
                        'gift_card_code_id' => $code->id,
                    ]);
                }
            });
    }

    return $order->fresh(['items']);
}

function applyEdit(Order $order, array $lines, string $reason = 'Customer changed their mind', string $disposition = OrderEditService::DISPOSITION_REVOKE)
{
    return app(OrderEditService::class)->apply(
        $order,
        $lines,
        User::where('is_admin', true)->first(),
        $reason,
        $disposition,
    );
}

describe('editing an order that has not been delivered yet', function () {
    beforeEach(function () {
        $this->admin = editAdmin();
        $this->card  = makeGiftCard('Steam $10', 1200, codes: 10);
    });

    it('reserves more codes when the quantity goes up', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 5]]);

        $item = $order->fresh()->items->first();

        expect($item->quantity)->toBe(5)
            ->and(GiftCardCode::where('order_item_id', $item->id)->where('status', 'reserved')->count())->toBe(5)
            ->and(GiftCardCode::where('gift_card_id', $this->card->id)->available()->count())->toBe(5)
            ->and((float) $order->fresh()->total_bdt)->toBe(6000.0);
    });

    it('returns reserved codes to stock when the quantity goes down', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 5]]);

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 2]]);

        $item = $order->fresh()->items->first();

        expect($item->quantity)->toBe(2)
            ->and(GiftCardCode::where('order_item_id', $item->id)->where('status', 'reserved')->count())->toBe(2)
            ->and(GiftCardCode::where('gift_card_id', $this->card->id)->available()->count())->toBe(8)
            ->and(GiftCardCode::revoked()->count())->toBe(0)
            ->and((float) $order->fresh()->total_bdt)->toBe(2400.0);
    });

    it('adds a brand new product at the current catalogue price', function () {
        $other = makeGiftCard('Steam $20', 2400, codes: 4);
        $order = makeOrder([['card' => $this->card, 'quantity' => 1]]);

        applyEdit($order, [
            ['gift_card_id' => $this->card->id, 'quantity' => 1],
            ['gift_card_id' => $other->id, 'quantity' => 2],
        ]);

        $order->refresh()->load('items');
        $newItem = $order->items->firstWhere('gift_card_id', $other->id);

        expect($order->items)->toHaveCount(2)
            ->and((float) $newItem->unit_price_bdt)->toBe(2400.0)
            ->and((float) $newItem->buy_price_bdt)->toBe(2160.0)
            ->and(GiftCardCode::where('order_item_id', $newItem->id)->where('status', 'reserved')->count())->toBe(2)
            ->and((float) $order->total_bdt)->toBe(6000.0);
    });

    it('drops a product entirely and frees every code it held', function () {
        $other = makeGiftCard('Steam $20', 2400, codes: 4);
        $order = makeOrder([
            ['card' => $this->card, 'quantity' => 1],
            ['card' => $other, 'quantity' => 3],
        ]);

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 1]]);

        $order->refresh()->load('items');

        expect($order->items)->toHaveCount(1)
            ->and($order->items->first()->gift_card_id)->toBe($this->card->id)
            ->and(GiftCardCode::where('gift_card_id', $other->id)->available()->count())->toBe(4)
            ->and((float) $order->total_bdt)->toBe(1200.0);
    });

    it('keeps the price the customer originally agreed to when the catalogue moves', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);
        $this->card->update(['price_bdt' => 1500]);

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 3]]);

        $item = $order->fresh()->items->first();

        expect((float) $item->unit_price_bdt)->toBe(1200.0)
            ->and((float) $order->fresh()->total_bdt)->toBe(3600.0);
    });

    it('honours an explicit unit price override', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 2, 'unit_price_bdt' => 1000]]);

        expect((float) $order->fresh()->total_bdt)->toBe(2000.0);
    });

    it('merges duplicate rows for the same product', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 1]]);

        applyEdit($order, [
            ['gift_card_id' => $this->card->id, 'quantity' => 2],
            ['gift_card_id' => $this->card->id, 'quantity' => 3],
        ]);

        $order->refresh()->load('items');

        expect($order->items)->toHaveCount(1)
            ->and($order->items->first()->quantity)->toBe(5);
    });

    it('refuses to grow beyond available stock and leaves the order untouched', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        expect(fn () => applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 99]]))
            ->toThrow(OrderEditException::class);

        $order->refresh()->load('items');

        expect($order->items->first()->quantity)->toBe(2)
            ->and((float) $order->total_bdt)->toBe(2400.0)
            ->and(GiftCardCode::where('gift_card_id', $this->card->id)->available()->count())->toBe(8);
    });

    it('refuses to empty an order', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 1]]);

        expect(fn () => applyEdit($order, []))->toThrow(OrderEditException::class);
    });

    it('requires a reason', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 1]]);

        expect(fn () => applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 2]], reason: '   '))
            ->toThrow(OrderEditException::class);
    });

    it('refuses to touch an order that is already closed', function (string $status) {
        $order = makeOrder([['card' => $this->card, 'quantity' => 1]], status: $status);

        expect(fn () => applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 2]]))
            ->toThrow(OrderEditException::class);

        $order->refresh()->load('items');

        expect($order->items)->toHaveCount(1)
            ->and($order->items->first()->quantity)->toBe(1);
    })->with(['completed', 'cancelled', 'refunded', 'failed']);

    it('resyncs the gift card stock column for every product it touched', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 4]]);

        // Read the column itself: the model accessor shadows it on attribute reads.
        expect((int) DB::table('gift_cards')->where('id', $this->card->id)->value('stock_count'))->toBe(6);
    });
});

describe('editing an order whose codes are already with the customer', function () {
    beforeEach(function () {
        $this->admin = editAdmin();
        $this->card  = makeGiftCard('Steam $10', 1200, codes: 10);
    });

    it('delivers newly added codes immediately', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 1]], status: 'paid');

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 3]]);

        $item = $order->fresh()->items->first();

        expect(GiftCardCode::where('order_item_id', $item->id)->where('status', 'sold')->count())->toBe(3)
            ->and(OrderItemCode::where('order_item_id', $item->id)->count())->toBe(3);
    });

    it('revokes a delivered code when told to, rather than reselling it', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 3]], status: 'paid');

        $edit = applyEdit(
            $order,
            [['gift_card_id' => $this->card->id, 'quantity' => 1]],
            disposition: OrderEditService::DISPOSITION_REVOKE,
        );

        $item = $order->fresh()->items->first();

        expect(GiftCardCode::revoked()->count())->toBe(2)
            ->and(OrderItemCode::where('order_item_id', $item->id)->count())->toBe(1)
            ->and(GiftCardCode::where('gift_card_id', $this->card->id)->available()->count())->toBe(7)
            ->and($edit->codeIds('revoked'))->toHaveCount(2);
    });

    it('returns a delivered code to stock, which is the default', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 3]], status: 'paid');

        // No disposition passed: the service default applies.
        app(OrderEditService::class)->apply(
            $order,
            [['gift_card_id' => $this->card->id, 'quantity' => 1]],
            $this->admin,
            'Customer changed their mind',
        );

        expect(GiftCardCode::revoked()->count())->toBe(0)
            ->and(GiftCardCode::where('gift_card_id', $this->card->id)->available()->count())->toBe(9);
    });

    it('records what the customer still owes', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]], status: 'paid');

        $edit = applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 3]]);

        expect((float) $edit->total_before_bdt)->toBe(2400.0)
            ->and((float) $edit->total_after_bdt)->toBe(3600.0)
            ->and((float) $edit->balance_delta_bdt)->toBe(1200.0)
            ->and($edit->customerOwes())->toBeTrue();
    });

    it('records what the store owes back', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 3]], status: 'paid');

        $edit = applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 1]]);

        expect((float) $edit->balance_delta_bdt)->toBe(-2400.0)
            ->and($edit->refundDue())->toBeTrue();
    });
});

describe('discounts on an edited order', function () {
    beforeEach(function () {
        $this->admin = editAdmin();
        $this->card  = makeGiftCard('Steam $10', 1200, codes: 10);
    });

    it('returns wallet credit that no longer fits the smaller order', function () {
        $customer = User::factory()->create(['wallet_balance' => 0]);

        $order = makeOrder(
            [['card' => $this->card, 'quantity' => 3]],
            status: 'paid',
            attributes: [
                'user_id'             => $customer->id,
                'wallet_discount_bdt' => 2000,
                'total_bdt'           => 1600,
            ],
        );

        $edit = applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 1]]);

        $order->refresh();

        expect((float) $order->subtotal_bdt)->toBe(1200.0)
            ->and((float) $order->wallet_discount_bdt)->toBe(1200.0)
            ->and((float) $order->total_bdt)->toBe(0.0)
            ->and((float) $edit->wallet_refunded_bdt)->toBe(800.0)
            ->and((float) $customer->fresh()->wallet_balance)->toBe(800.0);
    });

    it('leaves a discount that still fits alone', function () {
        $order = makeOrder(
            [['card' => $this->card, 'quantity' => 3]],
            attributes: ['referral_discount_bdt' => 200, 'total_bdt' => 3400],
        );

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 2]]);

        $order->refresh();

        expect((float) $order->referral_discount_bdt)->toBe(200.0)
            ->and((float) $order->total_bdt)->toBe(2200.0);
    });
});

describe('the audit trail', function () {
    beforeEach(function () {
        $this->admin = editAdmin();
        $this->card  = makeGiftCard('Steam $10', 1200, codes: 10);
    });

    it('stores a before and after snapshot with the reason and the admin', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        $edit = applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 4]], reason: 'Upsold on WhatsApp');

        expect($edit->admin_id)->toBe($this->admin->id)
            ->and($edit->reason)->toBe('Upsold on WhatsApp')
            ->and($edit->order_status)->toBe('pending_review')
            ->and($edit->items_before[0]['quantity'])->toBe(2)
            ->and($edit->items_after[0]['quantity'])->toBe(4)
            ->and($edit->items_before[0]['gift_card_name'])->toBe('Steam $10')
            ->and($edit->codeIds('reserved'))->toHaveCount(2)
            ->and($order->fresh()->isEdited())->toBeTrue();
    });
});

describe('the admin edit screen', function () {
    beforeEach(function () {
        $this->admin = editAdmin();
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Queue::fake();

        $this->card = makeGiftCard('Steam $10', 1200, codes: 10);
    });

    it('loads the current line items into the form', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        // Repeater rows are keyed by generated uuid, so assert on the row itself
        // rather than a dotted path.
        $rows = array_values(Livewire::test(EditOrderItems::class, ['record' => $order->getKey()])
            ->assertOk()
            ->get('data')['items']);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['gift_card_id'])->toBe($this->card->id)
            ->and($rows[0]['quantity'])->toBe(2)
            ->and((float) $rows[0]['unit_price_bdt'])->toBe(1200.0);
    });

    it('applies a quantity change submitted through the form', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        Livewire::test(EditOrderItems::class, ['record' => $order->getKey()])
            ->fillForm([
                'items'  => [['gift_card_id' => $this->card->id, 'quantity' => 4, 'unit_price_bdt' => 1200]],
                'reason' => 'Customer wanted two more',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($order->fresh()->items->first()->quantity)->toBe(4)
            ->and((float) $order->fresh()->total_bdt)->toBe(4800.0);
    });

    it('requires a reason before it will save', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        Livewire::test(EditOrderItems::class, ['record' => $order->getKey()])
            ->fillForm([
                'items'  => [['gift_card_id' => $this->card->id, 'quantity' => 4, 'unit_price_bdt' => 1200]],
                'reason' => null,
            ])
            ->call('save')
            ->assertHasFormErrors(['reason']);

        expect($order->fresh()->items->first()->quantity)->toBe(2);
    });

    it('surfaces a stock shortfall instead of half-applying the edit', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        Livewire::test(EditOrderItems::class, ['record' => $order->getKey()])
            ->fillForm([
                'items'  => [['gift_card_id' => $this->card->id, 'quantity' => 99, 'unit_price_bdt' => 1200]],
                'reason' => 'Bulk order',
            ])
            ->call('save')
            ->assertNotified();

        expect($order->fresh()->items->first()->quantity)->toBe(2);
    });

    it('queues the codes email for a paid order when asked', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 1]], status: 'paid');

        Livewire::test(EditOrderItems::class, ['record' => $order->getKey()])
            ->fillForm([
                'items'       => [['gift_card_id' => $this->card->id, 'quantity' => 2, 'unit_price_bdt' => 1200]],
                'reason'      => 'Customer added one more',
                'disposition' => OrderEditService::DISPOSITION_REVOKE,
                'notify'      => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Queue::assertPushed(SendOrderCodesEmail::class);
    });

    it('redirects away from an order that is already closed', function (string $status) {
        $order = makeOrder([['card' => $this->card, 'quantity' => 1]], status: $status);

        Livewire::test(EditOrderItems::class, ['record' => $order->getKey()])
            ->assertRedirect(OrderResourceUrl($order));
    })->with(['completed', 'cancelled']);

    it('pre-selects returning delivered codes to stock', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]], status: 'paid');

        Livewire::test(EditOrderItems::class, ['record' => $order->getKey()])
            ->assertOk()
            ->assertFormSet(['disposition' => OrderEditService::DISPOSITION_RESTOCK]);
    });

    it('restocks a removed delivered code when the admin leaves the default alone', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 3]], status: 'paid');

        Livewire::test(EditOrderItems::class, ['record' => $order->getKey()])
            ->fillForm([
                'items'  => [['gift_card_id' => $this->card->id, 'quantity' => 1, 'unit_price_bdt' => 1200]],
                'reason' => 'Customer only wanted one',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(GiftCardCode::revoked()->count())->toBe(0)
            ->and(GiftCardCode::where('gift_card_id', $this->card->id)->available()->count())->toBe(9);
    });

    it('hides the edit action on a completed order', function () {
        $completed = makeOrder([['card' => $this->card, 'quantity' => 1]], status: 'completed');
        $editable  = makeOrder([['card' => $this->card, 'quantity' => 1]], status: 'paid');

        Livewire::test(ListOrders::class)
            ->assertTableActionHidden('edit_items', $completed)
            ->assertTableActionVisible('edit_items', $editable);
    });
});

function OrderResourceUrl(Order $order): string
{
    return \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $order]);
}

describe('defensive handling of malformed orders', function () {
    beforeEach(function () {
        $this->admin = editAdmin();
        $this->card  = makeGiftCard('Steam $10', 1200, codes: 10);
    });

    it('folds away a duplicate line for the same product without leaking its codes', function () {
        $order = makeOrder([['card' => $this->card, 'quantity' => 2]]);

        // Nothing in the schema stops a second line for the same gift card.
        $duplicate = OrderItem::create([
            'order_id'       => $order->id,
            'gift_card_id'   => $this->card->id,
            'quantity'       => 3,
            'unit_price_bdt' => 1200,
            'subtotal_bdt'   => 3600,
        ]);

        GiftCardCode::where('gift_card_id', $this->card->id)
            ->where('status', 'available')
            ->limit(3)
            ->update(['status' => 'reserved', 'order_item_id' => $duplicate->id]);

        expect(GiftCardCode::where('gift_card_id', $this->card->id)->available()->count())->toBe(5);

        applyEdit($order, [['gift_card_id' => $this->card->id, 'quantity' => 4]]);

        $order->refresh()->load('items');

        expect($order->items)->toHaveCount(1)
            ->and($order->items->first()->quantity)->toBe(4)
            ->and(GiftCardCode::where('order_item_id', $order->items->first()->id)->where('status', 'reserved')->count())->toBe(4)
            ->and(GiftCardCode::where('gift_card_id', $this->card->id)->available()->count())->toBe(6)
            ->and((float) $order->total_bdt)->toBe(4800.0);
    });
});
