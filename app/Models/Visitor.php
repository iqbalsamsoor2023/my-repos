<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Visitor extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'contact_no',
        'id_type',
        'id_number',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the visitor logs that owns the Visitor.
     *
     * @return HasMany
     */
    public function visitorLogs(): HasMany
    {
        return $this->hasMany(VisitorLog::class);
    }

    /**
     * Get the pre-register visitors that owns the Visitor.
     *
     * @return HasMany
     */
    public function preRegisterVisitors(): HasMany
    {
        return $this->hasMany(PreregisterVisitor::class);
    }

    /**
     * Get the blacklisted visitor that owns the Visitor.
     *
     * @return HasMany
     */
    public function blacklistedVisitor(): HasMany
    {
        return $this->hasMany(BlacklistedVisitor::class);
    }
}
