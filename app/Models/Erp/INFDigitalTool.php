<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class INFDigitalTool extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    protected $table = 'inf_digital_tools';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }
}
