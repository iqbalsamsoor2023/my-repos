<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'report_date',
        'module',
        'format',
        'filepath',
        'expire_at',
    ];

    /**
     * Get the residence that owns the Report.
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }
}
