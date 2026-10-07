<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Audit as AuditContract;

class Audit extends Model implements AuditContract
{
    use \OwenIt\Auditing\Audit;

    protected $table = 'audits';

    protected $guarded = [];

    protected $fillable = [
        'user_type',
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    protected function oldValues(): Attribute
    {
        return Attribute::make(
            set: fn($value) => json_encode($value),
        );
    }

    protected function newValues(): Attribute
    {
        return Attribute::make(
            set: fn($value) => json_encode($value),
        );
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function userModel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
