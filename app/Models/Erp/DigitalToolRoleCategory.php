<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DigitalToolRoleCategory extends Model
{
    use SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'digital_tool_role_categories';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }
}
