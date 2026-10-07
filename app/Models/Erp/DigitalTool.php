<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DigitalTool extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    protected $table = 'digital_tools';

    protected $fillable = [
        'asset_type_id',
        'asset_category_id',
        'brand_id',
        'asset_supplier_id',
        'purchase_date',
    ];

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }
}
