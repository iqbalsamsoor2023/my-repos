<?php

namespace App\Models;

use App\Enums\Residence\CommitteeRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Committee extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'user_id',
        'residence_id',
        'unit_id',
        'term_start',
        'term_end',
    ];

    protected $casts = [
        'role' => CommitteeRole::class,
        'term_start' => 'date',
        'term_end' => 'date',
    ];

    /**
     * Get the user that owns the Committee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the unit that owns the Committee.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the residence that owns the Committee.
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Scope: Active committees (no term_end or future end date).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('term_end')
                ->orWhere('term_end', '>=', now());
        });
    }

    /**
     * Scope: Archived (term ended already).
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('term_end')
            ->where('term_end', '<', now());
    }

    /**
     * Scope: Filter by residence.
     */
    public function scopeForResidence(Builder $query, int $residenceId): Builder
    {
        return $query->where('residence_id', $residenceId);
    }

    /**
     * Scope: Filter by role.
     */
    public function scopeByRole(Builder $query, CommitteeRole|int $role): Builder
    {
        $roleValue = $role instanceof CommitteeRole ? $role->value : $role;

        return $query->where('role', $roleValue);
    }

    /**
     * Check if the committee is currently active.
     */
    public function isActive(): bool
    {
        return is_null($this->term_end) || $this->term_end >= now()->startOfDay();
    }

    /**
     * Check if the committee is archived.
     */
    public function isArchived(): bool
    {
        return ! $this->isActive();
    }
}
