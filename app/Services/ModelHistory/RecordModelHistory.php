<?php

namespace App\Services\ModelHistory;

use App\Models\ModelHistory;
use Illuminate\Support\Facades\Auth;

trait RecordModelHistory
{
    public $lastOriginal = [];

    protected $recordableHistories = [];

    protected static $modelHistoryEnabled = true;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootRecordModelHistory()
    {
        static::created(function ($instance) {
            if (! static::$modelHistoryEnabled) {
                return;
            }

            $values = [];
            $historyRecordables = $instance->getRecordableModelHistoryColumns();
            $attributes = $instance->attributesToArray();

            $timestampColumns = [$instance->getCreatedAtColumn(), $instance->getUpdatedAtColumn()];

            if (method_exists($instance, 'getDeletedAtColumn')) {
                $timestampColumns[] = $instance->getDeletedAtColumn();
            }

            if (count($historyRecordables)) {
                foreach ($attributes as $key => $value) {
                    if (! in_array($key, $historyRecordables)) {
                        continue;
                    }

                    $values[$key] = $value;
                }
            } else {
                foreach ($attributes as $key => $value) {
                    $values[$key] = $value;
                }
            }

            $instance->createModelHistory([
                'event' => 'Created',
                'old_values' => null,
                'new_values' => $values,
                'user_id' => Auth::check() ? Auth::id() : null,
            ]);
        });

        static::updating(function ($instance) {
            if (! static::$modelHistoryEnabled) {
                return;
            }

            $lastOriginal = [];

            $historyRecordables = $instance->getRecordableModelHistoryColumns();
            $hasHistoryRecordables = count($historyRecordables);

            $timestampColumns = [$instance->getCreatedAtColumn(), $instance->getUpdatedAtColumn()];

            if (method_exists($instance, 'getDeletedAtColumn')) {
                $timestampColumns[] = $instance->getDeletedAtColumn();
            }
            foreach ($instance->getOriginal() as $key => $value) {
                if (is_array($hasHistoryRecordables) && count($hasHistoryRecordables) && ! in_array($key, $historyRecordables)) {
                    continue;
                }

                $lastOriginal[$key] = $value;
            }

            $instance->lastOriginal = $lastOriginal;
        });

        static::updated(function ($instance) {
            if (! static::$modelHistoryEnabled) {
                return;
            }

            $changes = $instance->getChanges();
            $values = [];

            $historyRecordables = $instance->getRecordableModelHistoryColumns();

            if (count($historyRecordables)) {
                foreach ($changes as $key => $value) {
                    if (in_array($key, $historyRecordables)) {
                        $values[$key] = $value;
                    }
                }
            } else {
                $values = $changes;
            }

            $values[$instance->getUpdatedAtColumn()] = $instance->{$instance->getUpdatedAtColumn()};

            $instance->createModelHistory([
                'event' => 'Updated',
                'old_values' => $instance->getOriginal(),
                'new_values' => $values,
                'user_id' => Auth::check() ? Auth::id() : null,
            ]);

            $instance->lastOriginal = [];
        });

        static::deleted(function ($instance) {
            if (! static::$modelHistoryEnabled) {
                return;
            }

            if (! method_exists($instance, 'isForceDeleting') || $instance->isForceDeleting()) {
                $instance->histories()->delete();
            } else {
                $instance->createModelHistory([
                    'event' => 'Deleted',
                    'old_values' => null,
                    'new_values' => null,
                    'user_id' => Auth::check() ? Auth::id() : null,
                ]);
            }
        });
    }

    public function histories()
    {
        return $this->morphMany(ModelHistory::class, 'modelable', 'modelable_type', 'modelable_id', 'id');
    }

    public function getRecordableModelHistoryColumns()
    {
        return $this->recordableHistories ?: [];
    }

    public static function enableModelHistory()
    {
        static::$modelHistoryEnabled = true;
    }

    public static function disableModelHistory()
    {
        static::$modelHistoryEnabled = false;
    }

    public function createModelHistory(array $attributes)
    {
        if (! property_exists($this, 'ignoreEvents') || ! ($this->ignoreEvents && count($this->ignoreEvents)) || ! in_array($attributes['event'], $this->ignoreEvents)) {
            return $this->histories()->create(array_merge($attributes, [
                'modelable_type' => $this->getMorphClass(),
                'modelable_id' => $this->getKey(),
            ]));
        }
    }
}
