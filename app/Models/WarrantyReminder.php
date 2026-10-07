<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyReminder extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'user_id',
        'amenity_id',
        'stop_reminder_at',
    ];

    /**
     * Get the amenity that owns the Warranty Reminder.
     *
     * @return BelongsTo
     */
    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenity::class);
    }

    /**
     * Get the user that owns the Warranty Reminder.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the unit that owns the Warranty Reminder.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
