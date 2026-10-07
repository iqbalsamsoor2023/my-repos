<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidencePrivateClaimItem extends Model
{
    protected $table = 'residence_private_claim_item';
    
    protected $fillable = [
        'residence_id',
        'private_claim_item_id',
        'is_active',
    ];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function privateClaimItem(): BelongsTo
    {
        return $this->belongsTo(PrivateClaimItem::class);
    }
}
