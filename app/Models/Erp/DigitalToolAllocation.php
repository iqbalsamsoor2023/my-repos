<?php

namespace App\Models\Erp;

use App\Models\Residence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class DigitalToolAllocation extends Model
{
    protected $connection = 'mmbcnerp';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    /**
     * Get the residence that owns the DigitalToolAllocation
     *
     * @return BelongsTo
     */
    public function residence(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(Residence::class, 'residence_id', 'id');
    }

    /**
     * Get the DTA Digital Tool that owns the DigitalToolAllocation
     *
     * @return HasMany
     */
    public function dtaDigitalTools(): HasMany
    {
        return $this->hasMany(DtaDigitalTool::class, 'digital_tool_allocation_id');
    }
}
