<?php

/**
 * A card stocked as more than one code.
 *
 * Suppliers sometimes ship a 20 dollar card as two tens. That is still one
 * thing to sell, so it stays one row in gift_card_codes and every count, lock
 * and reservation is untouched — the row just holds both strings, joined by a
 * spaced plus. The risk worth testing is the other end: a buyer who is shown
 * only the first of them has paid for twenty and received ten.
 */

use App\Filament\Resources\GiftCardCodeResource\Pages\ListGiftCardCodes;
use App\Mail\OrderCodesMail;
use App\Models\BkashPayment;
use App\Models\GiftCard;
use App\Models\GiftCardCode;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function splitCard(): GiftCard
{
    return seoCard(seoProduct(seoBrand()), ['name' => 'Steam Wallet $20', 'slug' => 'steam-20']);
}

function stockCode(GiftCard $card, string $code): GiftCardCode
{
    return GiftCardCode::create([
        'gift_card_id'      => $card->id,
        'code'              => $code,
        'status'            => 'available',
        'added_by_admin_id' => User::factory()->create()->id,
    ]);
}

function buySingleUnit(GiftCard $card): Order
{
    Queue::fake();

    $order = app(OrderService::class)->createOrder(
        ['name' => 'Rahim Uddin', 'email' => 'rahim@example.com', 'phone' => '01700000000'],
        [[
            'gift_card_id' => $card->id,
            'gift_card'    => $card,
            'quantity'     => 1,
            'price'        => $card->price_bdt,
            'buyer_inputs' => [],
        ]],
    );

    BkashPayment::create(['order_id' => $order->id, 'amount' => $order->total_bdt, 'status' => 'initiated']);
    app(OrderService::class)->completeOrder($order, ['trxID' => 'TRX1']);

    return $order->fresh(['items.orderItemCodes.giftCardCode', 'items.giftCard.category']);
}

describe('reading a code that is really several', function () {
    it('splits on a spaced plus', function () {
        expect(GiftCardCode::split('AAA-111 + BBB-222'))->toBe(['AAA-111', 'BBB-222']);
    });

    it('leaves a plus that is part of the code alone', function () {
        // Only the spaces make it a separator, so a code containing a plus
        // survives intact rather than being torn in two.
        expect(GiftCardCode::split('AB+CD'))->toBe(['AB+CD']);
    });

    it('writes it the same way however it was typed', function (string $typed) {
        expect(stockCode(splitCard(), $typed)->code)->toBe('AAA-111 + BBB-222');
    })->with([
        'AAA-111 + BBB-222',
        'AAA-111   +   BBB-222',
        "  AAA-111 + BBB-222  ",
    ]);

    it('knows how many codes make up the card', function () {
        $card = splitCard();

        expect(stockCode($card, 'AAA-111')->isSplit())->toBeFalse()
            ->and(stockCode($card, 'BBB-222 + CCC-333')->partCount())->toBe(2)
            ->and(stockCode($card, 'DDD-444 + EEE-555 + FFF-666')->partCount())->toBe(3);
    });
});

describe('stock', function () {
    it('counts a two-code card as one card, not two', function () {
        $card = splitCard();
        stockCode($card, 'AAA-111 + BBB-222');

        expect($card->fresh()->availableCodesCount())->toBe(1);
    });

    it('is emptied by a single sale', function () {
        $card = splitCard();
        stockCode($card, 'AAA-111 + BBB-222');

        buySingleUnit($card);

        expect($card->fresh()->availableCodesCount())->toBe(0);
    });
});

describe('what the buyer receives', function () {
    it('gets every code in the e-mail, not just the first', function () {
        $card = splitCard();
        stockCode($card, 'AAA-111 + BBB-222');

        $order = buySingleUnit($card);
        $html  = (new OrderCodesMail($order))->render();

        expect($html)->toContain('AAA-111')
            ->and($html)->toContain('BBB-222')
            // Never the joined string, which would be pasted in as one code.
            ->and($html)->not->toContain('AAA-111 + BBB-222');
    });

    it('is told to redeem all of them', function () {
        $card = splitCard();
        stockCode($card, 'AAA-111 + BBB-222');

        expect((new OrderCodesMail(buySingleUnit($card)))->render())
            ->toContain('redeem all 2 codes');
    });

    it('says nothing extra when the card is a single code', function () {
        $card = splitCard();
        stockCode($card, 'AAA-111');

        $html = (new OrderCodesMail(buySingleUnit($card)))->render();

        expect($html)->toContain('AAA-111')
            ->and($html)->not->toContain('redeem all');
    });

    it('gets every code in the plain-text half too', function () {
        $card = splitCard();
        stockCode($card, 'AAA-111 + BBB-222');

        $order   = buySingleUnit($card);
        $mail    = new OrderCodesMail($order);
        $content = $mail->content();

        $text = view($content->text, array_merge($mail->buildViewData(), $content->with))->render();

        expect($text)->toContain('AAA-111')->and($text)->toContain('BBB-222');
    });

    it('sees them separately on the order page', function () {
        $card = splitCard();
        stockCode($card, 'AAA-111 + BBB-222');

        $order = buySingleUnit($card);
        $user  = User::factory()->create();
        $order->update(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('orders.show', $order->order_number))
            ->assertSuccessful()
            ->assertSee('AAA-111')
            ->assertSee('BBB-222')
            ->assertSee('Redeem all 2 codes below to get the full value.');
    });
});

describe('importing them', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    it('makes one card out of a line holding two codes', function () {
        $card = splitCard();

        Livewire::test(ListGiftCardCodes::class)
            ->callTableAction('bulk_import', data: [
                'gift_card_id' => $card->id,
                'codes'        => "AAA-111 + BBB-222\nCCC-333",
            ]);

        expect(GiftCardCode::where('gift_card_id', $card->id)->count())->toBe(2)
            ->and(GiftCardCode::where('code', 'AAA-111 + BBB-222')->exists())->toBeTrue()
            ->and($card->fresh()->stock_count)->toBe(2);
    });

    it('refuses a line that repeats a code already stocked inside a bundle', function () {
        // Selling the same code twice is the one import mistake that costs
        // money, and an exact match on the whole line would not see it.
        $card = splitCard();
        stockCode($card, 'AAA-111 + BBB-222');

        Livewire::test(ListGiftCardCodes::class)
            ->callTableAction('bulk_import', data: [
                'gift_card_id' => $card->id,
                'codes'        => 'BBB-222 + CCC-333',
            ]);

        expect(GiftCardCode::where('gift_card_id', $card->id)->count())->toBe(1);
    });

    it('refuses a repeat inside the same paste', function () {
        $card = splitCard();

        Livewire::test(ListGiftCardCodes::class)
            ->callTableAction('bulk_import', data: [
                'gift_card_id' => $card->id,
                'codes'        => "AAA-111 + BBB-222\nBBB-222 + CCC-333",
            ]);

        expect(GiftCardCode::where('gift_card_id', $card->id)->count())->toBe(1);
    });
});
