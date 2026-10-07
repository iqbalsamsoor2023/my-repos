<?php

namespace App\Filament\Resources\Committees\Pages;

use App\Filament\Resources\Committees\CommitteeResource;
use App\Enums\Residence\CommitteeRole;
use App\Models\Committee;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCommittee extends CreateRecord
{
    protected static string $resource = CommitteeResource::class;

    public function getTitle(): string
    {
        return __('committee.create_committee');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_ids'] = collect($data['user_ids'] ?? [])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($data['user_ids'])) {
            throw ValidationException::withMessages([
                'user_ids' => __('validation.required', ['attribute' => __('app.resident')]),
            ]);
        }

        $this->validateRoleAllowsSelectionCount($data);

        // Convert month/year selects to date fields
        if (isset($data['term_start_month']) && isset($data['term_start_year'])) {
            $data['term_start'] = sprintf(
                '%04d-%02d-01',
                $data['term_start_year'],
                $data['term_start_month']
            );
            unset($data['term_start_month'], $data['term_start_year']);
        }

        if (isset($data['term_end_month']) && isset($data['term_end_year'])) {
            $data['term_end'] = sprintf(
                '%04d-%02d-01',
                $data['term_end_year'],
                $data['term_end_month']
            );
            unset($data['term_end_month'], $data['term_end_year']);
        } else {
            $data['term_end'] = null;
            unset($data['term_end_month'], $data['term_end_year']);
        }

        $this->validateNoOverlappingTerm($data);
        $this->validateUniqueAssignments($data);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $userIds = collect($data['user_ids'] ?? []);
        $baseData = Arr::except($data, ['user_ids']);

        return DB::transaction(function () use ($userIds, $baseData): Committee {
            $primary = Committee::create([
                ...$baseData,
                'user_id' => (int) $userIds->first(),
            ]);

            $remainingIds = $userIds->slice(1)->values();

            if ($remainingIds->isNotEmpty()) {
                $timestamp = now();

                Committee::insert(
                    $remainingIds
                        ->map(fn ($userId) => [
                            ...$baseData,
                            'user_id' => (int) $userId,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ])
                        ->all()
                );
            }

            return $primary;
        });
    }

    /**
     * Validate that no overlapping term exists for unique-role positions.
     */
    protected function validateNoOverlappingTerm(array $data): void
    {
        $role = $this->resolveRoleOrFail($data['role'] ?? null);

        // Committee Member can have multiple active users in the same term.
        if ($role === CommitteeRole::MEMBER || $role === CommitteeRole::TREASURER) {
            return;
        }

        $start = $data['term_start'];
        $end = $data['term_end'] ?? now()->addYears(100)->toDateString();

        $overlapping = Committee::query()
            ->where('residence_id', $data['residence_id'])
            ->where('role', $role->value)
            ->where(function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start, $end) {
                    // Check if the new term overlaps with existing terms
                    $q->where('term_start', '<=', $end)
                        ->where(function ($sub) use ($start) {
                            $sub->whereNull('term_end')
                                ->orWhere('term_end', '>=', $start);
                        });
                });
            })
            ->exists();

        if ($overlapping) {
            Notification::make()
                ->danger()
                ->title(__('committee.error_overlapping_title'))
                ->body(__('committee.error_overlapping_body'))
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'role' => __('committee.error_overlapping_role'),
                'term_start' => __('committee.error_overlapping_term'),
            ]);
        }
    }

    /**
     * Non-member committee roles must remain single assignee per create action.
     */
    protected function validateRoleAllowsSelectionCount(array $data): void
    {
        $role = $this->resolveRoleOrFail($data['role'] ?? null);
        $selectedCount = count($data['user_ids'] ?? []);

        if (($role !== CommitteeRole::MEMBER && $role !== CommitteeRole::TREASURER) && $selectedCount > 1) {
            Notification::make()
                ->danger()
                ->title(__('committee.error_overlapping_title'))
                ->body(__('committee.error_single_seat_multi_select'))
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'user_ids' => __('committee.error_single_seat_multi_select'),
            ]);
        }
    }

    /**
     * Resolve committee role safely and return a validation error instead of a server error.
     */
    protected function resolveRoleOrFail(mixed $roleValue): CommitteeRole
    {
        if ($roleValue instanceof CommitteeRole) {
            return $roleValue;
        }

        $role = null;

        if (is_int($roleValue) || (is_string($roleValue) && ctype_digit($roleValue))) {
            $role = CommitteeRole::tryFrom((int) $roleValue);
        }

        if ($role) {
            return $role;
        }

        throw ValidationException::withMessages([
            'role' => __('committee.error_invalid_role'),
        ]);
    }

    /**
     * Validate uniqueness for the same user + residence + role + term_start.
     */
    protected function validateUniqueAssignments(array $data): void
    {
        $role = $this->resolveRoleOrFail($data['role'] ?? null);

        $userIds = collect($data['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $duplicatedUserIds = Committee::query()
            ->where('residence_id', (int) $data['residence_id'])
            ->where('role', $role->value)
            ->whereDate('term_start', $data['term_start'])
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($duplicatedUserIds->isEmpty()) {
            return;
        }

        $duplicatedNames = User::query()
            ->whereIn('id', $duplicatedUserIds)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        Notification::make()
            ->danger()
            ->title(__('committee.error_overlapping_title'))
            ->body(__('committee.error_duplicate_assignment', ['users' => implode(', ', $duplicatedNames)]))
            ->persistent()
            ->send();

        throw ValidationException::withMessages([
            'user_ids' => __('committee.error_duplicate_assignment', ['users' => implode(', ', $duplicatedNames)]),
        ]);
    }
}
