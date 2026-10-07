<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ThailandProvince extends Model
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
    ];

    protected function nameInEnglish(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'th' ? ($this->attributes['name_in_thai'] ?? '') : ($this->attributes['name_in_english'] ?? ''),
        );
    }

    /**
     * Get the ThailandDistrict for the ThailandProvince.
     *
     * @return HasMany
     */
    public function districts(): HasMany
    {
        return $this->hasMany(ThailandDistrict::class, 'province_id', 'id');
    }
}
