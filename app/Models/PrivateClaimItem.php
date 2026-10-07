<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PrivateClaimItem extends Model
{
    protected $fillable = [
        'private_claim_category_id',
        'name',
        'name_th',
    ];

    public function privateClaimItemTitles(): HasMany
    {
        return $this->hasMany(PrivateClaimItemTitle::class);
    }

    public function privateClaimCategory(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimCategory::class);
    }

    public function visibilitySettings()
    {
        return $this->hasMany(PrivateClaimVisibilitySetting::class, 'private_claim_item_id');
    }

    public function privateClaimItemSetting(): HasOne
    {
        return $this->hasOne(PrivateClaimItemSetting::class);
    }

    public function getDisplayNameAttribute()
    {
        return app()->getLocale() === 'th'
            ? $this->name_th
            : $this->name;
    }
}
