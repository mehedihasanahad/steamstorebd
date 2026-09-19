<?php

/**
 * Buyer inputs: what a product asks for before it can be fulfilled, how that
 * is validated, and how two Player IDs for the same SKU stay apart in the cart.
 */

use App\Models\GiftCardCategory;
use App\Services\BuyerInputSchema;
use App\Services\Cart;

function schemaProduct(array $fields): GiftCardCategory
{
    return seoProduct(seoBrand(), ['buyer_input_fields' => $fields]);
}

describe('reading the schema', function () {
    it('normalises a field the admin filled in loosely', function () {
        $product = schemaProduct([['key' => 'player_id']]);

        expect(app(BuyerInputSchema::class)->fields($product))->toBe([[
            'key'         => 'player_id',
            'label'       => 'player_id',
            'type'        => 'text',
            'required'    => true,
            'placeholder' => '',
            'help'        => '',
        ]]);
    });

    it('drops a field with no key, which could never be collected', function () {
        $product = schemaProduct([['label' => 'Nameless'], ['key' => 'zone_id', 'label' => 'Zone ID']]);

        expect(app(BuyerInputSchema::class)->fields($product))->toHaveCount(1);
    });

    it('falls back to text for a type it does not know', function () {
        $product = schemaProduct([['key' => 'x', 'type' => 'hologram']]);

        expect(app(BuyerInputSchema::class)->fields($product)[0]['type'])->toBe('text');
    });

    it('reports a product with no schema as needing nothing', function () {
        $product = seoProduct(seoBrand());

        expect($product->needsBuyerInput())->toBeFalse()
            ->and(app(BuyerInputSchema::class)->fields($product))->toBe([])
            ->and(app(BuyerInputSchema::class)->rules($product))->toBe([]);
    });
});

describe('validation rules', function () {
    it('requires a required field and allows an optional one to be blank', function () {
        $product = schemaProduct([
            ['key' => 'player_id', 'label' => 'Player ID', 'required' => true],
            ['key' => 'note', 'label' => 'Note', 'required' => false],
        ]);

        $rules = app(BuyerInputSchema::class)->rules($product);

        expect($rules['buyer_inputs.player_id'])->toContain('required')
            ->and($rules['buyer_inputs.note'])->toContain('nullable');
    });

    it('names each field the way the shopper sees it', function () {
        $product = schemaProduct([['key' => 'player_id', 'label' => 'Player ID']]);

        expect(app(BuyerInputSchema::class)->attributes($product))
            ->toBe(['buyer_inputs.player_id' => 'Player ID']);
    });

    it('rejects an add-to-cart that leaves a required field empty', function () {
        $product = schemaProduct([['key' => 'player_id', 'label' => 'Player ID', 'required' => true]]);
        $card    = seoCard($product, [], 3);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1])
            ->assertSessionHasErrors('buyer_inputs.player_id');

        expect(session('cart'))->toBeNull();
    });

    it('rejects an e-mail field that is not an e-mail', function () {
        $product = schemaProduct([['key' => 'account', 'label' => 'Account e-mail', 'type' => 'email']]);
        $card    = seoCard($product, [], 3);

        $this->post(route('cart.add'), [
            'gift_card_id' => $card->id,
            'quantity'     => 1,
            'buyer_inputs' => ['account' => 'not-an-email'],
        ])->assertSessionHasErrors('buyer_inputs.account');
    });

    it('accepts a line that satisfies the schema', function () {
        $product = schemaProduct([['key' => 'player_id', 'label' => 'Player ID']]);
        $card    = seoCard($product, [], 3);

        $this->post(route('cart.add'), [
            'gift_card_id' => $card->id,
            'quantity'     => 1,
            'buyer_inputs' => ['player_id' => '5123456789'],
        ])->assertSessionHasNoErrors();

        expect(app(Cart::class)->resolve()->first()['buyer_inputs'])
            ->toBe(['player_id' => '5123456789']);
    });
});

describe('sanitising what is stored', function () {
    it('keeps only the fields the product declared', function () {
        $product = schemaProduct([['key' => 'player_id', 'label' => 'Player ID']]);

        expect(app(BuyerInputSchema::class)->sanitise($product, [
            'player_id' => ' 5123456789 ',
            'is_admin'  => '1',
        ]))->toBe(['player_id' => '5123456789']);
    });

    it('never smuggles an undeclared field into the cart', function () {
        $product = schemaProduct([['key' => 'player_id', 'label' => 'Player ID']]);
        $card    = seoCard($product, [], 3);

        $this->post(route('cart.add'), [
            'gift_card_id' => $card->id,
            'quantity'     => 1,
            'buyer_inputs' => ['player_id' => '123', 'injected' => 'value'],
        ]);

        expect(app(Cart::class)->resolve()->first()['buyer_inputs'])
            ->toBe(['player_id' => '123']);
    });

    it('drops an optional field left blank rather than storing an empty string', function () {
        $product = schemaProduct([
            ['key' => 'player_id', 'label' => 'Player ID'],
            ['key' => 'note', 'label' => 'Note', 'required' => false],
        ]);

        expect(app(BuyerInputSchema::class)->sanitise($product, ['player_id' => '1', 'note' => '']))
            ->toBe(['player_id' => '1']);
    });
});

describe('cart keys', function () {
    it('keeps the bare card id when a line has no inputs', function () {
        // Every cart that exists today is keyed this way and must stay valid.
        expect(app(BuyerInputSchema::class)->cartKey(42, []))->toBe(42);
    });

    it('suffixes the key with a fingerprint of the inputs', function () {
        $key = app(BuyerInputSchema::class)->cartKey(42, ['player_id' => '123']);

        expect($key)->toBeString()->toStartWith('42:')
            ->and(strlen($key))->toBe(11);
    });

    it('gives the same inputs the same key regardless of their order', function () {
        $schema = app(BuyerInputSchema::class);

        expect($schema->cartKey(1, ['a' => '1', 'b' => '2']))
            ->toBe($schema->cartKey(1, ['b' => '2', 'a' => '1']));
    });

    it('lets two player ids for the same card live as separate lines', function () {
        $product = schemaProduct([['key' => 'player_id', 'label' => 'Player ID']]);
        $card    = seoCard($product, [], 5);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1, 'buyer_inputs' => ['player_id' => '111']]);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1, 'buyer_inputs' => ['player_id' => '222']]);

        $lines = app(Cart::class)->resolve();

        expect($lines)->toHaveCount(2)
            ->and($lines->pluck('buyer_inputs')->pluck('player_id')->all())->toBe(['111', '222']);
    });

    it('tops up the existing line when the same inputs are added twice', function () {
        $product = schemaProduct([['key' => 'player_id', 'label' => 'Player ID']]);
        $card    = seoCard($product, [], 5);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1, 'buyer_inputs' => ['player_id' => '111']]);
        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 2, 'buyer_inputs' => ['player_id' => '111']]);

        $lines = app(Cart::class)->resolve();

        expect($lines)->toHaveCount(1)
            ->and($lines->first()['quantity'])->toBe(2);
    });

    it('carries the inputs through to the order item', function () {
        $product = schemaProduct([['key' => 'player_id', 'label' => 'Player ID']]);
        $card    = seoCard($product, [], 5);

        $this->post(route('cart.add'), ['gift_card_id' => $card->id, 'quantity' => 1, 'buyer_inputs' => ['player_id' => '5123456789']]);

        $items = app(Cart::class)->checkoutItems();

        expect($items[0]['buyer_inputs'])->toBe(['player_id' => '5123456789']);
    });
});
