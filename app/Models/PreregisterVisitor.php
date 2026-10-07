<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreregisterVisitor extends BaseModel
{
    use Filterable, HasFactory;

    protected $fillable = [
        'visitor_id',
        'visitor_code',
        'arrival_type',
        'vehicle_type',
        'visitor_purpose',
        'vehicle_plate_no',
        'is_multiple_entry',
        'validity_start_date',
        'validity_end_date',
        'unit_id',
        'user_id',
        'is_qr_code_expired',
        'passenger_count',
        'company_name',
        'remark',
        'vehicle_info',
    ];

    protected $appends = [
        'qr_code_url',
    ];

    protected $casts = [
        'vehicle_info' => 'array',
    ];

    /**
     * Get the visitor that owns the PreregisterVisitor.
     *
     * @return BelongsTo
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * Get the unit that owns the PreregisterVisitor.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user that owns the PreregisterVisitor.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function qrCodeUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => route('visitors.qr', ['visitor_code' => $this->attributes['visitor_code']]),
        );
    }

    public function idType(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value == 4) ? 1 : $value,
        );
    }
}
