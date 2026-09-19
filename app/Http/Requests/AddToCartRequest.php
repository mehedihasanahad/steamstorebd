<?php

namespace App\Http\Requests;

use App\Models\GiftCard;
use App\Services\BuyerInputSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adding one line to the cart.
 *
 * The quantity bounds and the buyer-input rules both depend on which card is
 * being added, so they are resolved after the card is known rather than
 * declared up front. The storefront echoes the same rules into the markup for
 * instant feedback; what this class decides is the authority.
 */
class AddToCartRequest extends FormRequest
{
    private ?GiftCard $giftCard = null;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'gift_card_id' => ['required', 'exists:gift_cards,id'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'redirect_to'  => ['nullable', Rule::in(['cart', 'checkout'])],
        ];

        $card = $this->card();

        if ($card === null) {
            return $rules;
        }

        $rules['quantity'] = [
            'required',
            'integer',
            'min:' . max(1, $card->min_quantity),
            'max:' . max(1, $card->max_quantity),
        ];

        return array_merge($rules, app(BuyerInputSchema::class)->rules($card->category));
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $card = $this->card();

        return $card === null ? [] : app(BuyerInputSchema::class)->attributes($card->category);
    }

    /** The card being added, loaded once per request. */
    public function card(): ?GiftCard
    {
        if ($this->giftCard === null && $this->filled('gift_card_id')) {
            $this->giftCard = GiftCard::with('category')->find($this->input('gift_card_id'));
        }

        return $this->giftCard;
    }

    /**
     * Only the fields the product declared, trimmed.
     *
     * @return array<string, string>
     */
    public function buyerInputs(): array
    {
        $card = $this->card();

        if ($card?->category === null) {
            return [];
        }

        return app(BuyerInputSchema::class)->sanitise($card->category, (array) $this->input('buyer_inputs', []));
    }
}
