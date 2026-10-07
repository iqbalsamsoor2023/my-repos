<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SkuCenter extends Model
{
    use SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'sku_centers';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    public function digitalToolRole()
    {
        return $this->belongsTo(DigitalToolRole::class);
    }
}
