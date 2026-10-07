<?php

namespace App\Models;

use Throwable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use function Sentry\captureException;
use Illuminate\Database\Eloquent\Model;

class ModelHistory extends Model
{
    protected $table = 'model_histories';

    protected $fillable = [
        'user_id',
        'event',
        'modelable_type',
        'modelable_id',
        'old_values',
        'new_values',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'changes',
    ];

    public function setJsonValueData($attribute, $value)
    {
        $newValue = null;

        try {
            if ($value !== null) {
                $newValue = json_encode($value);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $newValue = null;
                }
            }
        } catch (Throwable $ex) {
            captureException($ex);
        }

        $this->attributes[$attribute] = $newValue;
    }

    public function getJsonValueData($attribute)
    {
        if (isset($this->attributes[$attribute])) {
            try {
                return json_decode($this->attributes[$attribute], true);
            } catch (Throwable $ex) {
                captureException($ex);
            }
        }

        return null;
    }

    public function setOldValuesAttribute($value)
    {
        $this->setJsonValueData('old_values', $value);
    }

    public function getOldValuesAttribute()
    {
        return $this->getJsonValueData('old_values');
    }

    public function setNewValuesAttribute($value)
    {
        $this->setJsonValueData('new_values', $value);
    }

    public function getNewValuesAttribute()
    {
        return $this->getJsonValueData('new_values');
    }

    public function getChangesAttribute()
    {
        $changes = [];

        $oldValues = $this->getOldValuesAttribute();

        if ($oldValues) {
            foreach ($oldValues as $key => $value) {
                if (! isset($changes[$key])) {
                    $changes[$key] = ['from' => null, 'to' => null];
                }

                $changes[$key]['from'] = $value;
            }
        }

        $newValue = $this->getNewValuesAttribute();

        if ($newValue) {
            foreach ($newValue as $key => $value) {
                if (! isset($changes[$key])) {
                    $changes[$key] = ['from' => null, 'to' => null];
                }

                $changes[$key]['to'] = $value;
            }
        }

        if (count($changes)) {
            return $changes;
        }

        return null;
    }

    /**
     * Get user.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the owning model.
     */
    public function modelable()
    {
        return $this->morphTo();
    }
}
