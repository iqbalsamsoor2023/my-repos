<?php

namespace App\Filament\Resources\Visitors\Widgets\Concerns;

trait HasDateFilter
{
    protected function filterDates(): array
    {
        return [
            $this->tableFilters['created_at']['created_from'] ?? null,
            $this->tableFilters['created_at']['created_until'] ?? null,
        ];
    }

    protected function resolvedDates(): array
    {
        [$from, $until] = $this->filterDates();

        return [
            $from ?? now()->startOfMonth()->toDateString(),
            $until ?? now()->toDateString(),
        ];
    }

    protected function periodHeading(string $label): string
    {
        [$from, $until] = $this->filterDates();

        if ($from || $until) {
            return "{$label} ({$from} → {$until})";
        }

        return $label.' ('.now()->format('F Y').')';
    }

    protected function hasDateFilter(): bool
    {
        [$from, $until] = $this->filterDates();

        return (bool) ($from || $until);
    }

    protected function hasNonDateFilters(): bool
    {
        $filters = $this->tableFilters ?? [];
        unset($filters['created_at']);

        return $this->hasAnyFilterValue($filters);
    }

    protected function hasAnyFilterValue(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->hasAnyFilterValue($item)) {
                    return true;
                }
            }

            return false;
        }

        return ! blank($value);
    }

    protected function usesFilteredTableQuery(): bool
    {
        return $this->hasNonDateFilters() && $this->hasDateFilter();
    }
}
