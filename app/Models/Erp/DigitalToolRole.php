<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DigitalToolRole extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    protected $table = 'digital_tool_roles';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    public function digitalToolRoleCategory(): BelongsTo
    {
        return $this->belongsTo(DigitalToolRoleCategory::class);
    }
}
