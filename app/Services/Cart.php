<?php

namespace App\Services;

use App\Models\GiftCard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * The session cart.
 *
 * Every read goes through resolve(), which is deliberately forgiving: this
 * store keeps sessions in the database, so a cart built by the previous
 * release is handed to this one. A line missing the fields added since is
 * defaulted rather than rejected, and a line whose card has since been
 * withdrawn is dropped on its own instead of voiding the whole cart.
 */
class Cart
{
    public const SESSION_KEY = 'cart';

    public function __construct(private BuyerInputSchema $schema) {}

    /** @return array<array-key, array<string, mixed>> */
    public function raw(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    public function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Add a line, or top up the one already there.
     *
     * @param  array<string, string>  $buyerInputs
     */
    public function add(GiftCard $giftCard, int $quantity, array $buyerInputs = []): string|int
    {
        $key  = $this->schema->cartKey($giftCard->id, $buyerInputs);
        $cart = $this->raw();

        $cart[$key] = [
            'gift_card_id' => $giftCard->id,
            'quantity'     => $quantity,
            'price'        => $giftCard->price_bdt,
            'selected'     => true,
            'buyer_inputs' => $buyerInputs,
        ];

        Session::put(self::SESSION_KEY, $cart);

        return $key;
    }

    public function setQuantity(string|int $key, int $quantity): bool
    {
        $cart = $this->raw();

        if (! array_key_exists($key, $cart)) {
            return false;
        }

        $cart[$key]['quantity'] = $quantity;
        Session::put(self::SESSION_KEY, $cart);

        return true;
    }

    /** Tick or untick one line. Unticked lines stay in the cart but do not check out. */
    public function setSelected(string|int $key, bool $selected): bool
    {
        $cart = $this->raw();

        if (! array_key_exists($key, $cart)) {
            return false;
        }

        $cart[$key]['selected'] = $selected;
        Session::put(self::SESSION_KEY, $cart);

        return true;
    }

    public function remove(string|int $key): void
    {
        $cart = $this->raw();
        unset($cart[$key]);
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Every live line, with its card loaded.
     *
     * A line is dropped when its card is gone or switched off. It is kept,
     * and marked short, when the card merely has less stock than the line
     * asks for — that is something the shopper can fix by lowering the
     * quantity, so silently emptying their cart would be the wrong answer.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function resolve(): Collection
    {
        $cart = $this->raw();

        if ($cart === []) {
            return collect();
        }

        $cards = GiftCard::with('category.mainCategory')
            ->whereIn('id', collect($cart)->pluck('gift_card_id')->filter()->unique())
            ->withAvailableCodesCount()
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->map(function (array $line, string|int $key) use ($cards) {
                $card = $cards->get($line['gift_card_id'] ?? $key);

                if (! $card || ! $card->is_active) {
                    return null;
                }

                $quantity = max(1, (int) ($line['quantity'] ?? 1));

                return [
                    'key'          => $key,
                    'gift_card_id' => $card->id,
                    'gift_card'    => $card,
                    'quantity'     => $quantity,
                    'price'        => (float) $card->price_bdt,
                    // Pre-deploy lines carry no flag; they behave exactly as
                    // they did, which is as part of the order.
                    'selected'     => (bool) ($line['selected'] ?? true),
                    'buyer_inputs' => (array) ($line['buyer_inputs'] ?? []),
                    'in_stock'     => $card->stock_count >= $quantity,
                    'max'          => $card->maxOrderableQuantity(),
                    'min'          => max(1, $card->min_quantity),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * The lines that will actually be ordered.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function selected(): Collection
    {
        return $this->resolve()->filter(fn (array $line) => $line['selected'])->values();
    }

    /**
     * Lines shaped the way OrderService expects, or null when any of them
     * cannot be fulfilled at the quantity asked for.
     *
     * @return list<array<string, mixed>>|null
     */
    public function checkoutItems(): ?array
    {
        $lines = $this->selected();

        if ($lines->isEmpty()) {
            return null;
        }

        foreach ($lines as $line) {
            if (! $line['in_stock']) {
                return null;
            }
        }

        return $lines->map(fn (array $line) => [
            'gift_card_id' => $line['gift_card_id'],
            'gift_card'    => $line['gift_card'],
            'quantity'     => $line['quantity'],
            'price'        => $line['price'],
            'buyer_inputs' => $line['buyer_inputs'],
        ])->all();
    }

    /**
     * Lines grouped by the product they belong to, which is how the cart page
     * presents them.
     *
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    public function grouped(): Collection
    {
        return $this->resolve()->groupBy(fn (array $line) => $line['gift_card']->category?->name ?? 'Other');
    }

    /** What the selected lines add up to before any discount. */
    public function subtotal(): float
    {
        return (float) $this->selected()->sum(fn (array $line) => $line['price'] * $line['quantity']);
    }

    /**
     * What the shopper saves against the compare-at prices on the selected
     * lines. Presentational: it is not a discount the order records.
     */
    public function savings(): float
    {
        return (float) $this->selected()->sum(function (array $line) {
            $saved = $line['gift_card']->discountAmount();

            return $saved === null ? 0 : $saved * $line['quantity'];
        });
    }

    public function selectedCount(): int
    {
        return $this->selected()->count();
    }
}
