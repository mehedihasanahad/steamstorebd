<?php

namespace App\Support\Campaigns;

use Closure;

/**
 * One thing a campaign can filter on.
 *
 * A field is either a plain column on the audience's derived table, or a
 * closure that constrains the query itself — which is what "has bought this
 * brand" needs, since that is an EXISTS against another table rather than a
 * value sitting on the row.
 */
final class AudienceField
{
    public const TYPE_NUMBER  = 'number';
    public const TYPE_DATE    = 'date';
    public const TYPE_SELECT  = 'select';
    public const TYPE_BOOLEAN = 'boolean';

    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type,
        public readonly ?string $column = null,
        public readonly ?Closure $constrain = null,
        public readonly ?string $options = null,
        public readonly ?string $suffix = null,
    ) {}

    public static function number(string $key, string $label, string $column, ?string $suffix = null): self
    {
        return new self($key, $label, self::TYPE_NUMBER, column: $column, suffix: $suffix);
    }

    public static function date(string $key, string $label, string $column): self
    {
        return new self($key, $label, self::TYPE_DATE, column: $column);
    }

    /** `$options` names a list the admin form resolves — see AudienceSchema::options(). */
    public static function select(string $key, string $label, string $options, Closure $constrain): self
    {
        return new self($key, $label, self::TYPE_SELECT, constrain: $constrain, options: $options);
    }

    public static function boolean(string $key, string $label, Closure $constrain): self
    {
        return new self($key, $label, self::TYPE_BOOLEAN, constrain: $constrain);
    }

    /** The operators this field's type understands, as value => label. */
    public function operators(): array
    {
        return match ($this->type) {
            self::TYPE_NUMBER => [
                '>=' => 'is at least',
                '<=' => 'is at most',
                '='  => 'is exactly',
                '>'  => 'is more than',
                '<'  => 'is less than',
            ],
            self::TYPE_DATE => [
                'in_last_days'     => 'is within the last (days)',
                'not_in_last_days' => 'is not within the last (days)',
                'after'            => 'is after',
                'before'           => 'is before',
            ],
            self::TYPE_SELECT => [
                'in'     => 'is any of',
                'not_in' => 'is none of',
            ],
            self::TYPE_BOOLEAN => [
                'is' => 'is',
            ],
            default => [],
        };
    }
}
