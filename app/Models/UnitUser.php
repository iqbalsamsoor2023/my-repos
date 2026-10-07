<?php

namespace App\Models;

use App\Enums\UserFamily\Relationship;
use App\Events\UnitUserCreated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class UnitUser extends Pivot implements Auditable
{
    use \OwenIt\Auditing\Auditable, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'user_id',
        'is_owner',
        'mmb_id',
        'is_main_owner',
        'is_main_tenant',
        'relationship',
        'approval_status',
        'is_created_via_family',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => UnitUserCreated::class,
    ];

    /**
     * Get the user that owns the UnitUser.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the unit that owns the UnitUser.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the insuranceCompany that owns the User
     *
     * @return BelongsTo
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Get all of the committee for the UnitUser
     *
     * @return HasMany
     */
    public function committee(): HasMany
    {
        return $this->hasMany(Committee::class);
    }

    /**
     * Interact with the unit user relationship.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function relationship(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Relationship::tryFrom($value)?->label()
        );
    }

    /**
     * Interact with the user's profile picture url.
     *
     * @return Attribute
     */
    public function hasTenant(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->is_owner == 1) {
                    $hasTenant = UnitUser::where('unit_id', $this->unit_id)->where(function ($query) {
                        $query->where('is_owner', 0);
                        $query->where('is_main_tenant', 1);
                    })->get();
                }

                return isset($hasTenant) ? 1 : 0;
            },
        );
    }

    /**
     * Scope a query to include necessary LEFT JOINs and exclude soft-deleted related records.
     */
    public function scopeWithValidResidenceRelations($query)
    {
        return $query
            ->joinActiveResidenceRelations()
            ->whereNull('unit_user.deleted_at');
    }

    public function scopeJoinActiveResidenceRelations($query)
    {
        return $query
            ->join('users', 'unit_user.user_id', '=', 'users.id')
            ->join('units', 'unit_user.unit_id', '=', 'units.id')
            ->join('residences', 'units.residence_id', '=', 'residences.id')
            ->whereNull('users.deleted_at')
            ->whereNull('units.deleted_at')
            ->whereNull('residences.deleted_at');
    }
}
