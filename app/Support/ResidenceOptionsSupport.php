<?php

namespace App\Support;

use App\Models\BpoSoftwareSupplier;
use App\Models\Residence;
use App\Models\ResidenceActivationStatus;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class ResidenceOptionsSupport
{
    private const STATUS_OPTIONS_KEY = 'residence:options:activation-statuses:v1';

    private const BPO_SUPPLIER_OPTIONS_KEY_PREFIX = 'residence:options:bpo-suppliers';

    private const SECURITY_GUARD_COUNT_OPTIONS_KEY = 'residence:options:security-guard-counts:v1';

    private const VISIBLE_RESIDENCE_OPTIONS_KEY_PREFIX = 'residence:options:visible-residences';

    public static function activationStatusOptions(): array
    {
        return self::cacheRepository()->remember(self::STATUS_OPTIONS_KEY, now()->addHours(6), function (): array {
            return ResidenceActivationStatus::query()
                ->orderBy('id', 'asc')
                ->pluck('status', 'id')
                ->toArray();
        });
    }

    public static function bpoSupplierOptions(int|string|null $category = null): array
    {
        $cacheKey = self::BPO_SUPPLIER_OPTIONS_KEY_PREFIX.':'.($category ?? 'all').':v1';

        return self::cacheRepository()->remember($cacheKey, now()->addHours(6), function () use ($category): array {
            $query = BpoSoftwareSupplier::query()->orderBy('name', 'asc');

            if ($category !== null) {
                $query->whereIn('category', [$category], 'and', false);
            }

            return $query->pluck('name', 'id')->toArray();
        });
    }

    public static function securityGuardCountOptions(): array
    {
        return self::cacheRepository()->remember(self::SECURITY_GUARD_COUNT_OPTIONS_KEY, now()->addHours(6), function (): array {
            return Residence::query()
                ->whereNotNull('security_guard_count', 'and')
                ->select('security_guard_count')
                ->distinct()
                ->orderBy('security_guard_count', 'asc')
                ->pluck('security_guard_count', 'security_guard_count')
                ->mapWithKeys(static fn ($count): array => [(string) $count => (string) $count])
                ->toArray();
        });
    }

    public static function visibleResidenceOptions(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        $cacheKey = self::VISIBLE_RESIDENCE_OPTIONS_KEY_PREFIX.':'.$user->getAuthIdentifier().':v1';

        return self::cacheRepository()->remember($cacheKey, now()->addMinutes(10), function () use ($user): array {
            return Residence::query()
                ->visibleToUser($user)
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'name_th'])
                ->mapWithKeys(static fn (Residence $residence): array => [
                    $residence->id => "{$residence->name} ({$residence->name_th})",
                ])
                ->all();
        });
    }

    private static function cacheRepository(): Repository
    {
        $stores = (array) config('cache.stores', []);
        $storeName = array_key_exists('redis', $stores)
            ? 'redis'
            : (string) config('cache.default', 'file');

        return Cache::store($storeName);
    }
}
