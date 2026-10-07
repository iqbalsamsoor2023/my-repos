<?php

namespace App\Models\Sgoc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShiftType extends Model
{
    protected $connection = 'sgoc';

    protected $table = 'shift_types';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $hidden = ['created_at', 'updated_at'];

    public function getLocalizedShiftNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'th' ? $this->shift_name_th : $this->shift_name;
    }
}
