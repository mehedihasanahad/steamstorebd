<?php

namespace App\Support\Campaigns;

use App\Models\EmailCampaign;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * What each audience is, and what can be asked about it.
 *
 * Every audience resolves to the same shape — one row per address, carrying
 * `email`, `name` and whatever columns its filters need — so the rule compiler
 * does not have to know which one it is working on. The aggregates are built
 * inside a derived table rather than with GROUP BY plus HAVING, because a
 * filter such as "bought Steam" has to be a condition on the customer and not
 * on one of their orders: applied to the rows being grouped it would quietly
 * change what "total spent" adds up.
 */
final class AudienceSchema
{
    /** An order only counts once the money is real. */
    public const COUNTED_STATUSES = ['paid', 'completed'];

    public function __construct(public readonly string $audience) {}

    public static function for(string $audience): self
    {
        return new self($audience);
    }

    public static function labels(): array
    {
        return [
            EmailCampaign::AUDIENCE_BUYERS    => 'Buyers — anyone who has paid for an order',
            EmailCampaign::AUDIENCE_RESELLERS => 'Approved resellers',
            EmailCampaign::AUDIENCE_MANUAL    => 'A list I paste in',
        ];
    }

    /** One row per address, with the columns this audience's fields read. */
    public function baseQuery(): Builder
    {
        return match ($this->audience) {
            EmailCampaign::AUDIENCE_RESELLERS => $this->resellerQuery(),
            default                           => $this->buyerQuery(),
        };
    }

    private function buyerQuery(): Builder
    {
        $aggregate = DB::table('orders')
            ->selectRaw('LOWER(TRIM(customer_email)) as email')
            ->selectRaw('MAX(customer_name) as name')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total_bdt), 0) as total_spent')
            ->selectRaw('MAX(created_at) as last_order_at')
            ->selectRaw('MIN(created_at) as first_order_at')
            ->whereIn('status', self::COUNTED_STATUSES)
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->groupBy(DB::raw('LOWER(TRIM(customer_email))'));

        return DB::query()->fromSub($aggregate, 'c');
    }

    private function resellerQuery(): Builder
    {
        $counted = "'" . implode("','", self::COUNTED_STATUSES) . "'";

        $aggregate = DB::table('reseller_applications as ra')
            ->selectRaw('LOWER(TRIM(ra.email)) as email')
            ->selectRaw('ra.name as name')
            ->selectRaw('ra.selling_platform as selling_platform')
            ->selectRaw('COALESCE(ra.reviewed_at, ra.created_at) as approved_at')
            ->selectRaw("(select COALESCE(SUM(o.total_bdt), 0) from orders o where LOWER(TRIM(o.customer_email)) = LOWER(TRIM(ra.email)) and o.status in ({$counted})) as total_spent")
            ->selectRaw("(select COUNT(*) from orders o where LOWER(TRIM(o.customer_email)) = LOWER(TRIM(ra.email)) and o.status in ({$counted})) as order_count")
            ->selectRaw("(select MAX(o.created_at) from orders o where LOWER(TRIM(o.customer_email)) = LOWER(TRIM(ra.email)) and o.status in ({$counted})) as last_order_at")
            ->where('ra.status', 'approved')
            // One approved application per address, newest wins.
            ->groupBy(DB::raw('LOWER(TRIM(ra.email))'));

        return DB::query()->fromSub($aggregate, 'c');
    }

    /** @return array<string,AudienceField> */
    public function fields(): array
    {
        $fields = [
            AudienceField::number('total_spent', 'Total spent', 'c.total_spent', '৳'),
            AudienceField::number('order_count', 'Number of orders', 'c.order_count'),
            AudienceField::date('last_order_at', 'Last ordered', 'c.last_order_at'),

            AudienceField::select('brand', 'Has bought brand', 'brands',
                fn (Builder $query, string $operator, mixed $value) => $this->constrainByCatalog($query, $operator, $value, 'main_category_id')),

            AudienceField::select('product', 'Has bought product', 'products',
                fn (Builder $query, string $operator, mixed $value) => $this->constrainByCatalog($query, $operator, $value, 'category_id')),
        ];

        if ($this->audience === EmailCampaign::AUDIENCE_RESELLERS) {
            $fields[] = AudienceField::date('approved_at', 'Approved', 'c.approved_at');
            $fields[] = AudienceField::select('selling_platform', 'Selling platform', 'platforms',
                function (Builder $query, string $operator, mixed $value) {
                    $values = array_values(array_filter((array) $value, fn ($v) => $v !== null && $v !== ''));

                    if ($values === []) {
                        return;
                    }

                    $operator === 'not_in'
                        ? $query->whereNotIn('c.selling_platform', $values)
                        : $query->whereIn('c.selling_platform', $values);
                });

            return collect($fields)->keyBy('key')->all();
        }

        $fields[] = AudienceField::date('first_order_at', 'First ordered', 'c.first_order_at');

        $fields[] = AudienceField::boolean('is_registered', 'Has an account',
            fn (Builder $query, string $operator, mixed $value) => $this->constrainByExistence(
                $query,
                (bool) $value,
                fn () => DB::table('users')
                    ->whereRaw('LOWER(TRIM(users.email)) = c.email')
                    ->where('users.is_admin', false),
            ));

        $fields[] = AudienceField::boolean('is_reseller', 'Is an approved reseller',
            fn (Builder $query, string $operator, mixed $value) => $this->constrainByExistence(
                $query,
                (bool) $value,
                fn () => DB::table('reseller_applications')
                    ->whereRaw('LOWER(TRIM(reseller_applications.email)) = c.email')
                    ->where('reseller_applications.status', 'approved'),
            ));

        return collect($fields)->keyBy('key')->all();
    }

    public function field(string $key): ?AudienceField
    {
        return $this->fields()[$key] ?? null;
    }

    /** Options for the select fields, resolved when the admin form is built. */
    public static function options(string $name): array
    {
        return match ($name) {
            'brands'   => MainCategory::orderBy('name')->pluck('name', 'id')->all(),
            'products' => GiftCardCategory::orderBy('name')->pluck('name', 'id')->all(),
            'platforms' => DB::table('reseller_applications')
                ->select('selling_platform')
                ->distinct()
                ->orderBy('selling_platform')
                ->pluck('selling_platform', 'selling_platform')
                ->all(),
            default => [],
        };
    }

    /**
     * "Has bought X" is an EXISTS against this customer's own orders, not a
     * join — a join would multiply a customer by their matching order lines
     * and the same address would come back several times.
     */
    private function constrainByCatalog(Builder $query, string $operator, mixed $value, string $column): void
    {
        $values = array_values(array_filter((array) $value, fn ($v) => $v !== null && $v !== ''));

        if ($values === []) {
            return;
        }

        $this->constrainByExistence(
            $query,
            $operator !== 'not_in',
            fn () => DB::table('orders as o')
                ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
                ->join('gift_cards as gc', 'gc.id', '=', 'oi.gift_card_id')
                ->join('gift_card_categories as cat', 'cat.id', '=', 'gc.category_id')
                ->whereRaw('LOWER(TRIM(o.customer_email)) = c.email')
                ->whereIn('o.status', self::COUNTED_STATUSES)
                ->whereIn($column === 'main_category_id' ? 'cat.main_category_id' : 'cat.id', $values),
        );
    }

    private function constrainByExistence(Builder $query, bool $shouldExist, callable $subquery): void
    {
        $sub = $subquery()->selectRaw('1');

        $shouldExist
            ? $query->whereExists($sub)
            : $query->whereNotExists($sub);
    }
}
