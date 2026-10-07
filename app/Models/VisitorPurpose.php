<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitorPurpose extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'residence_id',
        'purpose',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the residence that owns the VisitorPurpose.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }
}
