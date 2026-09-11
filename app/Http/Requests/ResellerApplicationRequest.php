<?php

namespace App\Http\Requests;

use App\Models\ResellerApplication;
use App\Rules\BangladeshiPhone;
use App\Services\ResellerProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResellerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ResellerProgram::fromSettings()->enabled();
    }

    public function rules(): array
    {
        $program = ResellerProgram::fromSettings();

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                // email:rfc alone accepts "someone@localhost"; a real business
                // contact always has a dotted domain.
                'regex:/^[^@\s]+@[^@\s]+\.[A-Za-z]{2,}$/',
                'max:150',
                Rule::unique('reseller_applications', 'email')->where('status', 'pending'),
            ],
            'phone' => ['required', 'string', 'max:20', new BangladeshiPhone],
            'whatsapp_number' => ['required', 'string', 'max:20', new BangladeshiPhone],
            'selling_platform' => ['required', 'string', Rule::in(array_keys($program->platformOptions()))],
            'gift_card_types' => ['required', 'array', 'min:1'],
            'gift_card_types.*' => ['required', 'string', Rule::in(array_keys($program->giftCardTypeOptions()))],
        ];
    }

    public function attributes(): array
    {
        return [
            'whatsapp_number' => 'WhatsApp number',
            'selling_platform' => 'selling platform',
            'gift_card_types' => 'gift card types',
            'gift_card_types.*' => 'gift card type',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'We already have a pending application for this email. We will get back to you soon.',
            'email.regex' => 'Please enter a complete email address, for example name@example.com.',
            'gift_card_types.required' => 'Please choose at least one gift card type you want to sell.',
            'gift_card_types.min' => 'Please choose at least one gift card type you want to sell.',
        ];
    }

    /**
     * Validated input shaped for persistence: phone numbers normalised to
     * +8801XXXXXXXXX and brand slugs resolved to the labels shown on the form,
     * so the record still reads correctly if a brand is later renamed.
     */
    public function applicationAttributes(): array
    {
        $options = ResellerProgram::fromSettings()->giftCardTypeOptions();

        return [
            'application_number' => ResellerApplication::generateApplicationNumber(),
            'name' => $this->string('name')->trim()->value(),
            'email' => $this->string('email')->trim()->lower()->value(),
            'phone' => BangladeshiPhone::normalise($this->string('phone')->value()),
            'whatsapp_number' => BangladeshiPhone::normalise($this->string('whatsapp_number')->value()),
            'selling_platform' => $this->string('selling_platform')->value(),
            'gift_card_types' => array_values(array_map(
                fn (string $slug): string => $options[$slug] ?? $slug,
                $this->input('gift_card_types', []),
            )),
            'status' => 'pending',
            'ip_address' => $this->ip(),
        ];
    }
}
