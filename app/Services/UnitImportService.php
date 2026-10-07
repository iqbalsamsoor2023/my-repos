<?php

namespace App\Services;

use App\Enums\Residence\MoobanType;
use App\Enums\Residence\SubType;
use App\Enums\Unit\HouseType;
use App\Filament\Resources\Units\UnitImport;
use App\Models\Residence;
use App\Models\Unit;
use App\Support\SpreadsheetImportSupport;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UnitImportService
{
    private const START_DATA_ROW = 3;

    private const FACTORY_LIKE_TYPES = [
        MoobanType::FACTORY->value,
        MoobanType::DEMO_FOR_SG->value,
    ];

    /**
     * @return array{errors: array<int, array{row: int, column: string, message: string}>, imported_count: int}
     */
    public function import(int $residenceId, string $uploadedPath): array
    {
        $absolutePath = SpreadsheetImportSupport::uploadedAbsolutePath($uploadedPath);

        try {
            $rowsForImport = $this->extractImportRows($this->readImportRows($absolutePath));
            $residence = Residence::query()->select(['id', 'mooban_type'])->findOrFail($residenceId);
            $moobanType = MoobanType::tryFrom((int) $residence->mooban_type);

            if (! $moobanType) {
                throw new RuntimeException('Invalid mooban type for the selected residence.');
            }

            $errors = $this->collectValidationErrors($rowsForImport, $moobanType);

            if (! empty($errors)) {
                return [
                    'errors' => $errors,
                    'imported_count' => 0,
                ];
            }

            $importedCount = DB::transaction(
                fn (): int => $this->persistRows($rowsForImport, $residenceId, $moobanType)
            );

            return [
                'errors' => [],
                'imported_count' => $importedCount,
            ];
        } finally {
            SpreadsheetImportSupport::deleteFileIfExists($absolutePath);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readImportRows(string $absolutePath): array
    {
        return SpreadsheetImportSupport::importRows($absolutePath, new UnitImport);
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
    protected function collectValidationErrors(array $rows, MoobanType $moobanType): array
    {
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            $this->validateRow((array) $row, (int) $rowNumber, $moobanType, $errors);
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, array{row: int, column: string, message: string}>  $errors
     */
    protected function validateRow(array $row, int $rowNumber, MoobanType $moobanType, array &$errors): void
    {
        if (empty($row['unit_number'])) {
            $this->addError($errors, $rowNumber, 'unit_number', __('unit.import_message.unit_number_is_required'));
        }

        if (empty($row['street'])) {
            $this->addError($errors, $rowNumber, 'street', __('unit.import_message.street_is_required'));
        }

        if (empty($row['status'])) {
            $this->addError($errors, $rowNumber, 'status', __('unit.import_message.status_is_required'));
        }

        $unitSize = $row['unit_size'] ?? null;
        if (! $this->isFactoryLikeType($moobanType)) {
            if ($unitSize === null || $unitSize === '') {
                $this->addError($errors, $rowNumber, 'unit_size', __('unit.import_message.unit_size_is_required'));
            } elseif (! is_numeric($unitSize)) {
                $this->addError($errors, $rowNumber, 'unit_size', __('unit.import_message.unit_size_must_be_number'));
            } elseif ((float) $unitSize < 0) {
                $this->addError($errors, $rowNumber, 'unit_size', __('unit.import_message.unit_size_must_be_zero_or_positive'));
            }
        } elseif ($unitSize !== null && $unitSize !== '') {
            if (! is_numeric($unitSize)) {
                $this->addError($errors, $rowNumber, 'unit_size', __('unit.import_message.unit_size_must_be_number'));
            } elseif ((float) $unitSize < 0) {
                $this->addError($errors, $rowNumber, 'unit_size', __('unit.import_message.unit_size_must_be_zero_or_positive'));
            }
        }

        $landSize = $row['land_size'] ?? null;
        if ($landSize !== null && $landSize !== '') {
            if (! is_numeric($landSize)) {
                $this->addError($errors, $rowNumber, 'land_size', __('unit.import_message.land_size_must_be_number'));
            } elseif ((float) $landSize < 0) {
                $this->addError($errors, $rowNumber, 'land_size', __('unit.import_message.land_size_must_be_zero_or_positive'));
            }
        }

        $excelSubType = (int) ($row['property_type'] ?? 0);
        if (! $this->isFactoryLikeType($moobanType) && $excelSubType === 0) {
            $this->addError($errors, $rowNumber, 'property_type', __('unit.import_message.property_type_is_required_for_this_mooban_type'));
        }

        $subTypeEnum = SubType::tryFrom($excelSubType);
        if ($subTypeEnum && ! in_array($subTypeEnum, $moobanType->allowedSubTypes(), true)) {
            $this->addError(
                $errors,
                $rowNumber,
                'property_type',
                __('unit.import_message.selected_sub_type_does_not_belong_to_mooban_type', [
                    'moobanType' => $moobanType->name,
                ])
            );
        }

        $isCondo = in_array($excelSubType, [SubType::CONDO_LOW_RISE->value, SubType::CONDO_HIGH_RISE->value], true);
        if ($isCondo && ! empty($landSize)) {
            $this->addError($errors, $rowNumber, 'land_size', __('unit.import_message.condo_do_not_have_land_size'));
        }

        if (! empty($row['house_type'])) {
            $houseTypeEnum = HouseType::tryFrom((int) $row['house_type']);
            $subTypeFromRow = SubType::tryFrom($excelSubType);

            if ($houseTypeEnum && $subTypeFromRow && $houseTypeEnum->subType()->value !== $subTypeFromRow->value) {
                $this->addError($errors, $rowNumber, 'house_type', __('unit.import_message.house_type_does_not_match_the_selected_sub_type'));
            }
        }
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
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function persistRows(array $rows, int $residenceId, MoobanType $moobanType): int
    {
        $importedCount = 0;

        foreach ($rows as $row) {
            $row = (array) $row;

            $excelSubType = (int) ($row['property_type'] ?? 0);
            $subType = $this->isFactoryLikeType($moobanType) ? null : $excelSubType;
            $houseType = ! empty($row['house_type']) ? (int) $row['house_type'] : null;
            $isCondo = in_array($excelSubType, [SubType::CONDO_LOW_RISE->value, SubType::CONDO_HIGH_RISE->value], true);
            $landSize = $isCondo ? null : ($row['land_size'] ?? null);

            Unit::updateOrCreate(
                [
                    'residence_id' => $residenceId,
                    'unit_number' => $row['unit_number'],
                ],
                [
                    'block' => $row['block'],
                    'street' => $row['street'],
                    'unit_size' => (float) ($row['unit_size'] ?? 0),
                    'land_size' => $landSize,
                    'sub_type' => $subType,
                    'house_type' => $houseType,
                    'floor' => $row['floor'],
                    'status' => (int) $row['status'],
                    'myseevr_link' => $row['myseevr_link'],
                    'move_in_at' => $this->parseMoveInDate($row['move_at'] ?? null),
                ]
            );

            $importedCount++;
        }

        return $importedCount;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function isEmptyImportRow(array $row): bool
    {
        return empty($row['unit_number'])
            && empty($row['street'])
            && empty($row['status'])
            && empty($row['unit_size']);
    }

    protected function isFactoryLikeType(MoobanType $moobanType): bool
    {
        return in_array($moobanType->value, self::FACTORY_LIKE_TYPES, true);
    }

    protected function parseMoveInDate(mixed $moveInAt): ?string
    {
        if (blank($moveInAt)) {
            return null;
        }

        $timestamp = strtotime((string) $moveInAt);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }
}
