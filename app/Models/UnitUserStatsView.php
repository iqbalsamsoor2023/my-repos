<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitUserStatsView extends Model
{
    protected $table = 'unit_user_stats_view';

    protected $primaryKey = 'unit_user_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'is_owner' => 'boolean',
        'is_main_owner' => 'boolean',
        'is_main_tenant' => 'boolean',
        'is_email_verified' => 'boolean',
        'synced_at' => 'datetime',
        'unit_user_created_at' => 'datetime',
        'unit_user_updated_at' => 'datetime',
        'unit_user_deleted_at' => 'datetime',
        'user_date_of_birth' => 'date',
    ];
}
