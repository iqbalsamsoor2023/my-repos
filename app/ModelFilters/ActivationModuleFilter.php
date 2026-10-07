<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class ActivationModuleFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    public function residence($value)
    {
        return $this->where('residence_id', $value);
    }

    public function module($value)
    {
        return $this->where('module', $value);
    }
}
