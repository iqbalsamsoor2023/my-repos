<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorRemark extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'remark',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the residence that owns the VisitorRemark.
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }
}
