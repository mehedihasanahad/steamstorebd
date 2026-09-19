<?php

use App\Filament\Widgets\StockAlertWidget;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;

/**
 * A card of a given fulfilment type under its own brand and product. Slugs are
 * unique per call so a single test can build several.
 */
function typedCard(string $type, array $overrides = [], int $codes = 0): GiftCard
{
    $id = uniqid();

    return seoCard(
        seoProduct(
            seoBrand(['name' => "Brand {$id}", 'slug' => "brand-{$id}"]),
            ['name' => "Product {$id}", 'slug' => "product-{$id}"],
        ),
        array_merge(['fulfilment_type' => $type, 'slug' => "card-{$id}"], $overrides),
        $codes,
    );
}

describe('fulfilment type defaults', function () {
    it('defaults every new card to the code pool', function () {
        $card = seoCard(seoProduct(seoBrand()));

        expect($card->fresh()->fulfilment_type)->toBe(GiftCard::FULFILMENT_CODE_POOL)
            ->and($card->fresh()->usesCodePool())->toBeTrue()
            ->and($card->fresh()->needsManualFulfilment())->toBeFalse();
    });

    it('starts manual stock at zero so no existing row inherits a value', function () {
        $card = seoCard(seoProduct(seoBrand()));

        expect($card->fresh()->manual_stock)->toBe(0);
    });

    it('recognises the manual and credentials types', function () {
        expect(typedCard(GiftCard::FULFILMENT_MANUAL)->needsManualFulfilment())->toBeTrue()
            ->and(typedCard(GiftCard::FULFILMENT_CREDENTIALS)->needsManualFulfilment())->toBeTrue();
    });
});

describe('stock accessor', function () {
    it('counts available codes for a code-pool card', function () {
        $card = typedCard(GiftCard::FULFILMENT_CODE_POOL, [], 4);

        expect($card->fresh()->stock_count)->toBe(4);
    });

    it('ignores the stored column for a code-pool card', function () {
        $card = typedCard(GiftCard::FULFILMENT_CODE_POOL, [], 2);
        GiftCard::whereKey($card->id)->update(['stock_count' => 99]);

        expect($card->fresh()->stock_count)->toBe(2);
    });

    it('reads manual_stock for a manually fulfilled card', function () {
        $card = typedCard(GiftCard::FULFILMENT_MANUAL, ['manual_stock' => 7]);

        expect($card->fresh()->stock_count)->toBe(7);
    });

    /**
     * The whole reason manual_stock is a new column. A production row can carry
     * a stale stock_count, and flipping it to manual must not promote that
     * number to authoritative stock.
     */
    it('never inherits a stale stock_count when a card becomes manual', function () {
        $card = typedCard(GiftCard::FULFILMENT_CODE_POOL, [], 0);
        GiftCard::whereKey($card->id)->update(['stock_count' => 99]);

        $card->update(['fulfilment_type' => GiftCard::FULFILMENT_MANUAL]);

        expect($card->fresh()->stock_count)->toBe(0);
    });

    it('names the column that actually holds its stock', function () {
        expect(typedCard(GiftCard::FULFILMENT_CODE_POOL)->stockColumn())->toBe('stock_count')
            ->and(typedCard(GiftCard::FULFILMENT_MANUAL)->stockColumn())->toBe('manual_stock');
    });
});

describe('stock scopes', function () {
    it('finds a stocked code-pool card', function () {
        $card = typedCard(GiftCard::FULFILMENT_CODE_POOL, [], 3);
        GiftCard::whereKey($card->id)->update(['stock_count' => 3]);

        expect(GiftCard::inStock()->count())->toBe(1);
    });

    it('finds a stocked manual card, which a stock_count filter would miss', function () {
        typedCard(GiftCard::FULFILMENT_MANUAL, ['manual_stock' => 5]);

        expect(GiftCard::inStock()->count())->toBe(1);
    });

    it('excludes a manual card with nothing left', function () {
        typedCard(GiftCard::FULFILMENT_MANUAL, ['manual_stock' => 0]);

        expect(GiftCard::inStock()->count())->toBe(0);
    });

    it('flags a low manual card to the stock alert', function () {
        typedCard(GiftCard::FULFILMENT_MANUAL, ['manual_stock' => 1]);

        expect(StockAlertWidget::canView())->toBeTrue()
            ->and(GiftCard::query()->lowStock(3)->count())->toBe(1);
    });

    it('leaves a well stocked manual card out of the alert', function () {
        typedCard(GiftCard::FULFILMENT_MANUAL, ['manual_stock' => 50]);

        expect(GiftCard::query()->lowStock(3)->count())->toBe(0);
    });
});

describe('buyer input schema', function () {
    it('asks for nothing by default', function () {
        $product = seoProduct(seoBrand());

        expect($product->buyerInputSchema())->toBe([])
            ->and($product->needsBuyerInput())->toBeFalse();
    });

    it('stores and reads back a declared schema', function () {
        $product = seoProduct(seoBrand(), [
            'buyer_input_fields' => [
                ['key' => 'player_id', 'label' => 'Player ID', 'type' => 'text', 'required' => true],
                ['key' => 'zone_id', 'label' => 'Zone ID', 'type' => 'number', 'required' => true],
            ],
        ]);

        $schema = GiftCardCategory::find($product->id)->buyerInputSchema();

        expect($schema)->toHaveCount(2)
            ->and($schema[0]['key'])->toBe('player_id')
            ->and(GiftCardCategory::find($product->id)->needsBuyerInput())->toBeTrue();
    });
});
