<?php

namespace App\Rules;

use App\Services\Turnstile as TurnstileVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a Cloudflare Turnstile token against the action it claims to
 * belong to.
 *
 * Applied only where Turnstile::enabled() is true, so an unconfigured
 * environment never produces an error nobody can clear.
 */
class Turnstile implements ValidationRule
{
    public function __construct(private string $action) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passed = app(TurnstileVerifier::class)->verify(
            is_string($value) ? $value : null,
            $this->action,
            request()->ip(),
        );

        if (! $passed) {
            $fail('We could not confirm you are human. Please reload the page and try again.');
        }
    }
}
