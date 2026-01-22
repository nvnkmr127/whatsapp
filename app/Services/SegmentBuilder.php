<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SegmentBuilder
{
    /**
     * Build query from segment rules.
     */
    public static function buildQuery(array $rules, ?int $teamId = null): Builder
    {
        $query = Contact::query();

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        static::applyRules($query, $rules);

        return $query;
    }

    /**
     * Apply rules to query.
     */
    protected static function applyRules(Builder $query, array $rules): void
    {
        $operator = $rules['operator'] ?? 'AND';
        $conditions = $rules['conditions'] ?? [];

        if (empty($conditions)) {
            return;
        }

        $query->where(function ($q) use ($conditions, $operator) {
            foreach ($conditions as $condition) {
                // Nested rules
                if (isset($condition['operator']) && isset($condition['conditions'])) {
                    static::applyRules($q, $condition);
                    continue;
                }

                // Single condition
                $method = strtolower($operator) === 'or' ? 'orWhere' : 'where';
                static::applyCondition($q, $condition, $method);
            }
        });
    }

    /**
     * Apply single condition to query.
     */
    protected static function applyCondition(Builder $query, array $condition, string $method = 'where'): void
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'] ?? null;

        switch ($operator) {
            case '=':
            case '!=':
            case '>':
            case '>=':
            case '<':
            case '<=':
                $query->$method($field, $operator, $value);
                break;

            case 'IN':
                $query->$method(function ($q) use ($field, $value) {
                    $q->whereIn($field, (array) $value);
                });
                break;

            case 'NOT_IN':
                $query->$method(function ($q) use ($field, $value) {
                    $q->whereNotIn($field, (array) $value);
                });
                break;

            case 'CONTAINS':
                $query->$method($field, 'LIKE', "%{$value}%");
                break;

            case 'STARTS_WITH':
                $query->$method($field, 'LIKE', "{$value}%");
                break;

            case 'ENDS_WITH':
                $query->$method($field, 'LIKE', "%{$value}");
                break;

            case 'BETWEEN':
                $query->$method(function ($q) use ($field, $value) {
                    $q->whereBetween($field, (array) $value);
                });
                break;

            case 'DAYS_AGO':
                $date = now()->subDays((int) $value);
                $query->$method($field, '<=', $date);
                break;

            case 'IS_NULL':
                $query->$method(function ($q) use ($field) {
                    $q->whereNull($field);
                });
                break;

            case 'IS_NOT_NULL':
                $query->$method(function ($q) use ($field) {
                    $q->whereNotNull($field);
                });
                break;

            case 'HAS_TAG':
                $query->$method(function ($q) use ($value) {
                    $q->whereHas('tags', function ($tagQuery) use ($value) {
                        $tagQuery->where('name', $value);
                    });
                });
                break;

            case 'HAS_ANY_TAG':
                $query->$method(function ($q) use ($value) {
                    $q->whereHas('tags', function ($tagQuery) use ($value) {
                        $tagQuery->whereIn('name', (array) $value);
                    });
                });
                break;

            case 'HAS_ALL_TAGS':
                foreach ((array) $value as $tag) {
                    $query->$method(function ($q) use ($tag) {
                        $q->whereHas('tags', function ($tagQuery) use ($tag) {
                            $tagQuery->where('name', $tag);
                        });
                    });
                }
                break;

            case 'CUSTOM_EQUALS':
                // Extract path from field (e.g., "custom_attributes.city")
                $path = str_replace('custom_attributes.', '', $field);
                $query->$method(function ($q) use ($path, $value) {
                    $q->whereJsonContains('custom_attributes->' . $path, $value);
                });
                break;

            case 'CUSTOM_EXISTS':
                $path = str_replace('custom_attributes.', '', $field);
                $query->$method(function ($q) use ($path) {
                    $q->whereNotNull('custom_attributes->' . $path);
                });
                break;
        }
    }

    /**
     * Check if a contact matches segment rules.
     */
    public static function contactMatches(Contact $contact, array $rules): bool
    {
        $query = static::buildQuery($rules, $contact->team_id);
        $query->where('id', $contact->id);

        return $query->exists();
    }

    /**
     * Estimate segment size without executing full query.
     */
    public static function estimateSize(array $rules, int $teamId): int
    {
        $query = static::buildQuery($rules, $teamId);

        return $query->count();
    }
}
