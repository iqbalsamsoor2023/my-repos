<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParcelStatsView extends Model
{
    protected $table = 'parcel_stats_view';

    protected $primaryKey = 'parcel_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'parcel_created_at' => 'datetime',
        'parcel_updated_at' => 'datetime',
        'pickup_time' => 'datetime',
        'synced_at' => 'datetime',
    ];
}
