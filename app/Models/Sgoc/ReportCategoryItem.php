<?php

namespace App\Models\Sgoc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReportCategoryItem extends Model
{
    protected $connection = 'sgoc';

    protected $table = 'report_category_items';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    /**
     * Get the report category that created the Residence Report Item
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reportCategory()
    {
        return $this->belongsTo(ReportCategory::class);
    }
}
