<?php

namespace App\Models\Sgoc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReportCategory extends Model
{
    protected $connection = 'sgoc';

    protected $table = 'report_categories';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }
}
