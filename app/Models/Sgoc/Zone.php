<?php

namespace App\Models\Sgoc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Zone extends Model
{
    protected $connection = 'sgoc';

    protected $table = 'zones';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }
}
