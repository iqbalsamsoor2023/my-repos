<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitHouseholdItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function householdItem()
    {
        return $this->belongsTo(HouseholdItem::class, 'household_item_id');
    }
}
