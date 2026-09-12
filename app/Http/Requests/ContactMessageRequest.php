<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:100'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Validated input shaped for persistence.
     */
    public function messageAttributes(): array
    {
        return [
            'name'       => $this->string('name')->trim()->value(),
            'email'      => $this->string('email')->trim()->lower()->value(),
            'message'    => $this->string('message')->trim()->value(),
            'ip_address' => $this->ip(),
        ];
    }
}
