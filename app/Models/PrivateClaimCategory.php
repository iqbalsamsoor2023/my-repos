<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivateClaimCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'name_th',
    ];

    public function privateClaimItems()
    {
        return $this->hasMany(PrivateClaimItem::class, 'private_claim_category_id');
    }

    public function getDisplayNameAttribute()
    {
        return app()->getLocale() === 'th'
            ? $this->name_th
            : $this->name;
    }
}
