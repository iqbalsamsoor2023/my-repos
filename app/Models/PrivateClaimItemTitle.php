<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivateClaimItemTitle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'private_claim_item_id',
        'private_claim_item_option_id',
        'option_name',
        'option_name_th',
    ];

    public function privateClaimItem(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimItem::class);
    }

    public function privateClaimItemOption(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimItemOption::class);
    }

    public function getDisplayNameAttribute()
    {
        return app()->getLocale() === 'th'
            ? $this->option_name_th
            : $this->option_name;
    }
}
