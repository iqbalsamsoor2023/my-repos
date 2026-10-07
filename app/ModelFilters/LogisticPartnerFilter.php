<?php

namespace App\ModelFilters;

use App\Enums\LogisticPartner\CategoryType;
use EloquentFilter\ModelFilter;

class LogisticPartnerFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    public function category($value)
    {
        // If value is already a valid enum case (int), just use it
        if (is_numeric($value) && CategoryType::tryFrom((int) $value)) {
            return $this->where('category', (int) $value);
        }

        // If value is a string (from old app), match it to enum label
        $mapped = collect(CategoryType::cases())
            ->first(fn ($case) => strtolower($case->getLabel()) === strtolower($value));

        if ($mapped) {
            return $this->where('category', $mapped->value);
        }

        // If not matchable, no filter applied
        return $this;
    }
}
