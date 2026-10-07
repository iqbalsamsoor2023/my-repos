<?php

namespace App\Services;

use App\Enums\Residence\Features;
use App\Enums\Residence\MoobanType;
use App\Enums\User\RoleType;
use App\Filament\Resources\Residences\ResidenceImport;
use App\Helpers\AutomationAccountGenerator;
use App\Helpers\ImportHelper;
use App\Models\Company;
use App\Models\Erp\ThailandSubDistrict;
use App\Models\Residence;
use App\Models\ResidenceFeature;
use App\Models\SubscriptionExpire;
use App\Models\User;
use App\Services\Concerns\BuildsImportResults;
use App\Support\SpreadsheetImportSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ResidenceImportService
{
    use BuildsImportResults;

    private const START_DATA_ROW = 3;

    private const MAX_IMPORT_ROWS = 50;

    private const ROW_PROCESSING_FAILURE = 'residence_import_row_processing_failure';

    private const REQUIRED_FIELDS = [
        'name',
        'mooban_type',
        'sub_type',
        'main_road',
        'completion_year',
        'latitude',
        'longitude',
        'subdistrict',
        'status',
        'internet_provider',
        'guard_house_entry',
        'entry_lane_type',
        'property_management_type',
        'property_management',
        'pm_expiry_date',
        'subscription_start',
        'subscription_end',
    ];

    /**
     * @return array{
     *     status: 'success'|'warning'|'danger',
     *     title: string,
     *     body: ?string,
     *     errors: array<int, array{row: int, column: string, message: string}>,
     *     imported_count: int
     * }
     */
    public function import(string $uploadedPath): array
    {
        $absolutePath = SpreadsheetImportSupport::uploadedAbsolutePath($uploadedPath);

        try {
            $rows = $this->readImportRows($absolutePath);
            $rowsForImport = $this->extractImportRows($rows);

            if (count($rowsForImport) === 0) {
                return $this->warningResult(__('app.the_import_not_enough_data'));
            }

            if (count($rowsForImport) > self::MAX_IMPORT_ROWS) {
                return $this->dangerResult(
                    __('app.upload_limit_exceeded'),
                    __('app.upload_limit_import_message')
                );
            }

            $validationErrors = $this->collectValidationErrors($rowsForImport);

            if ($validationErrors !== []) {
                return $this->warningResult(
                    __('app.some_rows_contain_errors'),
                    SpreadsheetImportSupport::formatRowErrors($validationErrors),
                    $validationErrors
                );
            }

            $processingErrors = [];
            $importedCount = 0;

            try {
                DB::transaction(function () use ($rowsForImport, &$processingErrors, &$importedCount): void {
                    foreach ($rowsForImport as $rowNumber => $row) {
                        try {
                            $this->processRow($row);
                            $importedCount++;
                        } catch (Throwable $exception) {
                            Log::error('Residence import row processing failed.', [
                                'row' => $rowNumber,
                                'message' => $exception->getMessage(),
                            ]);

                            $processingErrors[] = [
                                'row' => (int) $rowNumber,
                                'column' => '*',
                                'message' => __('app.failed_to_process_row'),
                            ];
                        }
                    }

                    if ($processingErrors !== []) {
                        throw new RuntimeException(self::ROW_PROCESSING_FAILURE);
                    }
                });
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() !== self::ROW_PROCESSING_FAILURE) {
                    throw $exception;
                }

                return $this->warningResult(
                    __('app.some_rows_contain_errors'),
                    SpreadsheetImportSupport::formatRowErrors($processingErrors),
                    $processingErrors
                );
            }

            return $this->successResult(__('app.import_success'), $importedCount);
        } catch (Throwable $exception) {
            Log::error('Residence import failed.', [
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
        return SpreadsheetImportSupport::importRows($absolutePath, new ResidenceImport);
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
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{row: int, column: string, message: string}>
     */
    protected function collectValidationErrors(array $rows): array
    {
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            foreach (self::REQUIRED_FIELDS as $field) {
                if (blank($row[$field] ?? null)) {
                    $errors[] = [
                        'row' => (int) $rowNumber,
                        'column' => $field,
                        'message' => __('validation.required', ['attribute' => __($field)]),
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function processRow(array $row): void
    {
        $residence = $this->storeResidence($row);

        $this->handleUserAccounts($residence);
        $this->handleSubscriptions($residence, $row);
        $this->handleFeatures($residence, $row);
        $this->createActivationModule($residence);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function storeResidence(array $row): Residence
    {
        $moobanType = MoobanType::tryFrom((int) ($row['mooban_type'] ?? 0));

        return Residence::create([
            'name' => $row['name'],
            'name_th' => $row['name_th'] ?? null,
            'mooban_type' => $moobanType?->value,
            'sub_type' => $row['sub_type'] ?? null,
            'main_road' => $row['main_road'] ?? null,
            'completion_year' => $row['completion_year'] ?? null,
            'full_address' => $row['full_address'] ?? null,
            'google_location_link' => $row['google_location_link'] ?? null,
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'subdistrict_id' => $this->resolveSubdistrictId($row['subdistrict'] ?? null),
            'residence_activation_status_id' => $row['status'],
            'internet_provider' => $row['internet_provider'] ?? null,
            'guard_house_entry_number' => $row['guard_house_entry'] ?? null,
            'guard_house_lane_type' => $row['entry_lane_type'] ?? null,
            'subscription_start_date' => ImportHelper::parseExcelDate($row['subscription_start'] ?? null),
            'subscription_end_date' => ImportHelper::parseExcelDate($row['subscription_end'] ?? null),
        ]);
    }

    protected function resolveSubdistrictId(mixed $subdistrict): ?int
    {
        if (blank($subdistrict)) {
            return null;
        }

        return ThailandSubDistrict::query()
            ->where(function ($query) use ($subdistrict): void {
                $query->where('name_in_thai', 'LIKE', '%'.$subdistrict.'%')
                    ->orWhere('name_in_english', 'LIKE', '%'.$subdistrict.'%');
            })
            ->value('id');
    }

    protected function handleUserAccounts(Residence $residence): void
    {
        $propertyAccount = AutomationAccountGenerator::generate($residence, RoleType::PM->value);
        $propertyUser = User::create(AutomationAccountGenerator::manageAccount($propertyAccount));

        AutomationAccountGenerator::attachRole($propertyUser, RoleType::PROPERTY_MANAGEMENT->value);

        $residence->property_management_user_id = $propertyUser->id;
        $residence->save();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function handleSubscriptions(Residence $residence, array $row): void
    {
        $subscriptions = [
            ['type' => 'Property Management', 'company' => 'property_management', 'expiry' => 'pm_expiry_date'],
            ['type' => 'Sgoc', 'company' => 'security_guard', 'expiry' => 'security_expiry_date'],
            ['type' => 'Fire Insurance', 'company' => 'insurance', 'expiry' => 'insurance_expiry_date'],
        ];

        foreach ($subscriptions as $subscription) {
            if (empty($row[$subscription['expiry']] ?? null)) {
                continue;
            }

            SubscriptionExpire::create([
                'type' => $subscription['type'],
                'company_id' => Company::query()
                    ->where('name', 'like', '%'.($row[$subscription['company']] ?? '').'%')
                    ->value('id'),
                'residence_id' => $residence->id,
                'expiry_date' => ImportHelper::parseExcelDate($row[$subscription['expiry']]),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function handleFeatures(Residence $residence, array $row): void
    {
        if (empty($row['has_receptionist'] ?? null)) {
            return;
        }

        ResidenceFeature::create([
            'residence_id' => $residence->id,
            'feature_id' => Features::RECEPTION_MANAGEMENT->value,
            'is_active' => (int) $row['has_receptionist'],
        ]);
    }

    protected function createActivationModule(Residence $residence): void
    {
        $features = [
            Features::PROPERTY_MANAGEMENT,
            Features::SECURITY_MANAGEMENT,
            Features::INBOX,
            Features::VISITOR,
            Features::CLAIM,
            Features::PARCEL,
            Features::BOOKING,
            Features::DEVELOPER,
            Features::CONTACT,
            Features::BILLING,
            Features::PARKING_FEE_BASIC,
        ];

        $data = collect($features)
            ->map(fn ($feature): array => [
                'residence_id' => $residence->id,
                'feature_id' => $feature->value,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        ResidenceFeature::query()->insert($data);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function isEmptyImportRow(array $row): bool
    {
        return ImportHelper::isEmptyRow($row);
    }
}
