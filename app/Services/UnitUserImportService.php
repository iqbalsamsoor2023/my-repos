<?php

namespace App\Services;

use App\Enums\UnitUser\ApprovalStatusType;
use App\Enums\User\RoleType;
use App\Filament\Resources\UnitUsers\ResidentImport;
use App\Helpers\AutomationAccountGenerator;
use App\Mail\UserRegistered as MailUserRegistered;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Models\User;
use App\Services\Concerns\BuildsImportResults;
use App\Support\SpreadsheetImportSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class UnitUserImportService
{
    use BuildsImportResults;

    private const START_DATA_ROW = 3;

    /**
     * @return array{
     *     status: 'success'|'warning'|'danger',
     *     title: string,
     *     body: ?string,
     *     errors: array<int, array{row: int, column: string, message: string}>,
     *     imported_count: int
     * }
     */
    public function import(int $residenceId, string $uploadedPath): array
    {
        $absolutePath = SpreadsheetImportSupport::uploadedAbsolutePath($uploadedPath);

        try {
            $rows = $this->readImportRows($absolutePath);
            $rowsForImport = $this->extractImportRows($rows);

            if ($rowsForImport === []) {
                return $this->warningResult(__('app.the_import_not_enough_data'));
            }

            $errors = $this->collectValidationErrors($rowsForImport, $residenceId);

            if ($errors !== []) {
                return $this->warningResult(
                    __('app.some_rows_contain_errors'),
                    SpreadsheetImportSupport::formatRowErrors($errors),
                    $errors
                );
            }

            $importedCount = DB::transaction(
                fn (): int => $this->persistRows($rowsForImport, $residenceId)
            );

            return $this->successResult(__('app.import_success'), $importedCount);
        } catch (Throwable $exception) {
            Log::error('Unit user import failed.', [
                'residence_id' => $residenceId,
                'message' => $exception->getMessage(),
            ]);

            return $this->dangerResult(__('app.import_failed'), $exception->getMessage());
        } finally {
            SpreadsheetImportSupport::deleteFileIfExists($absolutePath);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readImportRows(string $absolutePath): array
    {
        return SpreadsheetImportSupport::importRows($absolutePath, new ResidentImport);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function extractImportRows(array $rows): array
    {
        return SpreadsheetImportSupport::extractDataRows(
            $rows,
            self::START_DATA_ROW,
            fn (array $row): bool => $this->isEmptyImportRow($row),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function isEmptyImportRow(array $row): bool
    {
        return blank($row['user'] ?? null)
            && blank($row['unit_number'] ?? null)
            && blank($row['email'] ?? null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{row: int, column: string, message: string}>
     */
    protected function collectValidationErrors(array $rows, int $residenceId): array
    {
        $errors = [];
        $seenUnitEmail = [];
        $plannedMainOwnerByUnit = [];
        $plannedMainTenantByUnit = [];
        $unitCache = [];
        $userCache = [];
        $hasMainOwnerCache = [];
        $hasMainTenantCache = [];

        foreach ($rows as $rowNumber => $row) {
            $unitNumber = trim((string) ($row['unit_number'] ?? ''));
            $userName = trim((string) ($row['user'] ?? ''));
            $email = $this->sanitizeEmail($row['email'] ?? null);
            $isOwner = $this->normalizeBoolean($row['is_owner'] ?? null);
            $isMainTenant = $this->normalizeBoolean($row['is_main_tenant'] ?? null) ?? false;

            if ($userName === '') {
                $this->addError($errors, (int) $rowNumber, 'user', __('validation.required', ['attribute' => __('user.user')]));
            }

            if ($unitNumber === '') {
                $this->addError($errors, (int) $rowNumber, 'unit_number', __('validation.required', ['attribute' => __('unit.unit_number')]));
            }

            if (blank($row['email'] ?? null)) {
                $this->addError($errors, (int) $rowNumber, 'email', __('validation.required', ['attribute' => __('app.email')]));
            } elseif (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addError($errors, (int) $rowNumber, 'email', __('validation.email', ['attribute' => __('app.email')]));
            }

            if ($isOwner === null) {
                $this->addError($errors, (int) $rowNumber, 'is_owner', __('validation.required', ['attribute' => __('user.is_owner')]));
            }

            if ($unitNumber === '') {
                continue;
            }

            $unit = $this->resolveUnitByNumber($residenceId, $unitNumber, $unitCache);

            if (! $unit) {
                $this->addError($errors, (int) $rowNumber, 'unit_number', __('unit.import_message.unit_not_found'));

                continue;
            }

            $unitId = (int) $unit->id;

            if ($isOwner === true) {
                if (($plannedMainOwnerByUnit[$unitId] ?? false) || $this->hasExistingMainOwner($unitId, $hasMainOwnerCache)) {
                    $this->addError($errors, (int) $rowNumber, 'is_owner', __('unit.import_message.unit_already_has_main_owner'));
                }

                $plannedMainOwnerByUnit[$unitId] = true;
            }

            if ($isMainTenant) {
                if (($plannedMainTenantByUnit[$unitId] ?? false) || $this->hasExistingMainTenant($unitId, $hasMainTenantCache)) {
                    $this->addError($errors, (int) $rowNumber, 'is_main_tenant', __('unit.import_message.unit_already_has_main_tenant'));
                }

                $plannedMainTenantByUnit[$unitId] = true;
            }

            if (! $email) {
                continue;
            }

            $rowKey = $unitId.'|'.strtolower($email);
            if (isset($seenUnitEmail[$rowKey])) {
                $this->addError($errors, (int) $rowNumber, 'email', __('unit.import_message.user_already_exists_in_unit'));

                continue;
            }

            $seenUnitEmail[$rowKey] = true;

            $existingUser = $this->findUserByEmail($email, $userCache);
            if ($existingUser && $this->unitUserExists($unitId, (int) $existingUser->id)) {
                $this->addError($errors, (int) $rowNumber, 'email', __('unit.import_message.user_already_exists_in_unit'));
            }
        }

        return $errors;
    }

    /**
     * @param  array<int, array{row: int, column: string, message: string}>  $errors
     */
    protected function addError(array &$errors, int $rowNumber, string $column, string $message): void
    {
        $errors[] = [
            'row' => $rowNumber,
            'column' => $column,
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, Unit>  $cache
     */
    protected function resolveUnitByNumber(int $residenceId, string $unitNumber, array &$cache): ?Unit
    {
        $cacheKey = $residenceId.'|'.$unitNumber;

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = Unit::query()
            ->where('residence_id', $residenceId)
            ->where('unit_number', $unitNumber)
            ->first();

        return $cache[$cacheKey];
    }

    /**
     * @param  array<string, User|null>  $cache
     */
    protected function findUserByEmail(string $email, array &$cache): ?User
    {
        $normalizedEmail = strtolower($email);

        if (array_key_exists($normalizedEmail, $cache)) {
            return $cache[$normalizedEmail];
        }

        $cache[$normalizedEmail] = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail], 'and')
            ->first();

        return $cache[$normalizedEmail];
    }

    /**
     * @param  array<int, bool>  $cache
     */
    protected function hasExistingMainOwner(int $unitId, array &$cache): bool
    {
        if (array_key_exists($unitId, $cache)) {
            return $cache[$unitId];
        }

        $cache[$unitId] = UnitUser::query()
            ->where('unit_id', $unitId)
            ->where('is_main_owner', true)
            ->exists();

        return $cache[$unitId];
    }

    /**
     * @param  array<int, bool>  $cache
     */
    protected function hasExistingMainTenant(int $unitId, array &$cache): bool
    {
        if (array_key_exists($unitId, $cache)) {
            return $cache[$unitId];
        }

        $cache[$unitId] = UnitUser::query()
            ->where('unit_id', $unitId)
            ->where('is_main_tenant', true)
            ->exists();

        return $cache[$unitId];
    }

    protected function unitUserExists(int $unitId, int $userId): bool
    {
        return UnitUser::query()
            ->where('unit_id', $unitId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function persistRows(array $rows, int $residenceId): int
    {
        $importedCount = 0;
        $mailUsers = [];
        $unitCache = [];
        $userCache = [];

        foreach ($rows as $row) {
            $unitNumber = trim((string) ($row['unit_number'] ?? ''));
            $email = $this->sanitizeEmail($row['email'] ?? null);

            if ($unitNumber === '' || ! $email) {
                continue;
            }

            $unit = $this->resolveUnitByNumber($residenceId, $unitNumber, $unitCache);

            if (! $unit) {
                continue;
            }

            $user = $this->findUserByEmail($email, $userCache);

            if (! $user) {
                $user = $this->createResidentUser($row, $email);
                $userCache[strtolower($email)] = $user;
            }

            $isOwner = $this->normalizeBoolean($row['is_owner'] ?? null) ?? false;
            $isMainTenant = $this->normalizeBoolean($row['is_main_tenant'] ?? null) ?? false;

            UnitUser::query()->create([
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'is_owner' => $isOwner,
                'is_main_owner' => $isOwner,
                'is_main_tenant' => $isMainTenant,
                'relationship' => $row['relationship'] ?? null,
                'approval_status' => ApprovalStatusType::APPROVED->value,
            ]);

            $mailUsers[$user->id] = $user;
            $importedCount++;
        }

        if ($mailUsers !== []) {
            DB::afterCommit(fn () => $this->sendRegistrationEmails($mailUsers));
        }

        return $importedCount;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function createResidentUser(array $row, string $email): User
    {
        $user = User::query()->create([
            'country_id' => 1,
            'name' => (string) ($row['user'] ?? ''),
            'email' => $email,
            'password' => bcrypt($email),
            'phone_no' => $row['phone_no'] ?? '000-000000',
            'email_verified_at' => null,
        ]);

        AutomationAccountGenerator::attachRole($user, RoleType::UNIT_TENANT->value);

        return $user;
    }

    /**
     * @param  array<int, User>  $users
     */
    protected function sendRegistrationEmails(array $users): void
    {
        foreach ($users as $user) {
            if (blank($user->email)) {
                continue;
            }

            try {
                Mail::to($user->email)->send(new MailUserRegistered($user));
            } catch (Throwable $exception) {
                Log::warning('Failed sending registration email during resident import.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    protected function sanitizeEmail(mixed $email): ?string
    {
        if (blank($email)) {
            return null;
        }

        $cleaned = preg_replace('/[^A-Za-z0-9@._-]/', '', (string) $email);

        if (! is_string($cleaned)) {
            return null;
        }

        $cleaned = trim(strtolower($cleaned));

        return $cleaned === '' ? null : $cleaned;
    }

    protected function normalizeBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'owner', 'main_owner'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'tenant', 'main_tenant'], true)) {
            return false;
        }

        return null;
    }
}
