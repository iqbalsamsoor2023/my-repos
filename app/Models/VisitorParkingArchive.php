<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitorParkingArchive extends Model
{
    use SoftDeletes;

    protected $table = 'visitor_parkings_archive';

    protected $fillable = [
        'visitor_log_id',
        'calculation_id',
        'discount_value',
        'amount_to_pay',
        'amount_paid',
        'is_penalty',
        'is_stamp',
        'calculation_records',
        'vehicle_province',
        'vehicle_brand',
        'vehicle_color',
        'vehicle_model',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'calculation_records' => AsCollection::class,
    ];

    public function visitorLog(): BelongsTo
    {
        return $this->belongsTo(VisitorLogArchive::class, 'visitor_log_id');
    }

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(Calculation::class);
    }
}
