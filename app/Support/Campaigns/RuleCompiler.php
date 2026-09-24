<?php

namespace App\Support\Campaigns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Turns the stored filter tree into SQL.
 *
 * The tree is what the admin form saves:
 *
 *     ['match' => 'all', 'rules' => [
 *         ['type' => 'condition', 'data' => [...]],
 *         ['type' => 'group',     'data' => ['match' => 'any', 'rules' => [...]]],
 *     ]]
 *
 * Recursion here has no depth limit even though the form only offers one level
 * of nesting. Two levels answers every campaign anyone has actually asked for
 * — "(A and B) or C" — and a form that offered more would mostly be a way to
 * build a query nobody can read back.
 *
 * A rule that is incomplete contributes nothing rather than matching
 * everything. Half-written conditions are normal while someone is still
 * editing, and the alternative is a campaign that quietly addresses the whole
 * database because a value was left blank.
 */
final class RuleCompiler
{
    public function apply(Builder $query, ?array $tree, AudienceSchema $schema): void
    {
        $this->applyGroup($query, $tree, $schema);
    }

    private function applyGroup(Builder $query, ?array $node, AudienceSchema $schema): void
    {
        $rules = $node['rules'] ?? null;

        if (! is_array($rules) || $rules === []) {
            return;
        }

        $any = ($node['match'] ?? 'all') === 'any';

        // Laravel drops a nested where that added no conditions, so an
        // incomplete rule leaves no trace in the SQL rather than an empty ().
        $query->where(function (Builder $group) use ($rules, $any, $schema) {
            foreach ($rules as $rule) {
                $branch = fn (Builder $q) => $this->applyRule($q, $rule, $schema);

                $any ? $group->orWhere($branch) : $group->where($branch);
            }
        });
    }

    private function applyRule(Builder $query, mixed $rule, AudienceSchema $schema): void
    {
        if (! is_array($rule)) {
            return;
        }

        $type = $rule['type'] ?? 'condition';
        $data = $rule['data'] ?? $rule;

        if ($type === 'group') {
            $this->applyGroup($query, $data, $schema);

            return;
        }

        $this->applyCondition($query, $data, $schema);
    }

    private function applyCondition(Builder $query, array $data, AudienceSchema $schema): void
    {
        $field = $schema->field((string) ($data['field'] ?? ''));

        if (! $field) {
            return;
        }

        $operator = (string) ($data['operator'] ?? '');

        if (! array_key_exists($operator, $field->operators())) {
            return;
        }

        $value = $this->valueFor($field, $operator, $data);

        if ($value === null) {
            return;
        }

        if ($field->constrain) {
            ($field->constrain)($query, $operator, $value);

            return;
        }

        $this->applyColumnCondition($query, $field, $operator, $value);
    }

    private function applyColumnCondition(Builder $query, AudienceField $field, string $operator, mixed $value): void
    {
        $column = $field->column;

        if ($field->type === AudienceField::TYPE_NUMBER) {
            $query->where($column, $operator, $value);

            return;
        }

        match ($operator) {
            'after'  => $query->where($column, '>=', Carbon::parse($value)->startOfDay()),
            'before' => $query->where($column, '<', Carbon::parse($value)->startOfDay()),

            'in_last_days' => $query->where($column, '>=', now()->subDays((int) $value)),

            // A null date has never happened, so it has not happened recently.
            'not_in_last_days' => $query->where(
                fn (Builder $q) => $q->where($column, '<', now()->subDays((int) $value))->orWhereNull($column),
            ),

            default => null,
        };
    }

    /**
     * Each value type is stored under its own key, because the form swaps the
     * input for the chosen field and a single key would carry a stale value of
     * the wrong shape whenever someone changed their mind.
     */
    private function valueFor(AudienceField $field, string $operator, array $data): mixed
    {
        $value = match (true) {
            $field->type === AudienceField::TYPE_NUMBER  => $data['value_number'] ?? null,
            $field->type === AudienceField::TYPE_SELECT  => $data['value_select'] ?? null,
            $field->type === AudienceField::TYPE_BOOLEAN => $data['value_boolean'] ?? null,
            $field->type === AudienceField::TYPE_DATE    => str_contains($operator, 'last_days')
                ? ($data['value_days'] ?? null)
                : ($data['value_date'] ?? null),
            default => null,
        };

        if ($field->type === AudienceField::TYPE_BOOLEAN) {
            return $value === null || $value === '' ? null : filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (is_array($value)) {
            $value = array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));

            return $value === [] ? null : $value;
        }

        return $value === null || $value === '' ? null : $value;
    }
}
