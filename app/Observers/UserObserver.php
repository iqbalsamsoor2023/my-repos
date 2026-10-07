<?php

namespace App\Observers;

use App\Jobs\SyncUnitUserStatsViewJob;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    public function saved(User $user): void
    {
        if (! $user->wasChanged(['email', 'email_verified_at', 'country_id', 'gender', 'date_of_birth', 'deleted_at'])) {
            return;
        }

        $this->dispatchResidenceSyncs($user);
    }

    public function deleted(User $user): void
    {
        $this->dispatchResidenceSyncs($user);

        Audit::create([
            'user_type' => User::class,
            'user_id' => $user->deleted_by ?? auth()->id(),
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'event' => 'deleted',
            'old_values' => $user->getOriginal(),
            'new_values' => [],
            'url' => request()?->fullUrl(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'tags' => null,
        ]);
    }

    public function restored(User $user): void
    {
        $this->dispatchResidenceSyncs($user);
    }

    protected function dispatchResidenceSyncs(User $user): void
    {
        DB::table('unit_user as uu')
            ->join('units as unt', 'unt.id', '=', 'uu.unit_id')
            ->where('uu.user_id', $user->id)
            ->distinct()
            ->pluck('unt.residence_id')
            ->each(fn ($residenceId) => SyncUnitUserStatsViewJob::dispatch((int) $residenceId)->afterCommit());
    }
}
