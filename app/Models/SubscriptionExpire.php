<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionExpire extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'company_id',
        'residence_id',
        'expiry_date',
    ];

    /**
     * Get the residence that owns the SubscriptionExpire.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class, 'residence_id', 'id');
    }
}
