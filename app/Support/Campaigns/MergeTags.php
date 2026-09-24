<?php

namespace App\Support\Campaigns;

/**
 * The handful of placeholders a campaign body may carry.
 *
 * Written as {{ name }}, which is safe here precisely because the body is
 * stored content rather than a Blade template — it is echoed raw, so Blade
 * never sees these and they survive to be replaced per recipient.
 *
 * A missing name falls back rather than leaving "Hi ," at the top of the
 * message, which is the tell that an e-mail was sent by a machine that did not
 * check.
 */
final class MergeTags
{
    public const FALLBACK_NAME = 'there';

    public static function apply(?string $body, ?string $name, string $email): string
    {
        $name  = trim((string) $name);
        $first = $name === '' ? '' : (preg_split('/\s+/', $name)[0] ?? '');

        return str_replace(
            ['{{ name }}', '{{name}}', '{{ first_name }}', '{{first_name}}', '{{ email }}', '{{email}}'],
            [
                e($name ?: self::FALLBACK_NAME),
                e($name ?: self::FALLBACK_NAME),
                e($first ?: self::FALLBACK_NAME),
                e($first ?: self::FALLBACK_NAME),
                e($email),
                e($email),
            ],
            (string) $body,
        );
    }

    /** Shown under the editor so an admin knows what is available. */
    public static function hint(): string
    {
        return 'Placeholders: {{ name }}, {{ first_name }}, {{ email }} — each falls back to "'
            . self::FALLBACK_NAME . '" when we do not know it.';
    }
}
