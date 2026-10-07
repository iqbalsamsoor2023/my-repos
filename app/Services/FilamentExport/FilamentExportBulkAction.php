<?php

namespace App\Services\FilamentExport;

use AlperenErsoy\FilamentExport\Actions\Concerns\CanDisableAdditionalColumns;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanDisableFileName;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanDisableFileNamePrefix;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanDisableFilterColumns;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanDisableFormats;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanDisablePreview;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanDisableTableColumns;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanDownloadDirect;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanFormatStates;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanHaveExtraColumns;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanHaveExtraViewData;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanModifyWriters;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanRefreshTable;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanShowHiddenColumns;
use AlperenErsoy\FilamentExport\Actions\Concerns\CanUseSnappy;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasAdditionalColumnsField;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasCsvDelimiter;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasDefaultFormat;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasDefaultPageOrientation;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasExportModelActions;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasFileName;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasFileNameField;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasFilterColumnsField;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasFormatField;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasPageOrientationField;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasPaginator;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasTimeFormat;
use AlperenErsoy\FilamentExport\Actions\Concerns\HasUniqueActionId;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction as ActionsFilamentExportBulkAction;
use AlperenErsoy\FilamentExport\FilamentExport;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Filament\Notifications\Notification;

class FilamentExportBulkAction extends ActionsFilamentExportBulkAction
{
    use CanDisableAdditionalColumns;
    use CanDisableFileName;
    use CanDisableFileNamePrefix;
    use CanDisableFilterColumns;
    use CanDisableFormats;
    use CanDisablePreview;
    use CanDisableTableColumns;
    use CanDownloadDirect;
    use CanFormatStates;
    use CanHaveExtraColumns;
    use CanHaveExtraViewData;
    use CanModifyWriters;
    use CanRefreshTable;
    use CanShowHiddenColumns;
    use CanUseSnappy;
    use HasAdditionalColumnsField;
    use HasCsvDelimiter;
    use HasDefaultFormat;
    use HasDefaultPageOrientation;
    use HasExportModelActions;
    use HasFileName;
    use HasFileNameField;
    use HasFilterColumnsField;
    use HasFormatField;
    use HasPageOrientationField;
    use HasPaginator;
    use HasTimeFormat;
    use HasUniqueActionId;

    // Maximum number of rows allowed to export in a single operation.
    protected const MAX_EXPORT_ROWS = 2000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueActionId('bulk-action');

        FilamentExport::setUpFilamentExportAction($this);

        $this
            ->form(static function ($action, $records, $livewire): array {
                if ($action->shouldDownloadDirect()) {
                    return [];
                }

                $perPage = $livewire->tableRecordsPerPage;

                if ($perPage === 'all') {
                    // Create a fake paginator for 'all' that contains all records
                    $count = $records->count();
                    $fakePaginator = new LengthAwarePaginator(
                        $records,
                        $count,
                        $count,
                        1,
                        [
                            'pageName' => 'exportPage',
                        ]
                    );
                    $action->paginator($fakePaginator);

                    return FilamentExport::getFormComponents($action);
                }

                $currentPage = LengthAwarePaginator::resolveCurrentPage('exportPage');

                $paginator = new LengthAwarePaginator(
                    $records->forPage($currentPage, $perPage),
                    $records->count(),
                    $perPage,
                    $currentPage,
                    [
                        'pageName' => 'exportPage',
                    ]
                );

                $action->paginator($paginator);

                return FilamentExport::getFormComponents($action);
            })
            ->action(static function ($action, $records, $data): ?StreamedResponse {
                // Protect against very large exports. Count the records defensively.
                $count = null;

                if (is_countable($records)) {
                    $count = count($records);
                } elseif (method_exists($records, 'count')) {
                    $count = $records->count();
                } else {
                    $count = 0;
                }

                if ($count > static::MAX_EXPORT_ROWS) {
                    Notification::make()
                        ->title('Export limit exceeded')
                        ->body("You requested {$count} rows but the maximum allowed export is " . static::MAX_EXPORT_ROWS . " rows.")
                        ->danger()
                        ->send();

                    // Stop the export. Returning null ends the action without sending a download.
                    return null;
                }

                $action->fillDefaultData($data);

                return FilamentExport::callDownload($action, $records, $data);
            });
    }
}
