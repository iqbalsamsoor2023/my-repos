<?php

namespace App\Models;

use App\Enums\UserFamily\Relationship;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportDataResident extends Pivot
{
    use SoftDeletes;

    protected $table = 'unit_user';

    protected $fillable = [
        'unit_id',
        'user_id',
        'is_owner',
        'mmb_id',
        'is_main_owner',
        'is_main_tenant',
        'relationship',
        'approval_status',
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
     * Interact with the unit user relationship.
     *
     * @param  string  $value
     * @return Attribute
     */
    public function relationship(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                switch ($value) {
                    case Relationship::HUSBAND->value:
                        return 'Husband';
                        break;
                    case Relationship::WIFE->value:
                        return 'Wife';
                        break;
                    case Relationship::FATHER->value:
                        return 'Father';
                        break;
                    case Relationship::MOTHER->value:
                        return 'Mother';
                        break;
                    case Relationship::BROTHER->value:
                        return 'Brother';
                        break;
                    case Relationship::SISTER->value:
                        return 'Sister';
                        break;
                    case Relationship::SON->value:
                        return 'Son';
                        break;
                    case Relationship::DAUGHTER->value:
                        return 'Daughter';
                        break;
                    case Relationship::RELATIVE->value:
                        return 'Relative';
                        break;
                    case Relationship::CO_HOME->value:
                        return 'Co Home';
                        break;
                    default:
                        return null;
                }
            }
        );
    }
}
