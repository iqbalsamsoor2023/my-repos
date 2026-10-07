<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calculation extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parking_id',
        'vehicle_type',
        'is_stamp',
        'free_parking_minutes',
        'rate_per_hour',
        'chartered_duration',
        'chartered_price',
        'penalty',
        'created_at',
        'updated_at',
    ];

    /**
     * Convert free_parking_minutes to hour and minutes.
     *
     * @param  string  $value
     * @return string
     */
    // public function getFreeParkingMinutesAttribute($value)
    // {
    //     $hours = floor($value / 60);
    //     $minutes = $value % 60;
    //     return collect([
    //         'hours' => $hours,
    //         'minutes' => $minutes,
    //     ]);
    // }
    /**
     * Convert chartered duration to hour and minutes.
     *
     * @param  string  $value
     * @return string
     */
    // public function getCharteredDurationAttribute($value)
    // {
    //     $hours = floor($value / 60);
    //     $minutes = $value % 60;
    //     return collect([
    //         'hours' => $hours,
    //         'minutes' => $minutes,
    //     ]);
    // }
    /**
     * Interact with the calculation's free parking minutes.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    // public function freeParkingMinutes(): Attribute
    // {
    //     return Attribute::make(
    //         set: function ($value) {
    //             $hours = Carbon::parse($value)->format('H');
    //             $hours = (int) $hours * 60;
    //             $minutes = Carbon::parse($value)->format('i');
    //             $totalMinutes = $hours + $minutes;
    //             return $totalMinutes;
    //         }
    //     );
    // }
    /**
     * Interact with the calculation's chartered duration.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    // public function charteredDuration(): Attribute
    // {
    //     return Attribute::make(
    //         set: function ($value) {
    //             $hours = Carbon::parse($value)->format('H');
    //             $hours = (int) $hours * 60;
    //             $minutes = Carbon::parse($value)->format('i');
    //             $totalMinutes = $hours + $minutes;
    //             return $totalMinutes;
    //         }
    //     );
    // }
    /**
     * Get the parking that owns the Calculation.
     *
     * @return BelongsTo
     */
    public function parking(): BelongsTo
    {
        return $this->belongsTo(Parking::class);
    }
}
