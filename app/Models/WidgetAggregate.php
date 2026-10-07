<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WidgetAggregate extends Model
{
    protected $table = 'widget_aggregates';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'computed_at' => 'datetime',
    ];

    /**
     * Get cached aggregate by module and key. Returns null if stale or missing.
     */
    public static function getCached(string $key, int $maxAgeSeconds = 120, string $module = 'residence'): ?array
    {
        $record = static::where('module', $module)->where('key', $key)->first();

        if (! $record || ! $record->computed_at) {
            return null;
        }

        if ($record->computed_at->diffInSeconds(now()) > $maxAgeSeconds) {
            return null;
        }

        return $record->payload;
    }

    /**
     * Store/update aggregate data by module and key.
     */
    public static function putCache(string $key, array $payload, string $module = 'residence'): void
    {
        static::updateOrCreate(
            ['module' => $module, 'key' => $key],
            [
                'payload' => $payload,
                'computed_at' => now(),
            ]
        );
    }
}
