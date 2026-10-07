<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BusinessEntity extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'business_category_id',
        'name',
        'name_th',
        'province_id'
    ];
}
