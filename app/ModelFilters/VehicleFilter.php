<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class VehicleFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    public function user($value)
    {
        return $this->where('user_id', $value);
    }

    public function unit($value)
    {
        return $this->where('unit_id', $value);
    }
}
