<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitorCard extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'residence_id',
        'visitor_card_no',
        'is_custom',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the residence that owns the VisitorCard.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }
}
