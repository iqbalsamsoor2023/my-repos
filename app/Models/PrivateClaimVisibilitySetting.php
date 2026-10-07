<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateClaimVisibilitySetting extends Model
{
    protected $fillable = [
        'private_claim_item_id',
        'residence_id',
        'is_enabled',
    ];

    public function privateClaimItem(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimItem::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }
}
