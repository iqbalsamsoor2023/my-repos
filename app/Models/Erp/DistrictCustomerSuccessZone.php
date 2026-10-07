<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

class DistrictCustomerSuccessZone extends Pivot
{
    protected $connection = 'mmbcnerp';

    protected $table = 'district_customer_success_zone';

    protected $fillable = [
        'customer_success_zone_id',
        'thailand_district_id',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    public function customerSuccessZone(): BelongsTo
    {
        return $this->belongsTo(CustomerSuccessZone::class, 'customer_success_zone_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(ThailandDistrict::class, 'thailand_district_id');
    }
}
