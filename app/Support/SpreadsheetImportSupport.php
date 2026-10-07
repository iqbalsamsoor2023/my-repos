<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class SpreadsheetImportSupport
{
    public static function uploadedAbsolutePath(string $uploadedPath): string
    {
        return storage_path('app/'.$uploadedPath);
    }

    public static function deleteFileIfExists(string $absolutePath): void
    {
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function importRows(string $absolutePath, object $importer): array
    {
        Excel::import($importer, $absolutePath);

        if (! method_exists($importer, 'getArray')) {
            throw new RuntimeException('Spreadsheet importer must expose getArray().');
        }

        $rows = $importer->getArray();

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): bool  $isEmptyRow
     * @return array<int, array<string, mixed>>
     */
    public static function extractDataRows(array $rows, int $startDataRow, callable $isEmptyRow, bool $preserveRowNumbers = true): array
    {
        $rowsForImport = [];

        foreach ($rows as $index => $row) {
            if ($index < $startDataRow) {
                continue;
            }

            $normalizedRow = (array) $row;

            if ($isEmptyRow($normalizedRow)) {
                continue;
            }

            if ($preserveRowNumbers) {
                $rowsForImport[$index + 1] = $normalizedRow;

                continue;
            }

            $rowsForImport[] = $normalizedRow;
        }

        return $rowsForImport;
    }

    /**
     * @param  array<int, array{row: int, column: string, message: string}>  $errors
     */
    public static function formatRowErrors(array $errors): string
    {
        return collect($errors)
            ->map(fn (array $error): string => "Row {$error['row']} - Column '{$error['column']}': {$error['message']}")
            ->implode("\n");
    }
}
