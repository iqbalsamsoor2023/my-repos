<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class ThailandSubDistrict extends Model
{
    use HasFactory;

    protected $connection = 'mmbcnerp';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'id',
        'code',
        'name_in_english',
        'name_in_thai',
        'latitude',
        'longitude',
        'district_id',
        'zip_code',
    ];

    /**
     * Get the ThailandDistrict that owns the ThailandSubDistrict.
     *
     * @return BelongsTo
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(ThailandDistrict::class);
    }

    protected function nameInEnglish(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'th' ? ($this->attributes['name_in_thai'] ?? '') : ($this->attributes['name_in_english'] ?? ''),
        );
    }
}
