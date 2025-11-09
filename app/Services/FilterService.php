<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class FilterService
{
    /**
     * Get all available due date filters for frontend
     *
     * @return array
     */
    public function getDueDateFilters(): array
    {
        $filters = config('filters.due_date', []);

        // Return only the data needed by frontend (without query_builder closure)
        return array_map(function ($filter) {
            return [
                'label' => $filter['label'],
                'value' => $filter['value'],
                'description' => $filter['description'] ?? null,
            ];
        }, $filters);
    }

    /**
     * Get valid due date filter values for validation
     *
     * @return array
     */
    public function getValidDueDateFilterValues(): array
    {
        $filters = config('filters.due_date', []);
        return array_column($filters, 'value');
    }

    /**
     * Get validation rule for due date filter
     *
     * @return string
     */
    public function getDueDateFilterValidationRule(): string
    {
        $validValues = $this->getValidDueDateFilterValues();
        return 'nullable|string|in:' . implode(',', $validValues);
    }

    /**
     * Apply due date filter to query
     *
     * @param Builder $query
     * @param string $filterValue
     * @return Builder
     */
    public function applyDueDateFilter(Builder $query, string $filterValue): Builder
    {
        $filters = config('filters.due_date', []);
        $filterConfig = collect($filters)->firstWhere('value', $filterValue);

        if (!$filterConfig || !isset($filterConfig['query_builder'])) {
            return $query;
        }

        $now = now();
        return call_user_func($filterConfig['query_builder'], $query, $now);
    }

    /**
     * Check if a filter value is valid
     *
     * @param string $value
     * @return bool
     */
    public function isValidDueDateFilter(string $value): bool
    {
        return in_array($value, $this->getValidDueDateFilterValues());
    }
}

