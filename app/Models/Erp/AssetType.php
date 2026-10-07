<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssetType extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    protected $fillable = ['name'];

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }
}
