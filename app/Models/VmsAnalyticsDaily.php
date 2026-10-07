<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VmsAnalyticsDaily extends Model
{
    protected $table = 'vms_analytics_daily';

    protected $guarded = [];

    protected $casts = [
        'summary_date'             => 'date',
        'visitors_in'              => 'integer',
        'visitors_out'             => 'integer',
        'visitors_remaining'       => 'integer',
        'visitors_overnight'       => 'integer',
        'drive_in'                 => 'integer',
        'walk_in'                  => 'integer',
        'prebook'                  => 'integer',
        'vehicle_car'              => 'integer',
        'vehicle_truck'            => 'integer',
        'vehicle_motorbike'        => 'integer',
        'vehicle_van'              => 'integer',
        'vehicle_taxi'             => 'integer',
        'vehicle_pickup'           => 'integer',
        'courier_count'            => 'integer',
        'food_delivery_count'      => 'integer',
        'purpose_breakdown'        => 'array',
        'parcel_courier_breakdown' => 'array',
        'food_delivery_breakdown'  => 'array',
        'synced_at'                => 'datetime',
    ];
}
