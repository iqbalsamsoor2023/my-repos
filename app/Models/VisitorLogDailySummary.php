<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorLogDailySummary extends Model
{
    protected $table = 'visitor_logs_daily_summary';

    protected $primaryKey = 'summary_date';

    public $incrementing = false;

    protected $keyType = 'date';

    protected $fillable = [
        'summary_date',
        'visitors_in',
        'visitors_out',
        'visitors_remaining',
        'visitors_overnight',
        'drive_in',
        'walk_in',
        'prebook',
        'vehicle_car',
        'vehicle_truck',
        'vehicle_motorbike',
        'vehicle_van',
        'vehicle_taxi',
        'vehicle_pickup',
        'purpose_breakdown',
        'parcel_courier_breakdown',
        'food_delivery_breakdown',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'visitors_in' => 'integer',
        'visitors_out' => 'integer',
        'visitors_remaining' => 'integer',
        'visitors_overnight' => 'integer',
        'drive_in' => 'integer',
        'walk_in' => 'integer',
        'prebook' => 'integer',
        'vehicle_car' => 'integer',
        'vehicle_truck' => 'integer',
        'vehicle_motorbike' => 'integer',
        'vehicle_van' => 'integer',
        'vehicle_taxi' => 'integer',
        'vehicle_pickup' => 'integer',
        'purpose_breakdown' => 'array',
        'parcel_courier_breakdown' => 'array',
        'food_delivery_breakdown' => 'array',
    ];

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('summary_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get current month summary
     */
    public function scopeCurrentMonth($query)
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();

        return $query->whereBetween('summary_date', [$monthStart, $monthEnd]);
    }

    /**
     * Get total visitors for the summary
     */
    public function getTotalVisitorsAttribute(): int
    {
        return $this->drive_in + $this->walk_in + $this->prebook;
    }

    /**
     * Get total vehicles for the summary
     */
    public function getTotalVehiclesAttribute(): int
    {
        return $this->vehicle_car + $this->vehicle_truck +
               $this->vehicle_motorbike + $this->vehicle_van +
               $this->vehicle_taxi + $this->vehicle_pickup;
    }
}
