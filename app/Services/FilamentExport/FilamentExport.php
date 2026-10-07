<?php

namespace App\Services\FilamentExport;

use Filament\Tables\Columns\Concerns\CanFormatState;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use AlperenErsoy\FilamentExport\FilamentExport as BaseFilamentExport;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Spatie\Image\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilamentExport extends BaseFilamentExport implements WithColumnWidths, WithEvents
{
    protected $imageAttributes = [];

    protected static $noPhotoImagePath = null;

    protected $isForExcel = false;

    public const OPTIONFORMATS = [
        // 'xlsx' => 'Excel With Images',
        // 'csv' => \Maatwebsite\Excel\Excel::CSV,
        'xlsx' => 'Excel',
        'pdf' => 'Pdf',
    ];

    public function getPdfView(): string
    {
        return 'filament.export.pdf';
    }

    public function getPdfViewData(): array
    {
        return array_merge(
            [
                'fileName' => $this->getFileName(),
                'columns' => $this->getAllColumns(),
                'rows' => $this->collection(),
            ],
            $this->getExtraViewData()
        );
    }

    // public static function getFormComponents(FilamentExportHeaderAction|FilamentExportBulkAction $action, Collection $records): array
    // {
    //     $action->fileNamePrefix($action->getFileNamePrefix() ?: $action->getTable()->getHeading());

    //     $tableColumns = $action->shouldShowHiddenColumns() ? $action->getLivewire()->getCachedTableColumns() : $action->getTable()->getColumns();

    //     $columns = collect($tableColumns)
    //         ->mapWithKeys(fn ($column) => [$column->getName() => $column->getLabel()])
    //         ->toArray();

    //     $updateTableView = function ($component, $livewire) use ($records, $action) {
    //         $data = $action instanceof FilamentExportBulkAction ? $livewire->mountedTableBulkActionData : $livewire->mountedTableActionData;

    //         $export = static::make()
    //             ->filteredColumns($data['filter_columns'] ?? [])
    //             ->additionalColumns($data['additional_columns'] ?? [])
    //             ->data($records)
    //             ->table($action->getTable())
    //             ->extraViewData($action->getExtraViewData())
    //             ->withHiddenColumns($action->shouldShowHiddenColumns());

    //         $table_view = $component->getContainer()->getComponent(fn ($component) => $component->getName() === 'table_view');

    //         $table_view->export($export);
    //     };

    //     return [
    //         \Filament\Forms\Components\TextInput::make('file_name')
    //             ->label($action->getFileNameFieldLabel())
    //             ->default($action->getFileName())
    //             ->hidden($action->isFileNameDisabled())
    //             ->rule('regex:/[a-zA-Z0-9\s_\\.\-\(\):]/')
    //             ->required(),
    //         \Filament\Forms\Components\Select::make('format')
    //             ->label($action->getFormatFieldLabel())
    //             ->options(static::OPTIONFORMATS)
    //             ->default($action->getDefaultFormat())
    //             ->reactive(),
    //         // \Filament\Forms\Components\Select::make('page_orientation')
    //         //     ->label($action->getPageOrientationFieldLabel())
    //         //     ->options(static::getPageOrientations())
    //         //     ->default($action->getDefaultPageOrientation())
    //         //     ->visible(fn ($get) => $get('format') === 'pdf')
    //         //     ->reactive(),
    //         \Filament\Forms\Components\CheckboxList::make('filter_columns')
    //             ->label($action->getFilterColumnsFieldLabel())
    //             ->options($columns)
    //             ->columns(4)
    //             ->default(array_keys($columns))
    //             ->afterStateUpdated($updateTableView)
    //             ->reactive()
    //             ->hidden($action->isFilterColumnsDisabled()),
    //         \Filament\Forms\Components\KeyValue::make('additional_columns')
    //             ->label($action->getAdditionalColumnsFieldLabel())
    //             ->keyLabel($action->getAdditionalColumnsTitleFieldLabel())
    //             ->valueLabel($action->getAdditionalColumnsDefaultValueFieldLabel())
    //             ->addButtonLabel($action->getAdditionalColumnsAddButtonLabel())
    //             ->afterStateUpdated($updateTableView)
    //             ->reactive()
    //             ->hidden($action->isAdditionalColumnsDisabled()),
    //         TableView::make('table_view')
    //             ->export(
    //                 static::make()
    //                     ->data($records)
    //                     ->table($action->getTable())
    //                     ->extraViewData($action->getExtraViewData())
    //                     ->withHiddenColumns($action->shouldShowHiddenColumns())
    //             )
    //             ->uniqueActionId($action->getUniqueActionId())
    //             ->reactive(),
    //     ];
    // }

    public function columnWidths(): array
    {
        $columns = [];

        foreach ($this->imageAttributes as $imageData) {
            $columns[$imageData['column']] = 165;
        }

        return $columns;
    }

    // public function download(): BinaryFileResponse|StreamedResponse
    // {
    //     if ($this->getFormat() === 'pdf') {
    //         $pdf = $this->getPdf();

    //         return response()->streamDownload(fn () => print($pdf->output()), "{$this->getFileName()}.{$this->getFormat()}");
    //     }

    //     if ($this->getFormat() === 'xlsx') {
    //         $this->isForExcel = true;
    //     }

    //     $response = Excel::download($this, "{$this->getFileName()}.{$this->getFormat()}", static::FORMATS[$this->getFormat()]);

    //     $deletedImages = [];

    //     foreach ($this->imageAttributes as $imageData) {
    //         if (
    //             ! in_array($imageData['image_path'], $deletedImages) &&
    //             file_exists($imageData['image_path']) &&
    //             @unlink($imageData['image_path'])
    //         ) {
    //             clearstatcache(false, $imageData['image_path']);

    //             $deletedImages[] = $imageData['image_path'];
    //         }
    //     }

    //     $deletedImages = [];

    //     return $response;
    // }

    public static function callDownload(FilamentExportHeaderAction|FilamentExportBulkAction $action, Collection $records, array $data)
    {
        return static::make()
            ->fileName($data['file_name'] ?? $action->getFileName())
            ->data($records)
            ->table($action->getTable())
            ->filteredColumns(! $action->isFilterColumnsDisabled() ? $data['filter_columns'] : [])
            ->additionalColumns(! $action->isAdditionalColumnsDisabled() ? $data['additional_columns'] : [])
            ->format($data['format'] ?? $action->getDefaultFormat())
            ->pageOrientation($data['page_orientation'] ?? $action->getDefaultPageOrientation())
            ->snappy($action->shouldUseSnappy())
            ->extraViewData($action->getExtraViewData())
            ->withHiddenColumns($action->shouldShowHiddenColumns())
            ->download();
    }

    public function collection(): Collection
    {
        $records = $this->getData();

        $columns = $this->getAllColumns();

        $items = [];
        $rowNum = 2;

        foreach ($records as $recordIndex => $record) {
            $item = [];
            $columnIndex = 0;

            foreach ($columns as $column) {
                $column = $column->record($record);

                $state = in_array(CanFormatState::class, class_uses($column)) ? $column->getFormattedState() : $column->getState();
                if (is_array($state)) {
                    $state = implode(', ', $state);
                } elseif ($column instanceof ImageColumn) {
                    $state = $this->isForExcel ? null : $column->getImagePath();
                    $url = ! empty($column->getImagePath()) ? $column->getImagePath() : Storage::disk('cos')->url(config('app.path.cos').'/public/no-image.png');
                    $columnAlpha = $this->numToAlpha($columnIndex);
                    $coordinate = $columnAlpha.$rowNum;

                    $this->imageAttributes[$coordinate] = [
                        'url' => $url,
                        'row' => $recordIndex,
                        'column_index' => $columnIndex,
                        'column' => $columnAlpha,
                        'coordinate' => $coordinate,
                        'row_num' => $rowNum,
                    ];
                } elseif ($column instanceof ViewColumn) {
                    $state = trim(preg_replace('/\s+/', ' ', strip_tags($column->render()->render())));
                }

                $item[$column->getName()] = $state;
                $columnIndex++;
            }

            $items[] = $item;
            $rowNum++;
        }

        $this->imageAttributes = array_values($this->imageAttributes);

        return collect($items);
    }

    public function getDrawings(): array
    {
        if (empty($this->imageAttributes)) {
            return [];
        }

        $drawings = [];
        $dateString = now()->format('Y-m-d');

        foreach ($this->imageAttributes as $i => &$imageData) {
            $image = @file_get_contents($imageData['url']);
            $imagePath = null;

            if ($image !== false) {
                $download_path = "temp/$dateString/".$imagePath = Str::orderedUuid();
                Storage::put($download_path, $image);
                $imagePath = Storage::path($download_path);

                Image::load($imagePath)
                    ->width(150)
                    ->height(150)
                    ->save();
            }

            if (! $imagePath) {
                $imagePath = static::noPhotoImagePath();
            }

            $drawing = new Drawing;
            $drawing->setPath($imagePath);
            $drawing->setCoordinates($imageData['coordinate']);
            $drawing->setWidth(150);

            $drawings[$i] = $drawing;
            $this->imageAttributes[$i]['drawing_index'] = $i;
            $this->imageAttributes[$i]['image_path'] = $imagePath;
        }

        return $drawings;
    }

    public static function noPhotoImagePath()
    {
        if (static::$noPhotoImagePath !== null) {
            return static::$noPhotoImagePath;
        }
        $dateString = now()->format('Y-m-d');
        $content = @file_get_contents(Storage::disk('cos')->url(config('app.path.cos').'/public/no-image.png'));
        $download_path = "temp/$dateString/".Str::orderedUuid();

        Storage::put($download_path, $content);

        return static::$noPhotoImagePath = Storage::path($download_path);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if (empty($this->imageAttributes)) {
                    return;
                }

                $workSheet = $event->sheet->getDelegate();

                $drawings = $this->getDrawings();
                $imageAttributes = collect($this->imageAttributes);

                foreach ($drawings as $i => $drawing) {
                    $drawing->setWorksheet($workSheet);
                    $sheet = $event->sheet->getDelegate();

                    $imageCoordinate = $imageAttributes->where('drawing_index', $i)->first();
                    $sheet->getRowDimension($imageCoordinate['row_num'])->setRowHeight(150);
                    $sheet->getColumnDimension($imageCoordinate['column'])->setWidth(15);
                }
            },
        ];
    }

    public function numToAlpha($n)
    {
        $r = '';
        for ($i = 1; $n >= 0 && $i < 10; $i++) {
            $r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))).$r;
            $n -= pow(26, $i);
        }

        return $r;
    }
}
