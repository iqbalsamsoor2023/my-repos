<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ErpLocationTagType extends Model
{
    protected $connection = 'mmbcnerp';

    protected $table = 'location_tag_types';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    /**
     * Get all of the locationTags for the ErpLocationTagType
     *
     * @return HasMany
     */
    public function locationTags(): HasMany
    {
        return $this->hasMany(ErpLocationTag::class);
    }
}
