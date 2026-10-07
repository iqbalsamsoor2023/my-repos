<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleStatsView extends Model
{
    protected $table = 'vehicle_stats_view';

    protected $primaryKey = 'vehicle_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'vehicle_created_at' => 'datetime',
        'vehicle_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}
