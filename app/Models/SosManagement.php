<?php

namespace App\Models;

use App\Enums\SosManagement\Status;
use App\Enums\SosManagement\UserActionRequest;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosManagement extends Model
{
    use HasFactory;

    protected $table = 'sos_managements';

    protected $fillable = [
        'created_by_id',
        'unit_id',
        'accepted_by_id',
        'user_action_request',
        'longitude',
        'latitude',
        'status',
        'remark',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user that creates SosManagement.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the unit that creates SosManagement.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user that creates SosManagement.
     *
     * @return BelongsTo
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(SgocUser::class, 'accepted_by_id', 'id');
    }

    /**
     * Interact with the user's action request.
     *
     * @return Attribute
     */
    protected function userActionRequest(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value == UserActionRequest::CALL_AMBULANCE->value) {
                    return 'Call Ambulance';
                } elseif ($value == UserActionRequest::CALL_POLICE->value) {
                    return 'Call Police';
                }
            }
        );
    }

    /**
     * Interact with the sosm's status.
     *
     * @return Attribute
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value == Status::PENDING->value) {
                    return 'Pending';
                } elseif ($value == Status::IN_PROGRESS->value) {
                    return 'In Progress';
                } elseif ($value == Status::CANCELLED->value) {
                    return 'Cancelled';
                } elseif ($value == Status::COMPLETED->value) {
                    return 'Completed';
                }
            }
        );
    }
}
