<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitingArrangementArchive extends Model
{
    use SoftDeletes;

    protected $table = 'visiting_arrangements_archive';

    protected $fillable = [
        'visitor_log_id',
        'residence_id',
        'unit_id',
        'user_id',
        'status',
        'estamp_by',
        'estamp_by_type',
        'feedback_remark',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function visitorLog(): BelongsTo
    {
        return $this->belongsTo(VisitorLogArchive::class, 'visitor_log_id');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estampBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estamp_by', 'id');
    }

    public function visitorRemarks(): HasMany
    {
        return $this->hasMany(VisitorRemark::class, 'residence_id', 'residence_id');
    }
}
