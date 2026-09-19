<?php

namespace App\Services;

use App\Models\GiftCardCategory;

/**
 * What a product asks the buyer for before it can be fulfilled — a Player ID,
 * a Zone ID, an account e-mail.
 *
 * The schema is admin-authored JSON on the product, so nothing may be trusted
 * about its shape: every reader here tolerates a missing key rather than
 * assuming the admin filled every field in. The storefront echoes these rules
 * into the markup for instant feedback; this class is what actually decides
 * whether an input is acceptable.
 */
class BuyerInputSchema
{
    /** @var array<string, string> Field type => the validation rule it implies. */
    private const TYPE_RULES = [
        'text'   => 'string',
        'number' => 'string',
        'email'  => 'email',
    ];

    /**
     * The declared fields, normalised. A field with no key is dropped: it
     * could not be collected or stored, so it cannot be asked for.
     *
     * @return list<array{key: string, label: string, type: string, required: bool, placeholder: string, help: string}>
     */
    public function fields(GiftCardCategory $product): array
    {
        return collect($product->buyerInputSchema())
            ->filter(fn ($field) => is_array($field) && filled($field['key'] ?? null))
            ->map(fn (array $field) => [
                'key'         => (string) $field['key'],
                'label'       => (string) ($field['label'] ?? $field['key']),
                'type'        => array_key_exists($field['type'] ?? '', self::TYPE_RULES) ? $field['type'] : 'text',
                'required'    => (bool) ($field['required'] ?? true),
                'placeholder' => (string) ($field['placeholder'] ?? ''),
                'help'        => (string) ($field['help'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * Validation rules for a request carrying this product's inputs.
     *
     * @return array<string, list<string>>
     */
    public function rules(GiftCardCategory $product): array
    {
        $rules = [];

        foreach ($this->fields($product) as $field) {
            $rules['buyer_inputs.' . $field['key']] = [
                $field['required'] ? 'required' : 'nullable',
                self::TYPE_RULES[$field['type']],
                'max:190',
            ];
        }

        return $rules;
    }

    /**
     * Human names for the validation messages, so a shopper reads
     * "The Player ID field is required" rather than "buyer_inputs.player_id".
     *
     * @return array<string, string>
     */
    public function attributes(GiftCardCategory $product): array
    {
        return collect($this->fields($product))
            ->mapWithKeys(fn (array $field) => ['buyer_inputs.' . $field['key'] => $field['label']])
            ->all();
    }

    /**
     * Keep only what the product actually asked for. A field the schema does
     * not declare is not stored, so a crafted request cannot smuggle extra
     * data into an order item.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function sanitise(GiftCardCategory $product, array $input): array
    {
        $collected = [];

        foreach ($this->fields($product) as $field) {
            $value = $input[$field['key']] ?? null;

            if (is_scalar($value) && filled($value)) {
                $collected[$field['key']] = trim((string) $value);
            }
        }

        return $collected;
    }

    /**
     * A short, stable fingerprint of one line's inputs.
     *
     * This is what lets two top-ups of the same SKU for two different Player
     * IDs live as separate cart lines instead of silently overwriting each
     * other. Lines with no inputs get no suffix, so every cart that exists
     * today keeps its plain integer keys.
     */
    public function cartKey(int $giftCardId, array $inputs): string|int
    {
        if ($inputs === []) {
            return $giftCardId;
        }

        ksort($inputs);

        return $giftCardId . ':' . substr(hash('sha256', json_encode($inputs)), 0, 8);
    }
}
