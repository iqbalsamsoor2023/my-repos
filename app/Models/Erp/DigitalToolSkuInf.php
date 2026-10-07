<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DigitalToolSkuInf extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    protected $table = 'digital_tool_sku_inf';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    /**
     * Get the infDigitalTool that owns the DigitalToolSkuInf
     *
     * @return BelongsTo
     */
    public function infDigitalTool(): BelongsTo
    {
        return $this->belongsTo(INFDigitalTool::class, 'inf_digital_tool_id');
    }
}
