<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitStatsView extends Model
{
    protected $table = 'units_stats_view';

    protected $primaryKey = 'unit_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'is_signed_up' => 'boolean',
        'is_registered_owner' => 'boolean',
        'is_registered_tenant' => 'boolean',
        'synced_at' => 'datetime',
        'unit_created_at' => 'datetime',
        'unit_updated_at' => 'datetime',
    ];
}
