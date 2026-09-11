<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts the three ways a Bangladeshi mobile number is written in the wild —
 * 01XXXXXXXXX, 8801XXXXXXXXX and +8801XXXXXXXXX — with or without spaces and
 * dashes, and rejects anything outside the 013-019 operator range.
 */
class BangladeshiPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || self::normalise($value) === null) {
            $fail('The :attribute must be a valid Bangladeshi mobile number (e.g. 01712345678).');
        }
    }

    /**
     * Returns the number as +8801XXXXXXXXX, or null when it is not a valid
     * Bangladeshi mobile number. Pure, so it can be unit tested directly.
     */
    public static function normalise(string $value): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', trim($value)) ?? '';
        $digits = ltrim($digits, '+');

        if (! preg_match('/^\d+$/', $digits)) {
            return null;
        }

        $local = match (true) {
            str_starts_with($digits, '880') => substr($digits, 3),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };

        return preg_match('/^1[3-9]\d{8}$/', $local) === 1
            ? '+880'.$local
            : null;
    }
}
