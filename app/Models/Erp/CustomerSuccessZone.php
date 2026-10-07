<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CustomerSuccessZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $fillable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(
            ThailandDistrict::class,
            'district_customer_success_zone',
            'customer_success_zone_id',
            'thailand_district_id'
        )->using(DistrictCustomerSuccessZone::class);
    }
}
