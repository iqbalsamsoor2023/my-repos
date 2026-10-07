<?php

namespace App\Models\Erp;

use App\Enums\DigitalTool\DtaDigitalToolStatusEnum;
use App\Models\Sgoc\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DtaDigitalTool extends Model
{
    protected $connection = 'mmbcnerp';

    protected $table = 'dta_digital_tool';

    protected $fillable = [
        'digital_tool_allocation_id',
        'digital_tool_id',
        'sku_center_id',
        'sim_id',
        'user_platform',
        'user_id',
        'service_start_date',
        'service_end_date',
        'status'
    ];

    protected $casts = [
        'status' => DtaDigitalToolStatusEnum::class,
    ];

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    public function sgocUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function digitalToolAllocation(): BelongsTo
    {
        return $this->belongsTo(DigitalToolAllocation::class, 'digital_tool_allocation_id');
    }

    public function digitalTool(): BelongsTo
    {
        return $this->belongsTo(DigitalTool::class, 'digital_tool_id');
    }

    public function skuCenter(): BelongsTo
    {
        return $this->belongsTo(SkuCenter::class, 'sku_center_id');
    }

    public function sim(): BelongsTo
    {
        return $this->belongsTo(SimCard::class, 'sim_id');
    }
}
